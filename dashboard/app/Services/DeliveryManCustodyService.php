<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Model\DeliveryMan;
use App\Model\DeliveryManCustodyTransaction;
use App\Model\DeliveryManWallet;
use App\Model\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryManCustodyService
{
    /**
     * Get or initialize a delivery man wallet.
     */
    public static function getOrCreateWallet(int $deliveryManId): DeliveryManWallet
    {
        return DeliveryManWallet::firstOrCreate(
            ['delivery_man_id' => $deliveryManId],
            [
                'cash_in_hand' => 0.00,
                'total_collected_cash' => 0.00,
                'total_settled_amount' => 0.00,
                'collected_orders_count' => 0,
            ]
        );
    }

    /**
     * Record cash collection when a COD order is delivered and paid.
     * Prevents duplicate recording for the same order.
     */
    public static function recordCashCollection(Order $order, DeliveryMan $deliveryMan): ?DeliveryManCustodyTransaction
    {
        // Must be cash on delivery
        if ($order->payment_method !== 'cash_on_delivery') {
            return null;
        }

        // Avoid recording canceled or non-valid orders
        if (in_array($order->order_status, ['canceled', 'returned', 'failed'])) {
            return null;
        }

        return DB::transaction(function () use ($order, $deliveryMan) {
            // Check if already recorded for this order to prevent duplicates
            $existing = DeliveryManCustodyTransaction::where('order_id', $order->id)
                ->where('transaction_type', 'cash_collection')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $wallet = self::getOrCreateWallet($deliveryMan->id);

            // Amount to collect: order_amount or currency_amount
            $amount = (float) $order->order_amount;
            $balanceBefore = (float) $wallet->cash_in_hand;
            $balanceAfter = $balanceBefore + $amount;

            // Update wallet
            $wallet->cash_in_hand = $balanceAfter;
            $wallet->total_collected_cash += $amount;
            $wallet->collected_orders_count += 1;
            $wallet->save();

            // Extract customer info
            $customerName = 'عميل';
            $customerPhone = '';
            $customerId = $order->user_id;

            if ($order->is_guest == 0 && $order->customer) {
                $customerName = trim(($order->customer->f_name ?? '') . ' ' . ($order->customer->l_name ?? ''));
                $customerPhone = $order->customer->phone ?? '';
            } elseif ($order->is_guest == 1 && $order->guest) {
                $customerName = trim(($order->guest->f_name ?? '') . ' ' . ($order->guest->l_name ?? ''));
                $customerPhone = $order->guest->phone ?? '';
            }

            if ((empty($customerName) || $customerName === 'عميل') && !empty($order->delivery_address)) {
                $addr = is_array($order->delivery_address) ? $order->delivery_address : json_decode($order->delivery_address, true);
                if (!empty($addr['contact_person_name'])) {
                    $customerName = $addr['contact_person_name'];
                }
                if (!empty($addr['contact_person_number'])) {
                    $customerPhone = $addr['contact_person_number'];
                }
            }

            // Create ledger transaction
            $transaction = DeliveryManCustodyTransaction::create([
                'transaction_id' => 'COL-' . $order->id . '-' . strtoupper(uniqid()),
                'delivery_man_id' => $deliveryMan->id,
                'transaction_type' => 'cash_collection',
                'order_id' => $order->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'customer_id' => $customerId,
                'customer_name' => $customerName ?: translate('Customer'),
                'customer_phone' => $customerPhone,
                'notes' => translate('Cash Collection') . ' - #' . $order->id,
            ]);

            // Notify Admin Dashboard
            self::notifyAdminCashCollected($deliveryMan, $order, $amount, $customerName);

            return $transaction;
        });
    }

    /**
     * Settle cash custody with the administration (full or partial).
     */
    public static function settleCustody(
        DeliveryMan $deliveryMan,
        float $settledAmount,
        int $adminId,
        string $paymentMethod = 'cash',
        ?string $reference = null,
        ?string $notes = null
    ): DeliveryManCustodyTransaction {
        return DB::transaction(function () use ($deliveryMan, $settledAmount, $adminId, $paymentMethod, $reference, $notes) {
            $wallet = self::getOrCreateWallet($deliveryMan->id);

            $balanceBefore = (float) $wallet->cash_in_hand;

            if ($settledAmount <= 0) {
                throw new \InvalidArgumentException(translate('Amount must be greater than zero'));
            }

            if ($settledAmount > $balanceBefore) {
                throw new \InvalidArgumentException(translate('Amount exceeds current cash in hand'));
            }

            $balanceAfter = round($balanceBefore - $settledAmount, 2);
            $settlementType = ($balanceAfter <= 0) ? 'full' : 'partial';

            // Update wallet
            $wallet->cash_in_hand = $balanceAfter;
            $wallet->total_settled_amount += $settledAmount;
            $wallet->last_settlement_at = now();
            $wallet->save();

            // Create ledger transaction
            return DeliveryManCustodyTransaction::create([
                'transaction_id' => 'SETT-' . strtoupper(uniqid()),
                'delivery_man_id' => $deliveryMan->id,
                'transaction_type' => 'custody_settlement',
                'order_id' => null,
                'amount' => $settledAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'settled_by_admin_id' => $adminId,
                'settlement_type' => $settlementType,
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'notes' => $notes ?: ($settlementType === 'full' ? translate('Fully Settled') : translate('Partially Settled')),
            ]);
        });
    }

    /**
     * Send push notification to Admin dashboard via FCM topic.
     */
    protected static function notifyAdminCashCollected(
        DeliveryMan $deliveryMan,
        Order $order,
        float $amount,
        string $customerName
    ): void {
        try {
            $dmName = trim(($deliveryMan->f_name ?? '') . ' ' . ($deliveryMan->l_name ?? ''));
            $title = translate('Cash Custody Collection');
            $msgTemplate = translate('Delivery man (:dm) collected (:amount) for order #:order from customer (:customer).');
            $description = str_replace(
                [':dm', ':amount', ':order', ':customer'],
                [$dmName, Helpers::set_symbol($amount), $order->id, $customerName],
                $msgTemplate
            );

            // Cache event for real-time dashboard notification
            $event = [
                'id' => 'custody_' . $order->id . '_' . time(),
                'title' => $title,
                'description' => $description,
                'type' => 'custody_collection',
                'delivery_man_id' => $deliveryMan->id,
                'delivery_man_name' => $dmName,
                'customer_name' => $customerName,
                'amount' => $amount,
                'url' => route('admin.delivery-man.custody.details', ['id' => $deliveryMan->id]),
            ];
            \Illuminate\Support\Facades\Cache::put('latest_delivery_event', $event, now()->addMinutes(5));

            $data = [
                'title' => $title,
                'description' => $description,
                'order_id' => (string) $order->id,
                'type' => 'custody_collection',
                'delivery_man_id' => (string) $deliveryMan->id,
                'amount' => (string) $amount,
                'customer_name' => $customerName,
                'image' => '',
            ];

            // Send to topic grofresh_admin_message
            Helpers::send_push_notif_to_topic($data, 'grofresh_admin_message');
        } catch (\Throwable $e) {
            Log::warning('Failed to push custody notification to admin: ' . $e->getMessage());
        }
    }
}
