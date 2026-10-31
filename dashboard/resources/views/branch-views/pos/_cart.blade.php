@php
    use App\Model\Product;
    use App\CentralLogics\Helpers;

    $selectedBranchId = session()->get('branch_id') ?? (auth('branch')->id() ?? 1);
    if (!isset($deliveryMen)) {
        $deliveryMen = \App\Model\DeliveryMan::where(['is_active' => 1])
            ->where(function($query) use ($selectedBranchId) {
                $query->where('branch_id', $selectedBranchId)
                      ->orWhere('branch_id', 0);
            })->get();
    }
    if (!isset($chargeSetup)) {
        $chargeSetup = \App\Models\DeliveryChargeSetup::where('branch_id', $selectedBranchId)->first();
    }
    $fastDeliveryCharge = $chargeSetup?->fast_delivery_charge ?? ($chargeSetup?->fixed_delivery_charge ?? 0);
    $convenientDeliveryCharge = $chargeSetup?->convenient_delivery_charge ?? 0;
    $customer = session()->has('customer_id') ? \App\User::find(session('customer_id')) : null;
    $walletStatus = (bool)(\App\Model\BusinessSetting::where('key', 'wallet_status')->first()->value ?? false);
    $offlinePaymentMethods = \App\Models\OfflinePaymentMethod::active()->get();

    $subtotal = 0;
    $extraDiscount = session()->get('extra_discount', 0);
    $extraDiscountType = session()->get('extra_discount_type', 'amount');
    $discount_on_product = 0;
    $totalTax = 0;
    $updatedTotalTax = 0;
    $vatStatus = Helpers::get_business_settings('product_vat_tax_status') === 'included' ? 'included' : 'excluded';
    $productWeight = 0;
@endphp

<div class="d-flex flex-row cart--table-scroll">
    <div class="table-responsive">
        <table class="table table-bordered border-left-0 border-right-0 middle-align">
            <thead class="thead-light">
                <tr>
                    <th scope="col">{{ translate('item') }}</th>
                    <th scope="col" class="text-center">{{ translate('qty') }}</th>
                    <th scope="col">{{ translate('price') }}</th>
                    <th scope="col">{{ translate('delete') }}</th>
                </tr>
            </thead>

            <tbody>
                @if (session()->has('cart') && count(session()->get('cart')) > 0)
                    @foreach (session()->get('cart') as $key => $cartItem)
                        @if (is_array($cartItem))
                            <?php
                            $product_subtotal = $cartItem['price'] * $cartItem['quantity'];
                            $discount_on_product += $cartItem['discount'] * $cartItem['quantity'];
                            $subtotal += $product_subtotal;

                            $product = Product::find($cartItem['id']);
                            $totalTax += Helpers::tax_calculate($product, ($cartItem['price'] - $cartItem['discount'])) * $cartItem['quantity'];
                            $updatedTotalTax += $vatStatus === 'included' ? 0 : Helpers::tax_calculate($product, ($cartItem['price'] - $cartItem['discount'])) * $cartItem['quantity'];
                            $productWeight += $cartItem['weight'] * $cartItem['quantity'];
                            ?>

                            <tr data-product-id="{{ $cartItem['id'] }}"
                                data-product-quantity="{{ $cartItem['quantity'] }}">
                                <td>
                                    <div class="media align-items-center">
                                        @if (!empty(json_decode($cartItem['image'], true)))
                                            <img class="avatar avatar-sm mr-1"
                                                src="{{ asset('storage/app/public/product') }}/{{ json_decode($cartItem['image'], true)[0] }}"
                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/2.png') }}'"
                                                alt="{{ $cartItem['name'] }} {{ translate('image') }}">
                                        @else
                                            <img class="avatar avatar-sm mr-1"
                                                src="{{ asset('public/assets/admin/img/160x160/2.png') }}">
                                        @endif
                                        <div class="media-body">
                                            <h6 class="text-hover-primary mb-0">{{ Str::limit($cartItem['name'], 10) }}
                                            </h6>
                                            <small>{{ Str::limit($cartItem['variant'], 20) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-items-center text-center">
                                    <input type="number" data-key="{{ $key }}"
                                        class="amount--input form-control text-center" id="{{ $cartItem['id'] }}"
                                        value="{{ $cartItem['quantity'] }}" min="1"
                                        max="{{ $cartItem['total_stock'] }}" onkeyup="updateQuantity(event)">
                                </td>
                                <td class="text-center px-0 py-1">
                                    <div class="btn text-left">
                                        {{ Helpers::set_symbol($product_subtotal) }}
                                    </div>
                                </td>
                                <td class="align-items-center text-center">
                                    <div class="d-flex flex-wrap justify-content-center">
                                        <a href="javascript:" onclick="removeFromCart({{ $key }}, this)"
                                            class="btn btn-sm btn--danger rounded-full action-btn">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endif

                @if (!session()->has('cart') || session('cart')->isEmpty())
                    <tr>
                        <td colspan="4" class="text-center">
                            <p class="mb-0 text-center p-2 font-weight-bold">{{ translate('No product added yet') }}
                            </p>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@php
$cart = session()->get('cart', []);
$total = $subtotal;
$sessionTotal = $subtotal - $discount_on_product;
\Session::put('total', $sessionTotal);

$total -= $discount_on_product;

if ($extraDiscountType === 'percent' && $extraDiscount > 0) {
    $extraDiscountAmount = ($total * $extraDiscount) / 100;
} elseif ($extraDiscount > $total) {
    $extraDiscount = 0;
    $extraDiscountAmount = 0;

    session()->forget('extra_discount');
    session()->forget('extra_discount_type');
} else {
    $extraDiscountAmount = $extraDiscount;
}

$total -= $extraDiscountAmount;

$delivery_charge = 0;
$productWeightCharge = 0;

$deliveryServiceType = session()->get('delivery_service_type') ?? 'fast_delivery';
if (session()->get('order_type') == 'home_delivery') {
    $distance = 0;
    $areaId = null;
    if (session()->has('address')) {
        $address = session()->get('address');
        $distance = $address['distance'] ?? 0;
        $areaId = $address['area_id'] ?? null;
    }
    $deliveryCharge = Helpers::get_delivery_charge(branchId: $selectedBranchId, distance: $distance, selectedDeliveryArea: $areaId, orderType: $deliveryServiceType);
    $delivery_charge = $deliveryCharge;
    $productWeightCharge = Helpers::productWeightChargeCalculation(branchId: $selectedBranchId, weight: $productWeight);
} else {
    $deliveryCharge = 0;
    $delivery_charge = 0;
}
@endphp

<div class="box p-3">
    <dl class="row">
        <dt class="col-sm-6">{{ translate('sub_total') }} :</dt>
        <dd class="col-sm-6 text-right">{{ Helpers::set_symbol($subtotal) }}</dd>

        <dt class="col-sm-6">{{ translate('product') }} {{ translate('discount') }}:
        </dt>
        <dd class="col-sm-6 text-right"> - {{ Helpers::set_symbol($discount_on_product) }}</dd>

        <dt class="col-sm-6">{{ translate('extra') }} {{ translate('discount') }}:
        </dt>

        <dd class="col-6 text-right text-info d-flex gap-2 justify-content-end">
            @if (count($cart ?? []) > 0)
                <button id="extra_discount_modal" class="btn btn-sm p-0 text-info" type="button" data-toggle="modal"
                    data-target="#update-extra-discount">
                    <i class="tio-edit"></i>
                </button>
            @endif

            @if ($extraDiscountAmount > 0)
                <button id="extra_discount_delete_btn" class="btn btn-sm p-0 text-danger" type="button"
                    data-toggle="modal" data-target="#delete-extra-discount">
                    <i class="tio-delete"></i>
                </button>
            @endif

            <span>- <span>
                    {{ Helpers::set_symbol($extraDiscountAmount) }}
                </span>
            </span>
        </dd>

        <dt class="col-sm-6">{{ translate('tax') }}
            {{ Helpers::get_business_settings('product_vat_tax_status') === 'included' ? '(Included)' : '' }}
            :</dt>
        <dd class="col-sm-6 text-right">{{ Helpers::set_symbol($totalTax) }}</dd>

        <dt class="col-sm-6">{{ translate('Delivery Charge') }} :</dt>
        <dd class="col-sm-6 text-right">{{ Helpers::set_symbol($delivery_charge) }}</dd>

        @if (session()->get('order_type') == 'home_delivery')
            <dt class="col-sm-6">{{ translate('Charge On Weight') }} :</dt>
            <dd class="col-sm-6 text-right">{{ Helpers::set_symbol($productWeightCharge) }}</dd>
        @endif

        <dt class="col-12">
            <hr class="mt-0">
        </dt>

        @php($totalOrderAmount = $total + Helpers::convertStringAmountToNumber($updatedTotalTax) + $delivery_charge + $productWeightCharge)

        <dt class="col-sm-6">{{ translate('total') }} :</dt>
        <dd class="col-sm-6 text-right h4 b">{{ Helpers::set_symbol($totalOrderAmount) }}</dd>
    </dl>

    <div>
        <form action="{{ route('branch.pos.order') }}" id='order_place' method="post">
            @csrf
            <div class="pos--delivery-man-options mb-3" style="display: {{ session('order_type') == 'home_delivery' ? 'block' : 'none' }};">
                <label class="input-label font-weight-semibold text-dark d-flex align-items-center justify-content-between mb-1">
                    <span><i class="tio-user"></i> {{ translate('Assign Delivery Man') }}</span>
                    <small class="text-muted">({{ translate('optional') }})</small>
                </label>
                <select name="delivery_man_id" id="delivery_man_id" class="form-control js-select2-custom">
                    <option value="">{{ translate('-- Select Delivery Man --') }}</option>
                    @if(isset($deliveryMen))
                        @foreach($deliveryMen as $dm)
                            <option value="{{ $dm->id }}" {{ session('delivery_man_id') == $dm->id ? 'selected' : '' }}>
                                {{ $dm->f_name }} {{ $dm->l_name }} ({{ $dm->phone }})
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="pos--payment-options mt-3 mb-3">
                <h5 class="mb-3">{{ translate('Payment Method') }}</h5>
                <ul class="mb-3 d-flex flex-wrap gap-2 list-unstyled">
                    <li>
                        <label class="btn btn-outline-secondary mb-0 {{ (!session()->has('order_type') || session('order_type') == 'take_away') ? 'active' : '' }}">
                            <input type="radio" class="paid-by" name="type" value="cash" hidden=""
                                {{ (!session()->has('order_type') || session('order_type') == 'take_away') ? 'checked' : '' }}>
                            <span><i class="tio-money-vs"></i> {{ translate('cash') }}</span>
                        </label>
                    </li>
                    <li>
                        <label class="btn btn-outline-secondary mb-0">
                            <input type="radio" class="paid-by" name="type" value="card" hidden="">
                            <span><i class="tio-credit-card"></i> {{ translate('card') }}</span>
                        </label>
                    </li>
                    <li style="display: {{ session('order_type') == 'home_delivery' ? 'block' : 'none' }}">
                        <label class="btn btn-outline-secondary mb-0 {{ session('order_type') == 'home_delivery' ? 'active' : '' }}">
                            <input type="radio" class="paid-by" name="type" value="cash_on_delivery" hidden=""
                                {{ session('order_type') == 'home_delivery' ? 'checked' : '' }}>
                            <span><i class="tio-bike"></i> {{ translate('cash_on_delivery') }}</span>
                        </label>
                    </li>
                    @if($walletStatus)
                    <li style="display: {{ session()->has('customer_id') && session('customer_id') ? 'block' : 'none' }}">
                        @php($hasBalance = isset($customer) && $customer->wallet_balance >= $totalOrderAmount)
                        <label class="btn btn-outline-secondary mb-0 {{ !$hasBalance ? 'opacity-50' : '' }}" {{ !$hasBalance ? 'title=' . translate('Insufficient wallet balance') : '' }}>
                            <input type="radio" class="paid-by" name="type" value="wallet_payment" hidden="" {{ !$hasBalance ? 'disabled' : '' }}>
                            <span>
                                <i class="tio-wallet"></i> {{ translate('wallet') }}
                                @if(isset($customer))
                                    <small class="badge badge-soft-{{ $hasBalance ? 'success' : 'danger' }}">({{ Helpers::set_symbol($customer->wallet_balance) }})</small>
                                @endif
                            </span>
                        </label>
                    </li>
                    @endif
                    @if(isset($offlinePaymentMethods) && count($offlinePaymentMethods) > 0)
                    <li>
                        <label class="btn btn-outline-secondary mb-0">
                            <input type="radio" class="paid-by" name="type" value="offline_payment" hidden="">
                            <span><i class="tio-museum"></i> {{ translate('offline_payment') }}</span>
                        </label>
                    </li>
                    @endif
                </ul>

                @if(isset($offlinePaymentMethods) && count($offlinePaymentMethods) > 0)
                <div class="offline-payment-details border rounded p-3 bg-light mb-3" id="pos_offline_section" style="display: none;">
                    <div class="form-group mb-2">
                        <label class="input-label font-weight-semibold text-dark">{{ translate('Select Payment Method') }} <span class="text-danger">*</span></label>
                        <select name="offline_payment_id" id="offline_payment_id" class="form-control">
                            <option value="">{{ translate('-- Select Method --') }}</option>
                            @foreach($offlinePaymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->method_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label class="input-label font-weight-semibold text-dark">{{ translate('Transaction Reference / Receipt No') }} <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_reference" class="form-control" placeholder="{{ translate('Ex: TXN12345678') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="input-label font-weight-semibold text-dark">{{ translate('Payment Note') }}</label>
                        <textarea name="payment_note" class="form-control" rows="2" placeholder="{{ translate('Payment Note (Optional)') }}"></textarea>
                    </div>
                </div>
                @endif
            </div>

            <div class="collect-cash-section"
                style="display: {{ (session('order_type') != 'home_delivery') ? 'block' : 'none' }}">
                <div class="form-group mb-2 d-flex align-items-center justify-content-between gap-2">
                    <label class="w-50 mb-0">{{ translate('Paid Amount') }} :</label>
                    <input type="number" class="form-control w-50 text-right" name="paid_amount" step="0.01"
                        id="paid-amount" min="0" value="{{ $totalOrderAmount }}"
                        onkeyup="calculateAmountDifference()" required>
                    <input type="hidden" class="hidden-paid-amount" value="{{ $totalOrderAmount }}">
                </div>
                <div class="form-group d-flex align-items-center justify-content-between gap-2">
                    <label class="due-or-change-amount w-50 mb-0">{{ translate('Change Amount') }} :</label>
                    <input type="number" class="form-control text-right w-50 border-0 shadow-none"
                        id="amount-difference" value="0" step="0.01" readonly required>
                </div>
            </div>

            <div class="row button--bottom-fixed g-1 bg-white ">
                <div class="col-sm-6">
                    <a class="btn btn-outline-danger btn--danger btn-sm btn-block cancel-order-button"><i
                            class="fa fa-times-circle "></i> {{ translate('Cancel Order') }} </a>
                </div>

                <div class="col-sm-6">
                    <span id="place-order-tooltip-wrapper">
                        <button type="submit" class="btn btn--primary btn-sm btn-block order-place-btn">
                            {{ translate('Place Order') }}
                        </button>
                    </span>
                </div>
            </div>
</form>
    </div>
</div>

<div class="modal fade" id="update-extra-discount" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ translate('Extra_Discount') }}</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="update_extra_discount_form">
                    @csrf
                    <div class="bg-light rounded p-3">
                        <label for="">{{ translate('discount') }}</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="discount" id="extra_discount_input"
                                   value="{{ $extraDiscount ?? 0 }}" min="0"
                                step="any" placeholder="{{ translate('Ex: 45') }}">

                            <div class="input-group-append bg-light">
                                <select name="type" class="form-control input-group-text"
                                    id="discount_type_select">
                                    <option value="amount" {{ $extraDiscountType == 'amount' ? 'selected' : '' }}>
                                        {{ translate('amount') }}
                                        ({{ Helpers::currency_symbol() }})
                                    </option>
                                    <option value="percent" {{ $extraDiscountType == 'percent' ? 'selected' : '' }}>
                                        {{ translate('percent') }}(%)
                                    </option>
                                </select>
                            </div>

                        </div>
                        <small id="extra_discount_error"
                            class="d-flex justify-content-end fs-12px text-danger"></small>
                    </div>

                    <div class="btn--container justify-content-end mt-3">
                        <button class="btn btn-sm btn--reset" type="reset">{{ translate('reset') }}</button>
                        <button id="extra_discount_update_btn" class="btn btn-sm btn--primary"
                            type="button">{{ translate('submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="delete-extra-discount" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-mxwidth">
        <div class="modal-content shadow-sm pb-sm-3">
            <div class="modal-header p-0">
                <button type="button"
                    class="close w-35 h-35 rounded-circle d-flex align-items-center justify-content-center bg-light position-relative"
                    data-dismiss="modal" aria-label="Close" style="top: 10px; inset-inline-end: 10px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img src="{{ asset('public/assets/admin/img/delete-warning.png') }}" alt="" class="mb-3">
                <h3 class="mb-2">{{ translate('Remove Discount') }}?</h3>
                <p class="m-0">{{ 'Are you sure you want to remove this discount' }}?</p>
            </div>
            <div class="modal-footer justify-content-center border-0 gap-2">
                <button type="button" class="btn min-w-120 btn--reset" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn min-w-120 btn-danger" id="confirm-delete-discount">
                    {{ translate('Delete') }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add-tax" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('update_tax') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('branch.pos.tax') }}" method="POST" class="row">
                    @csrf
                    <div class="form-group col-12">
                        <label for="">{{ translate('tax') }} (%)</label>
                        <input type="number" class="form-control" name="tax" min="0">
                    </div>

                    <div class="col-sm-12">
                        <div class="btn--container">
                            <button class="btn btn-sm btn--reset" type="reset">{{ translate('reset') }}</button>
                            <button class="btn btn-sm btn--primary" type="submit">{{ translate('submit') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function calculateAmountDifference() {
        let paidAmount = parseFloat($('#paid-amount').val());

        if (isNaN(paidAmount) || paidAmount < 0) {
            paidAmount = 0;
            $('#paid-amount').val('0');
        }

        let orderAmount = {{ $totalOrderAmount }};
        let difference = paidAmount - orderAmount;

        let label = $('.due-or-change-amount');
        let differenceInput = $('#amount-difference');
        let placeOrderButton = $('.order-place-btn');
        let tooltipWrapper = $('#place-order-tooltip-wrapper');

        if (paidAmount >= orderAmount) {
            label.text('{{ translate('Change Amount') }} :');
            differenceInput.val(difference.toFixed(2));
            placeOrderButton.prop('disabled', false);
            // Remove tooltip
            tooltipWrapper.removeAttr('data-original-title')
                .removeAttr('title')
                .tooltip('dispose');

        } else {
            label.text('{{ translate('Due Amount') }} :');
            differenceInput.val(difference.toFixed(2));
            placeOrderButton.prop('disabled', true);

            // Set tooltip
            tooltipWrapper.attr('title', '{{ translate('Paid amount must be equal or greater than total amount') }}')
                .tooltip('dispose')
                .tooltip();
        }
    }

    $(document).ready(function() {
        calculateAmountDifference();
    });

    // Update paid-by radio button handler
    $('.paid-by').change(function() {
        var selectedPaymentOption = $(this).val();
        var totalOrderAmount = $('.hidden-paid-amount').val();

        $('.paid-by').closest('label').removeClass('active');
        $(this).closest('label').addClass('active');

        if (selectedPaymentOption == 'cash') {
            $('.collect-cash-section').show();
            $('#pos_offline_section').hide();
            $('#paid-amount').removeAttr('readonly').removeClass('bg-F5F5F5');
            $('#paid-amount').attr('required', true);
            calculateAmountDifference();
        } else if (selectedPaymentOption == 'card') {
            $('.collect-cash-section').show();
            $('#pos_offline_section').hide();
            $('#paid-amount').attr('readonly', true).addClass('bg-F5F5F5');
            $('#paid-amount').val(totalOrderAmount);
            $('#paid-amount').attr('required', true);
            calculateAmountDifference();
        } else if (selectedPaymentOption == 'offline_payment') {
            $('.collect-cash-section').hide();
            $('#pos_offline_section').show();
            $('#paid-amount').removeAttr('required');
        } else {
            // cash_on_delivery or wallet_payment
            $('.collect-cash-section').hide();
            $('#pos_offline_section').hide();
            $('#paid-amount').removeAttr('required');
        }
    });

    $('#delivery_man_id').change(function() {
        var dmId = $(this).val();
        if (typeof store_key === 'function') {
            store_key('delivery_man_id', dmId);
        }
    });
</script>
