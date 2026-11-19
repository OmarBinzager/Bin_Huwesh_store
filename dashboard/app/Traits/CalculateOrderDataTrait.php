<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use App\Model\Coupon;
use App\Model\Product;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use function App\CentralLogics\translate;

trait CalculateOrderDataTrait
{
    /**
     * @throws ValidationException
     */
    protected function calculateOrderAmount(array $cart, ?string $couponCode = null, ?int $customerId = null, array $deliveryChargeInfo = [], bool $isPartiallyPaid = false, array $paymentInfo = [], int|string $userType = 0, array $lookIds = []): array|JsonResponse
    {
        if (empty($cart)) {
            return [
                'ordered_amount' => 0,
                'coupon_discount_amount' => 0,
                'coupon_discount_title' => null,
                'extra_discount' => 0,
                'delivery_charge_amount' => 0,
                'weight_charge_amount' => 0,
                'total_product_tax_amount' => 0,
                'free_delivery_charge_amount' => 0,
            ];
        }

        $productIds = collect($cart)->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $totalProductPrice = 0;
        $totalProductWeight = 0;
        $totalProductTax = 0;
        $deliveryCharge = 0;
        $productWeightCharge = 0;
        $cartByPid = [];
        foreach ($cart as $item) {
            $product = $products->get($item['product_id']);
            if (!$product) continue;

            if ($product['maximum_order_quantity'] < $item['quantity']) {
                throw ValidationException::withMessages([
                    'insufficient_amount' => [$product['name'] . ' ' . translate('quantity_must_be_equal_or_less_than ' . $product['maximum_order_quantity'])]
                ]);
            }

            $rawVariations = $product['variations'] ?? [];
            $hasVariations = !empty(is_string($rawVariations) ? json_decode($rawVariations, true) : $rawVariations);
            $singleProductPrice = $hasVariations ? (float)Helpers::variation_price($product, json_encode($item['variation'] ?? [])) : (float)($product['price'] ?? 0);
            if ($singleProductPrice <= 0) {
                $singleProductPrice = (float)($product['price'] ?? 0);
            }
            if ($singleProductPrice <= 0 && isset($item['price']) && (float)$item['price'] > 0) {
                $singleProductPrice = (float)$item['price'];
            }
            $categoryId = collect(json_decode($product['category_ids'], true))->firstWhere('position', 1)['id'] ?? null;
            $singleProductCategoryDiscount = Helpers::category_discount_calculate($categoryId, $singleProductPrice);
            $singleProductBaseDiscount = Helpers::discount_calculate($product, $singleProductPrice);
            $singleProductFlashDiscount = Helpers::flash_deal_discount_calculate((int)$product->id, $singleProductPrice);
            $effectiveCategoryDiscount = ($singleProductCategoryDiscount >= $singleProductPrice) ? 0 : $singleProductCategoryDiscount;
            $discountOnSingleProduct = max($effectiveCategoryDiscount, $singleProductBaseDiscount, $singleProductFlashDiscount);
            $singleProductTax = Helpers::tax_calculate($product, ($singleProductPrice - $discountOnSingleProduct));
            $qty = (int)($item['quantity'] ?? 1);
            $netUnitPrice = max(0, $singleProductPrice - $discountOnSingleProduct);
            $totalProductWeight += $product['weight'] * $qty;
            $totalProductPrice += $netUnitPrice * $qty;
            $totalProductTax += $singleProductTax * $qty;

            $pid = (int)$product->id;
            if (!isset($cartByPid[$pid])) {
                $cartByPid[$pid] = ['qty' => 0, 'subtotal' => 0.0];
            }
            $cartByPid[$pid]['qty'] += $qty;
            $cartByPid[$pid]['subtotal'] += $netUnitPrice * $qty;
        }

        // Server-verified Look / Bundle discount (applies only when all linked items of a published look are in cart)
        $lookBundleDiscount = 0;
        if (!empty($lookIds)) {
            $looks = \App\Model\StorefrontLook::with('items')
                ->whereIn('uid', $lookIds)
                ->where('published', true)
                ->where('whole_look_discount', '>', 0)
                ->get();
            foreach ($looks as $look) {
                $requiredPids = $look->items->pluck('product_id')->filter()->map(fn($v) => (int)$v)->unique();
                if ($requiredPids->isEmpty()) {
                    continue;
                }
                $allPresent = $requiredPids->every(fn($pid) => isset($cartByPid[$pid]) && $cartByPid[$pid]['qty'] > 0);
                if ($allPresent) {
                    $bundleCount = $requiredPids->min(fn($pid) => $cartByPid[$pid]['qty']);
                    $lookSubtotal = 0.0;
                    foreach ($requiredPids as $pid) {
                        $unitAvg = $cartByPid[$pid]['subtotal'] / max(1, $cartByPid[$pid]['qty']);
                        $lookSubtotal += $unitAvg * $bundleCount;
                    }
                    $lookBundleDiscount += (int)round(($lookSubtotal * (int)$look->whole_look_discount) / 100);
                }
            }
            $lookBundleDiscount = min($lookBundleDiscount, (int)round($totalProductPrice));
        }

        $totalProductPriceAfterLooks = max(0, $totalProductPrice - $lookBundleDiscount);
        $couponDiscountData = $this->calculateCouponAmount(userType: $userType, couponCode: $couponCode, customerId: $customerId, totalProductPrice: $totalProductPriceAfterLooks);
        $couponDiscountAmount = $couponDiscountData['coupon_discount_amount'];
        $couponDiscountTitle = $couponDiscountData['coupon_discount_title'] ?? null;
        $couponType = $couponDiscountData['coupon_type'];
        $freeDeliveryStatus = Helpers::get_business_settings('free_delivery_over_amount_status') == 1;
        $getFreeDeliveryAmount = Helpers::get_business_settings('free_delivery_over_amount');
        $freeDeliveryChargeAmount = 0;
        $totalOrderedAmountWithoutDeliveryCharge = max(0, $totalProductPriceAfterLooks + $totalProductTax - $couponDiscountAmount);
        $isSelfPickup = ($deliveryChargeInfo['order_type'] == 'self_pickup' && ($deliveryChargeInfo['is_legacy_pickup'] ?? false));
        $qualifiesForFreeDeliveryOverAmount = $freeDeliveryStatus && (float)$getFreeDeliveryAmount > 0 && $totalProductPrice >= (float)$getFreeDeliveryAmount;
        if (!$isSelfPickup && !$qualifiesForFreeDeliveryOverAmount && ($couponType != 'free_delivery' || !$freeDeliveryStatus || $getFreeDeliveryAmount > ($totalOrderedAmountWithoutDeliveryCharge))) {
            $deliveryCharge = Helpers::get_delivery_charge(branchId: $deliveryChargeInfo['branch_id'], distance: $deliveryChargeInfo['distance'], selectedDeliveryArea: $deliveryChargeInfo['selected_delivery_area'], orderType: $deliveryChargeInfo['order_type'] ?? null);
            $productWeightCharge = Helpers::productWeightChargeCalculation(branchId: $deliveryChargeInfo['branch_id'], weight: $totalProductWeight);
        }

        if (!$isSelfPickup && ($qualifiesForFreeDeliveryOverAmount || ($couponType == 'free_delivery' && $freeDeliveryStatus && $getFreeDeliveryAmount <= ($totalOrderedAmountWithoutDeliveryCharge)))) {
            $freeDeliveryChargeAmount = Helpers::get_delivery_charge(branchId: $deliveryChargeInfo['branch_id'], distance: $deliveryChargeInfo['distance'], selectedDeliveryArea: $deliveryChargeInfo['selected_delivery_area'], orderType: $deliveryChargeInfo['order_type'] ?? null);
        }

        $totalOrderedAmount = $totalOrderedAmountWithoutDeliveryCharge + $deliveryCharge + $productWeightCharge;
        $minimumAmount = Helpers::get_business_settings('minimum_order_value');
        $maximumAmount = Helpers::get_business_settings('maximum_amount_for_cod_order');
        $maxAmountForCODStatus = Helpers::get_business_settings('maximum_amount_for_cod_order_status');
        $walletStatus = Helpers::get_business_settings('wallet_status');
        $customer = auth('api')->user() ? User::find(auth('api')->user()->id) : null;
        if ($minimumAmount > $totalOrderedAmount) {
            throw ValidationException::withMessages([
                'insufficient_amount' => [translate('Minimum order amount must be equal or more than ' . $minimumAmount)]
            ]);
        }
        if ($paymentInfo['payment_method'] == 'cash_on_delivery' && $maxAmountForCODStatus && ($maximumAmount < $totalOrderedAmount)) {
            throw ValidationException::withMessages([
                'maximum_amount_over' => [translate('For Cash on Delivery, maximum order amount must be equal or less than ' . $maximumAmount)]
            ]);
        }
        if ($paymentInfo['payment_method'] == 'wallet_payment' && !$walletStatus) {
            throw ValidationException::withMessages([
                'payment_method' => [translate('customer_wallet_status_is_disable')]
            ]);
        }

        if ($paymentInfo['payment_method'] == 'wallet_payment' && $customer->wallet_balance < $totalOrderedAmount) {
            throw ValidationException::withMessages([
                'payment_method' => [translate('you_do_not_have_sufficient_balance_in_wallet')]
            ]);
        }

        if ($isPartiallyPaid && auth('api')->user()) {

            if (!$walletStatus) {
                throw ValidationException::withMessages([
                    'payment_method' => [translate('customer_wallet_status_is_disable')]
                ]);
            }
            if (isset($customer) && $customer->wallet_balance > $totalOrderedAmount) {
                throw ValidationException::withMessages([
                    'payment_method' => [translate('since your wallet balance is more than order amount, you can not place partial order')]
                ]);
            }
            if (isset($customer) && $customer->wallet_balance < 1) {
                throw ValidationException::withMessages([
                    'payment_method' => [translate('since your wallet balance is less than 1, you can not place partial order')]
                ]);
            }
        }

        return [
            'ordered_amount' => $totalOrderedAmount,
            'coupon_discount_amount' => $couponDiscountAmount,
            'coupon_discount_title' => $couponDiscountTitle,
            'extra_discount' => $lookBundleDiscount,
            'delivery_charge_amount' => $deliveryCharge,
            'weight_charge_amount' => $productWeightCharge,
            'total_product_tax_amount' => $totalProductTax,
            'free_delivery_charge_amount' => $freeDeliveryChargeAmount,
        ];
    }

    /**
     * @throws ValidationException
     */
    protected function calculateCouponAmount(int|string $userType = 0, ?string $couponCode, ?int $customerId = null, float $totalProductPrice)
    {
        if (!$couponCode || !$customerId) {
            return [
                'coupon_discount_amount' => 0,
                'coupon_discount_title' => null,
                'coupon_type' => null,
            ];
        }
        $coupons = $this->couponList($customerId, $userType);
        $coupon = $coupons->firstWhere('code', $couponCode);
        if (!$coupon) {
            return [
                'coupon_discount_amount' => 0,
                'coupon_discount_title' => null,
                'coupon_type' => null,
            ];
        }

        if ($coupon->min_purchase > $totalProductPrice) {
            throw ValidationException::withMessages([
                'coupon_code' => [translate('minimum_purchase_amount_for_this_coupon_is') . ' ' . Helpers::set_symbol($coupon->min_purchase)]
            ]);
        }
        $couponDiscountAmount = 0;

        if ($coupon->discount_type == 'amount') {
            $couponDiscountAmount = $coupon->discount;
            if ($couponDiscountAmount > $totalProductPrice) {
                $couponDiscountAmount = $totalProductPrice;
            }
        }

        if ($coupon->discount_type == 'percent') {
            $couponDiscountAmount = (($totalProductPrice * $coupon->discount) / 100);
            if ($coupon->max_discount > 0 && $couponDiscountAmount > $coupon->max_discount) {
                $couponDiscountAmount = $coupon->max_discount;
            }
        }

        return [
            'coupon_discount_amount' => $couponDiscountAmount,
            'coupon_discount_title' => $coupon->title ?? null,
            'coupon_type' => $coupon->coupon_type,
        ];
    }

    protected function couponList($customerId, $userType)
    {
        if (is_null($customerId) || $customerId == 0) {
            return collect();
        }
        return Coupon::withCount(['orders as used_count' => function ($query) use ($customerId, $userType) {
            $query->where(['user_id' => $customerId, 'is_guest' => $userType]);
        }])
            ->active()
            ->get()
            ->filter(function ($coupon) use ($customerId) {
                if ($coupon->coupon_type == 'first_order') {
                    return $coupon->used_count == 0;
                }
                if ($coupon->coupon_type == 'free_delivery') {
                    return $coupon->used_count < $coupon->limit;
                }
                if ($coupon->coupon_type == 'customer_wise') {
                    return $coupon->customer_id == $customerId && $coupon->used_count < $coupon->limit;
                }

                return $coupon->used_count < $coupon->limit || $coupon->limit == null;
            });

    }
}
