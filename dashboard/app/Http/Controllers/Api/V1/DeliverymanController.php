<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\DeliveryHistory;
use App\Model\DeliveryMan;
use App\Model\DeliveryManCustodyTransaction;
use App\Model\Order;
use App\Models\OrderPartialPayment;
use App\Services\DeliveryManCustodyService;
use App\Traits\HelperTrait;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliverymanController extends Controller
{
    use HelperTrait;
    public function __construct(
        private BusinessSetting $businessSetting,
        private DeliveryHistory $deliveryHistory,
        private DeliveryMan $deliveryman,
        private Order $order,
        private User $user
    ){}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => '401', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }
        return response()->json($deliveryman, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => '401', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }
        $orders = $this->order->with(['delivery_address','customer', 'partial_payment', 'order_image'])
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery'])
            ->where(['delivery_man_id' => $deliveryman['id']])
            ->get();

        return response()->json($orders, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function recordLocationData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        $outForDeliveryOrders = $this->order->where(['order_status' => 'out_for_delivery', 'delivery_man_id' => $deliveryman->id])->pluck('id');

        foreach ($outForDeliveryOrders as $orderId) {
            $this->deliveryHistory->updateOrInsert(
                [
                    'order_id' => $orderId, // Ensure order_id is set properly
                    'deliveryman_id' => $deliveryman->id,
                ],
                [
                    'longitude' => $request['longitude'],
                    'latitude' => $request['latitude'],
                    'time' => now(),
                    'location' => $request['location'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        return response()->json(['message' => translate('location recorded')], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();

        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        $history = $this->deliveryHistory->where(['order_id' => $request['order_id'], 'deliveryman_id' => $deliveryman['id']])->get();

        return response()->json($history, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOrderStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'order_id' => 'required',
            'status' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        $this->order->where(['id' => $request['order_id'], 'delivery_man_id' => $deliveryman['id']])->update([
            'order_status' => $request['status']
        ]);

        $order= $this->order->find($request['order_id']);
        $languageCode = $order->is_guest == 0 ? ($order->customer ? $order->customer->language_code : 'en') : ($order->guest ? $order->guest->language_code : 'en');
        $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);

        if ($request['status']=='out_for_delivery'){
            $message = Helpers::order_status_update_message('ord_start');

            if ($languageCode != 'en'){
                $message = $this->translate_message($languageCode, 'ord_start');
            }
            $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

            // Instant Notification & Event for Admin & Branch Dashboard
            try {
                $dmName = trim(($deliveryman->f_name ?? '') . ' ' . ($deliveryman->l_name ?? ''));
                if (empty($dmName)) {
                    $dmName = translate('Delivery Man');
                }
                $adminTitle = translate('Delivery Movement Update') . " - #" . $order->id;
                $adminDesc = "الموصل ({$dmName}) أكد استلام مهمة الطلب رقم (#{$order->id}) وهو الآن في الطريق لأخذ الطلب من البراند.";

                $event = [
                    'id' => $order->id . '_' . time(),
                    'order_id' => (string)$order->id,
                    'title' => $adminTitle,
                    'description' => $adminDesc,
                    'type' => 'delivery_man_confirmed',
                    'delivery_man_name' => $dmName,
                    'branch_id' => $order->branch_id,
                    'time' => time(),
                    'url' => route('admin.orders.details', ['id' => $order->id]),
                ];
                \Illuminate\Support\Facades\Cache::put('latest_delivery_event', $event, now()->addMinutes(5));

                $pushData = [
                    'title' => $adminTitle,
                    'description' => $adminDesc,
                    'order_id' => (string) $order->id,
                    'image' => '',
                    'type' => 'delivery_man_confirmed',
                    'delivery_man_name' => $dmName,
                    'order_status' => 'out_for_delivery',
                ];

                Helpers::send_push_notif_to_topic($pushData, 'grofresh_admin_message', route('admin.orders.details', ['id' => $order->id]));
                if ($order->branch_id) {
                    Helpers::send_push_notif_to_topic($pushData, 'grofresh_branch_' . $order->branch_id . '_message', route('branch.orders.details', ['id' => $order->id]));
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed to send admin notification on delivery confirmation: ' . $e->getMessage());
            }

        }elseif ($request['status']=='delivered'){
            $message = Helpers::order_status_update_message('delivery_boy_delivered');

            if ($languageCode != 'en'){
                $message = $this->translate_message($languageCode, 'delivery_boy_delivered');
            }
            $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

            if ($order->is_guest == 0){
                if($order->user_id) CustomerLogic::create_loyalty_point_transaction($order->user_id, $order->id, $order->order_amount, 'order_place');

                $user = $this->user->find($order->user_id);
                $isFirstOrder = $this->order->where(['user_id' => $user->id, 'order_status' => 'delivered'])->count('id');
                $referred_by_user = $this->user->find($user->referred_by);

                if ($isFirstOrder < 2 && isset($user->referred_by) && isset($referred_by_user)){
                    if($this->businessSetting->where('key','ref_earning_status')->first()?->value == 1) {
                        CustomerLogic::referral_earning_wallet_transaction($order->user_id, 'referral_order_place', $referred_by_user->id);
                    }
                }
            }

            if ($order['payment_method'] == 'cash_on_delivery'){
                $partialData = OrderPartialPayment::where(['order_id' => $order->id])->first();
                if ($partialData){
                    $partial = new OrderPartialPayment;
                    $partial->order_id = $order['id'];
                    $partial->paid_with = 'cash_on_delivery';
                    $partial->paid_amount = $partialData->due_amount;
                    $partial->due_amount = 0;
                    $partial->save();
                }

                DeliveryManCustodyService::recordCashCollection($order, $deliveryman);
            }
        }

        try {
            if ($value){
                $data=[
                    'title'=>'Order',
                    'description'=>$value,
                    'order_id'=>$order['id'],
                    'image'=>'',
                    'type' => 'order'
                ];
                Helpers::send_push_notif_to_device($customerFcmToken,$data);
            }
        } catch (\Exception $e) {

        }

        return response()->json(['message' => translate('Status updated')], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        $orderId = $request['order_id'] ?? $request['id'] ?? null;
        if (!$orderId) {
            return response()->json([], 200);
        }

        $order = $this->order->with(['details'])->where(['delivery_man_id' => $deliveryman['id'], 'id' => $orderId])->first();
        if (!$order) {
            $order = $this->order->with(['details'])->find($orderId);
        }

        if (!$order || !$order->details) {
            return response()->json([], 200);
        }

        $details = $order->details;

        foreach ($details as $detail) {
            try {
                $detail['add_on_ids'] = json_decode($detail['add_on_ids']);
                $detail['add_on_qtys'] = json_decode($detail['add_on_qtys']);

                $variation = json_decode($detail['variation']);
                if (!is_array($variation)) {
                    $variation = $variation ? [$variation] : [];
                }
                $detail['variation'] = $variation;

                if (is_array($variation) && isset($variation[0])) {
                    $detail['formatted_variation'] = $variation[0];
                } elseif (is_object($variation)) {
                    $detail['formatted_variation'] = $variation;
                } else {
                    $detail['formatted_variation'] = null;
                }

                $decodedProduct = json_decode($detail['product_details'], true);
                $sellerName = null;

                if (is_array($decodedProduct)) {
                    $detail['product_details'] = Helpers::product_data_formatting($decodedProduct);
                    if (!empty($decodedProduct['seller']['name'])) {
                        $sellerName = $decodedProduct['seller']['name'];
                    } elseif (!empty($decodedProduct['seller_name'])) {
                        $sellerName = $decodedProduct['seller_name'];
                    } elseif (!empty($decodedProduct['seller_id'])) {
                        try {
                            if (\Illuminate\Support\Facades\Schema::hasTable('sellers')) {
                                $seller = \Illuminate\Support\Facades\DB::table('sellers')->where('id', $decodedProduct['seller_id'])->first();
                                $sellerName = $seller?->name;
                            }
                        } catch (\Throwable $e) {}
                    }
                } else {
                    $detail['product_details'] = null;
                    if (!empty($detail['product_id'])) {
                        try {
                            $fallbackProduct = $this->product->find($detail['product_id']);
                            if ($fallbackProduct) {
                                $detail['product_details'] = Helpers::product_data_formatting(json_decode(json_encode($fallbackProduct), true));
                            }
                        } catch (\Throwable $e) {}
                    }
                }

                if (empty($sellerName) && !empty($detail['product_id'])) {
                    try {
                        if (\Illuminate\Support\Facades\Schema::hasTable('sellers')) {
                            $prod = \Illuminate\Support\Facades\DB::table('products')->where('id', $detail['product_id'])->first();
                            if ($prod && !empty($prod->seller_id)) {
                                $seller = \Illuminate\Support\Facades\DB::table('sellers')->where('id', $prod->seller_id)->first();
                                $sellerName = $seller?->name;
                            }
                        }
                    } catch (\Throwable $e) {}
                }

                $detail['seller_name'] = $sellerName;
                if (is_array($detail['product_details'])) {
                    $detail['product_details']['seller_name'] = $sellerName;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error parsing order detail item: ' . $e->getMessage());
            }
        }
        return response()->json($details, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => '401', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }
        $orders = $this->order->with(['delivery_address','customer'])->where(['delivery_man_id' => $deliveryman['id']])->get();
        return response()->json($orders, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getLastLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deliveryman_id' => 'required',
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $lastData = $this->deliveryHistory
            ->where(['deliveryman_id' => $request['deliveryman_id'], 'order_id' => $request['order_id']])
            ->latest()
            ->first();

        return response()->json($lastData, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function orderPaymentStatusUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        if ($this->order->where(['delivery_man_id' => $deliveryman['id'], 'id' => $request['order_id']])->first()) {
            $this->order->where(['delivery_man_id' => $deliveryman['id'], 'id' => $request['order_id']])->update([
                'payment_status' => $request['status']
            ]);

            $updatedOrder = $this->order->find($request['order_id']);
            if ($request['status'] == 'paid' && $updatedOrder && $updatedOrder->payment_method == 'cash_on_delivery') {
                DeliveryManCustodyService::recordCashCollection($updatedOrder, $deliveryman);
            }

            return response()->json(['message' => translate('Payment status updated')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('not found!')]
            ]
        ], 404);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        $this->deliveryman->where(['id' => $deliveryman['id']])->update([
            'fcm_token' => $request['fcm_token']
        ]);

        return response()->json(['message' => translate('successfully updated!')], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function changeLanguage(Request $request): JsonResponse
    {
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (isset($deliveryman)){
            $deliveryman->language_code = $request->language_code ?? 'en';
            $deliveryman->save();
        }
        return response()->json(['delivery_man' => $deliveryman], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function orderModel(Request $request): JsonResponse
    {
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();

        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [['code' => 'delivery-man', 'message' => translate('Invalid token!')]]], 401);
        }

        $order = $this->order
            ->with(['customer', 'partial_payment', 'order_image', 'delivery_address'])
            ->where(['delivery_man_id' => $deliveryman['id'], 'id' => $request->id])
            ->first();

        return response()->json($order, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrdersCount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where('auth_token', $request['token'])->first();

        if (!$deliveryman) {
            return response()->json(['errors' => [['code' => 'delivery-man', 'message' => translate('Invalid token!')]]], 401);
        }

        $orders['out_for_delivery'] = $this->order->where(['delivery_man_id' => $deliveryman->id, 'order_status' => 'out_for_delivery'])->count();

        return response()->json($orders, 200);
    }

    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where('auth_token', $request['token'])->first();

        if (!$deliveryman) {
            return response()->json(['errors' => [['code' => 'delivery-man', 'message' => translate('Invalid token!')]]], 401);
        }

        $ongoingOrders = $this->order
            ->where('delivery_man_id', $deliveryman->id)
            ->whereIn('order_status', [CONFIRMED, PROCESSING, OUT_FOR_DELIVERY])
            ->exists();

        if ($ongoingOrders) {
            return response()->json([
                'errors' => [['code' => 'delivery-man', 'message' => translate('You cannot delete your account while you have ongoing orders!')]]], 403);
        }

        $deliveryman->delete();
        return response()->json([
            'message' => translate('Deliveryman deleted successfully.')
        ], 200);

    }

    /**
     * Get delivery man custody wallet details.
     */
    public function getWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        $wallet = DeliveryManCustodyService::getOrCreateWallet($deliveryman->id);

        $recentTransactions = DeliveryManCustodyTransaction::with([
                'admin:id,f_name,l_name',
                'order:id,order_amount,user_id,delivery_address,payment_method,created_at',
                'order.customer:id,f_name,l_name,phone'
            ])
            ->where('delivery_man_id', $deliveryman->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($tx) {
                $txArray = $tx->toArray();
                $txArray['admin_name'] = $tx->admin ? trim($tx->admin->f_name . ' ' . $tx->admin->l_name) : null;
                return $txArray;
            });

        return response()->json([
            'cash_in_hand' => (float) $wallet->cash_in_hand,
            'total_collected_cash' => (float) $wallet->total_collected_cash,
            'total_settled_amount' => (float) $wallet->total_settled_amount,
            'collected_orders_count' => (int) $wallet->collected_orders_count,
            'settlement_status' => $wallet->settlement_status,
            'last_settlement_at' => $wallet->last_settlement_at ? $wallet->last_settlement_at->toIso8601String() : null,
            'recent_transactions' => $recentTransactions,
        ], 200);
    }

    /**
     * Get custody wallet transactions with filtering and pagination.
     */
    public function getWalletTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        $limit = (int) ($request['limit'] ?? 10);
        $offset = (int) ($request['offset'] ?? 1);
        $type = $request['type'] ?? 'all';

        $query = DeliveryManCustodyTransaction::with([
            'admin:id,f_name,l_name',
            'order:id,order_amount,user_id,delivery_address,payment_method,created_at',
            'order.customer:id,f_name,l_name,phone'
        ])->where('delivery_man_id', $deliveryman->id);

        if ($type !== 'all' && in_array($type, ['cash_collection', 'custody_settlement'])) {
            $query->where('transaction_type', $type);
        }

        $paginator = $query->latest()->paginate($limit, ['*'], 'page', $offset);

        $transactions = collect($paginator->items())->map(function ($tx) {
            $adminName = $tx->admin ? trim($tx->admin->f_name . ' ' . $tx->admin->l_name) : null;
            $customerName = $tx->customer_name;
            $customerPhone = $tx->customer_phone;
            if (empty($customerName) && $tx->order) {
                if ($tx->order->customer) {
                    $customerName = trim($tx->order->customer->f_name . ' ' . $tx->order->customer->l_name);
                    $customerPhone = $tx->order->customer->phone;
                } elseif ($tx->order->delivery_address) {
                    $addr = is_array($tx->order->delivery_address) ? $tx->order->delivery_address : json_decode($tx->order->delivery_address, true);
                    $customerName = $addr['contact_person_name'] ?? null;
                    $customerPhone = $addr['contact_person_number'] ?? null;
                }
            }

            $txArray = $tx->toArray();
            $txArray['admin_name'] = $adminName;
            $txArray['customer_name'] = $customerName ?: translate('Customer');
            $txArray['customer_phone'] = $customerPhone;
            $txArray['expected_amount'] = (float)$tx->balance_before;
            $txArray['received_amount'] = (float)$tx->amount;
            $txArray['remaining_amount'] = (float)$tx->balance_after;
            return $txArray;
        });

        return response()->json([
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'transactions' => $transactions,
        ], 200);
    }

}
