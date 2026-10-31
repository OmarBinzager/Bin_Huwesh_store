@extends('layouts.admin.app')

@section('title', translate('Delivery Men Wallets & Cash Custody'))

@push('css_or_js')
    <style>
        .badge-soft-danger {
            background-color: rgba(237, 76, 120, .1);
            color: #ed4c78;
        }
        .badge-soft-warning {
            background-color: rgba(245, 202, 153, .25);
            color: #f5a623;
        }
        .badge-soft-success {
            background-color: rgba(0, 201, 167, .1);
            color: #00c9a7;
        }
        .badge-soft-primary {
            background-color: rgba(55, 125, 255, .1);
            color: #377dff;
        }
        .badge-soft-info {
            background-color: rgba(0, 201, 219, .1);
            color: #00c9db;
        }
        .kpi-card {
            border-radius: 12px;
            transition: all 0.25s ease-in-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid #edf2f7;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #377dff;
            color: #377dff !important;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header pb-2">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center gap-2">
                    <span class="page-header-icon">
                        <i class="tio-wallet text-primary"></i>
                    </span>
                    <span>{{ translate('Delivery Men Wallets & Cash Custody') }}</span>
                    <span class="badge badge-soft-primary badge-pill ml-2">{{ $deliverymen->total() }}</span>
                </h1>
                <p class="text-muted mb-0">{{ translate('Track cash custody, COD collections, and admin settlements in real-time.') }}</p>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                <a href="{{ route('admin.delivery-man.custody.audit-report') }}" class="btn btn-outline-info">
                    <i class="tio-chart-bar-4 mr-1"></i> {{ translate('Settlements & Financial Audit') }}
                </a>
                <a href="{{ route('admin.delivery-man.custody.export', request()->all()) }}" class="btn btn-outline-primary">
                    <i class="tio-download-to mr-1"></i> {{ translate('Export Excel Sheet') }}
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="js-nav-scroller hs-nav-scroller-horizontal mt-3">
            <ul class="nav nav-tabs page-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('admin.delivery-man.custody.index') }}">
                        <i class="tio-user-big mr-1"></i> {{ translate('Delivery Men Custody Overview') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.delivery-man.custody.audit-report') }}">
                        <i class="tio-receipt-outlined mr-1"></i> {{ translate('Settlements & Discrepancies Audit Log') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- KPIs Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card kpi-card h-100 bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted font-weight-bold font-size-sm">{{ translate('Total Custody in Field') }}</span>
                        <span class="badge badge-soft-danger badge-pill p-2"><i class="tio-money-vs font-size-lg"></i></span>
                    </div>
                    <h2 class="text-danger font-weight-bold mb-1">{{ \App\CentralLogics\Helpers::set_symbol($totalCashInHand) }}</h2>
                    <span class="text-muted font-size-xs">{{ translate('Cash remaining with delivery men') }}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card kpi-card h-100 bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted font-weight-bold font-size-sm">{{ translate('Total Collected Cash') }}</span>
                        <span class="badge badge-soft-primary badge-pill p-2"><i class="tio-receipt font-size-lg"></i></span>
                    </div>
                    <h2 class="text-primary font-weight-bold mb-1">{{ \App\CentralLogics\Helpers::set_symbol($totalCollectedCash) }}</h2>
                    <span class="text-muted font-size-xs">
                        {{ translate('Today:') }} {{ \App\CentralLogics\Helpers::set_symbol($todayCollectedCash) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card kpi-card h-100 bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted font-weight-bold font-size-sm">{{ translate('Settled with Administration') }}</span>
                        <span class="badge badge-soft-success badge-pill p-2"><i class="tio-checkmark-circle font-size-lg"></i></span>
                    </div>
                    <h2 class="text-success font-weight-bold mb-1">{{ \App\CentralLogics\Helpers::set_symbol($totalSettledAmount) }}</h2>
                    <span class="text-muted font-size-xs">
                        {{ translate('Today:') }} {{ \App\CentralLogics\Helpers::set_symbol($todaySettledCash) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card kpi-card h-100 bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted font-weight-bold font-size-sm">{{ translate('Collected Orders Count') }}</span>
                        <span class="badge badge-soft-warning badge-pill p-2"><i class="tio-shopping-basket font-size-lg"></i></span>
                    </div>
                    <h2 class="text-dark font-weight-bold mb-1">{{ $totalCollectedOrdersCount }}</h2>
                    <span class="text-muted font-size-xs">
                        {{ translate('Pending delivery men:') }} <strong class="text-danger">{{ $pendingDeliveryMenCount }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & List Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header border-0 pb-0">
            <div class="row align-items-center g-2 w-100">
                <div class="col-md-4">
                    <form action="{{ url()->current() }}" method="GET">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <div class="input-group input-group-merge input-group-flush">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><i class="tio-search"></i></div>
                            </div>
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                placeholder="{{ translate('Search by Delivery Man Name or Phone...') }}"
                                value="{{ $search }}" aria-label="Search">
                            <button type="submit" class="btn btn-primary">{{ translate('search') }}</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-8 d-flex justify-content-md-end align-items-center flex-wrap gap-2">
                    <!-- Branch Filter -->
                    <form action="{{ url()->current() }}" method="GET" class="d-inline-block">
                        <input type="hidden" name="search" value="{{ $search }}">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <select name="branch_id" class="form-control form-control-sm custom-select" onchange="this.form.submit()">
                            <option value="all" {{ $branchId == 'all' ? 'selected' : '' }}>{{ translate('All Branches') }}</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </form>

                    <!-- Status Filter Tabs -->
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}"
                            class="btn {{ $status == 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ translate('All') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}"
                            class="btn {{ $status == 'pending' ? 'btn-danger' : 'btn-outline-secondary' }}">
                            {{ translate('Pending') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'partially_settled']) }}"
                            class="btn {{ $status == 'partially_settled' ? 'btn-warning' : 'btn-outline-secondary' }}">
                            {{ translate('Partially Settled') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'settled']) }}"
                            class="btn {{ $status == 'settled' ? 'btn-success' : 'btn-outline-secondary' }}">
                            {{ translate('Fully Settled') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0 mt-3">
            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('#') }}</th>
                            <th>{{ translate('deliveryman') }}</th>
                            <th>{{ translate('branch') }}</th>
                            <th class="text-center">{{ translate('Cash Orders & Customers') }}</th>
                            <th class="text-right">{{ translate('Total Collected') }}</th>
                            <th class="text-right">{{ translate('Settled to Admin') }}</th>
                            <th class="text-right">{{ translate('Remaining Custody') }}</th>
                            <th class="text-center">{{ translate('Settlement Status') }}</th>
                            <th class="text-center">{{ translate('action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliverymen as $key => $dm)
                            @php
                                $wallet = $dm->wallet;
                                $cashInHand = $wallet ? (float)$wallet->cash_in_hand : 0.00;
                                $totalCollected = $wallet ? (float)$wallet->total_collected_cash : 0.00;
                                $totalSettled = $wallet ? (float)$wallet->total_settled_amount : 0.00;
                                $ordersCount = $wallet ? (int)$wallet->collected_orders_count : 0;
                                $uniqueCustomers = $dm->custodyTransactions->pluck('customer_name')->filter(fn($n) => !empty($n) && $n !== 'عميل')->unique();
                            @endphp
                            <tr>
                                <td>{{ $deliverymen->firstItem() + $key }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img class="avatar avatar-circle" src="{{ $dm->image_full_path }}"
                                            alt="{{ $dm->f_name }}" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <a href="{{ route('admin.delivery-man.custody.details', $dm->id) }}" class="font-weight-bold text-dark d-block">
                                                {{ $dm->f_name . ' ' . $dm->l_name }}
                                            </a>
                                            <span class="font-size-sm text-muted d-block" dir="ltr">{{ $dm->phone }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-info">{{ $dm->branch ? $dm->branch->name : translate('Main') }}</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-soft-primary px-2 py-1 font-weight-bold mb-1"
                                        onclick="showDmCashOrdersModal('{{ $dm->id }}', '{{ addslashes($dm->f_name . ' ' . $dm->l_name) }}')"
                                        title="{{ translate('View all collected orders and customer names') }}">
                                        <i class="tio-receipt mr-1"></i> {{ $ordersCount }} {{ translate('orders') }}
                                    </button>
                                    @if($uniqueCustomers->isNotEmpty())
                                        <div class="font-size-xs text-muted text-center" style="max-width: 170px; white-space: normal; line-height: 1.2;">
                                            <i class="tio-user-outlined mr-1"></i>{{ $uniqueCustomers->take(2)->implode('، ') }}
                                            @if($uniqueCustomers->count() > 2)
                                                <span class="text-primary font-weight-bold">+{{ $uniqueCustomers->count() - 2 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-right font-weight-semibold text-primary">
                                    {{ \App\CentralLogics\Helpers::set_symbol($totalCollected) }}
                                </td>
                                <td class="text-right font-weight-semibold text-success">
                                    {{ \App\CentralLogics\Helpers::set_symbol($totalSettled) }}
                                </td>
                                <td class="text-right">
                                    @if($cashInHand > 0)
                                        <span class="font-weight-bold text-danger font-size-16">
                                            {{ \App\CentralLogics\Helpers::set_symbol($cashInHand) }}
                                        </span>
                                    @else
                                        <span class="font-weight-bold text-muted">0.00</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($cashInHand <= 0)
                                        <span class="badge badge-soft-success py-1 px-2 font-weight-bold">
                                            <i class="tio-checkmark-circle mr-1"></i> {{ translate('Fully Settled') }}
                                        </span>
                                    @elseif($totalSettled > 0 && $cashInHand > 0)
                                        <span class="badge badge-soft-warning py-1 px-2 font-weight-bold">
                                            <i class="tio-time mr-1"></i> {{ translate('Partially Settled') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger py-1 px-2 font-weight-bold">
                                            <i class="tio-warning mr-1"></i> {{ translate('Pending') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @if($cashInHand > 0)
                                            <button type="button" class="btn btn-sm btn-primary"
                                                onclick="openSettlementModal('{{ $dm->id }}', '{{ addslashes($dm->f_name . ' ' . $dm->l_name) }}', '{{ $cashInHand }}')"
                                                title="{{ translate('Confirm Cash Custody Settlement') }}">
                                                <i class="tio-receipt mr-1"></i> {{ translate('Settle Custody') }}
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.delivery-man.custody.details', $dm->id) }}"
                                            class="btn btn-sm btn-outline-info" title="{{ translate('Account Statement & Transactions') }}">
                                            <i class="tio-visible"></i> {{ translate('Account Statement') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center p-4">
                                    <img class="mb-3" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="Image Description" style="width: 7rem;">
                                    <p class="mb-0 text-muted">{{ translate('No data found matching your search') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer border-0">
            <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                <div class="col-sm-auto mb-2 mb-sm-0">
                    <div class="text-muted font-size-sm">
                        {{ translate('showing') }} {{ $deliverymen->firstItem() ?? 0 }} - {{ $deliverymen->lastItem() ?? 0 }} {{ translate('of') }} {{ $deliverymen->total() }}
                    </div>
                </div>
                <div class="col-sm-auto">
                    {!! $deliverymen->links() !!}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تفاصيل الطلبات المحصلة مع أسماء العملاء -->
<div class="modal fade" id="dmCashOrdersModal" tabindex="-1" role="dialog" aria-labelledby="dmCashOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold d-flex align-items-center gap-2" id="dmCashOrdersModalLabel">
                    <i class="tio-shopping-basket text-primary"></i>
                    <span>{{ translate('Cash Collected Orders & Customers') }} - <span id="orders_modal_dm_name" class="text-primary"></span></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center p-2 mb-3 bg-soft-primary rounded">
                    <div>
                        <span class="text-muted font-size-sm">{{ translate('Total Cash Orders:') }}</span>
                        <strong id="orders_modal_count" class="text-dark ml-1 font-size-md">0</strong>
                    </div>
                    <div>
                        <span class="text-muted font-size-sm">{{ translate('Total Amount:') }}</span>
                        <strong id="orders_modal_total" class="text-success ml-1 font-size-md">0.00</strong>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('order_id') }}</th>
                                <th>{{ translate('customer_name') }}</th>
                                <th>{{ translate('customer_phone') }}</th>
                                <th class="text-right">{{ translate('amount') }}</th>
                                <th class="text-right">{{ translate('Balance After') }}</th>
                                <th class="text-center">{{ translate('date') }}</th>
                            </tr>
                        </thead>
                        <tbody id="orders_modal_table_body">
                            <tr>
                                <td colspan="6" class="text-center p-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light p-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ translate('close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal تأكيد استلام العهدة وتسويتها -->
<div class="modal fade" id="settlementModal" tabindex="-1" role="dialog" aria-labelledby="settlementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.delivery-man.custody.settle') }}" method="POST">
                @csrf
                <input type="hidden" name="delivery_man_id" id="modal_delivery_man_id">

                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold d-flex align-items-center gap-2" id="settlementModalLabel">
                        <i class="tio-receipt text-primary"></i>
                        <span>{{ translate('Confirm Cash Custody Settlement') }}</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-soft-info mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>{{ translate('Delivery Man:') }} <strong id="modal_dm_name" class="text-dark"></strong></span>
                            <span>{{ translate('Date & Time:') }} <strong class="text-dark">{{ date('Y-m-d H:i') }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="tio-user-big mr-1"></i> {{ translate('Receiving Accountant:') }} <strong class="text-primary">{{ auth('admin')->user()->f_name }} {{ auth('admin')->user()->l_name }}</strong></span>
                        </div>
                    </div>

                    <div class="card bg-soft-danger border-danger mb-3">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="font-size-sm text-muted d-block">{{ translate('Current Due Cash Custody (Expected)') }}</span>
                                <h3 class="mb-0 text-danger font-weight-bold" id="modal_cash_in_hand_display">0.00</h3>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btn_fill_full_amount">
                                <i class="tio-money-vs mr-1"></i> {{ translate('Collect Full Amount') }}
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="input-label font-weight-bold" for="modal_amount">
                            {{ translate('Amount Actually Received') }} <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" name="amount" id="modal_amount"
                                class="form-control form-control-lg font-weight-bold text-primary" required
                                placeholder="0.00">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 px-1 font-size-sm">
                            <span class="text-muted">{{ translate('Remaining Balance (Difference):') }}</span>
                            <strong id="modal_remaining_display" class="text-danger font-weight-bold">0.00</strong>
                        </div>
                        <small class="text-muted d-block mt-1">{{ translate('The system supports partial settlement while retaining the remaining balance automatically.') }}</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="input-label font-weight-bold">{{ translate('Receiving Method') }}</label>
                            <select name="payment_method" class="form-control custom-select">
                                <option value="cash">{{ translate('Cash (Treasury)') }}</option>
                                <option value="bank_transfer">{{ translate('Bank Transfer / Banking App') }}</option>
                                <option value="other">{{ translate('other') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="input-label font-weight-bold">{{ translate('Receipt Voucher / Reference No') }}</label>
                            <input type="text" name="reference" class="form-control" placeholder="{{ translate('e.g. REC-9842') }}">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="input-label font-weight-bold">{{ translate('Accountant / Admin Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ translate('Any notes on the settlement...') }}"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="tio-checkmark-circle mr-1"></i> {{ translate('Confirm Custody Receipt & Deduct') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    let currentMaxCash = 0;

    function openSettlementModal(dmId, dmName, cashInHand) {
        currentMaxCash = parseFloat(cashInHand);
        $('#modal_delivery_man_id').val(dmId);
        $('#modal_dm_name').text(dmName);
        $('#modal_cash_in_hand_display').text(currentMaxCash.toFixed(2));
        $('#modal_amount').val(currentMaxCash.toFixed(2));
        $('#modal_amount').attr('max', currentMaxCash);
        updateRemainingDisplay();
        $('#settlementModal').modal('show');
    }

    function updateRemainingDisplay() {
        let entered = parseFloat($('#modal_amount').val()) || 0;
        let diff = currentMaxCash - entered;
        if (diff < 0) diff = 0;
        $('#modal_remaining_display').text(diff.toFixed(2));
        if (diff === 0) {
            $('#modal_remaining_display').removeClass('text-danger').addClass('text-success').text('0.00 (' + '{{ translate('Fully Settled') }}' + ')');
        } else {
            $('#modal_remaining_display').removeClass('text-success').addClass('text-danger');
        }
    }

    $('#modal_amount').on('input change keyup', updateRemainingDisplay);

    $('#btn_fill_full_amount').on('click', function() {
        $('#modal_amount').val(currentMaxCash.toFixed(2));
        updateRemainingDisplay();
    });

    function showDmCashOrdersModal(dmId, dmName) {
        $('#orders_modal_dm_name').text(dmName);
        $('#orders_modal_table_body').html('<tr><td colspan="6" class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>');
        $('#dmCashOrdersModal').modal('show');

        $.ajax({
            url: "{{ url('/admin/delivery-man/custody/dm-cash-orders') }}/" + dmId,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    $('#orders_modal_count').text(res.total_orders);
                    $('#orders_modal_total').text(res.total_amount_formatted);

                    let rows = '';
                    if (res.orders && res.orders.length > 0) {
                        res.orders.forEach(function(item) {
                            rows += '<tr>' +
                                '<td><a href="' + (item.order_url ? item.order_url : '#') + '" class="font-weight-bold text-primary" target="_blank">#' + item.order_id + '</a></td>' +
                                '<td><strong class="text-dark">' + item.customer_name + '</strong></td>' +
                                '<td dir="ltr">' + item.customer_phone + '</td>' +
                                '<td class="text-right text-success font-weight-bold">+' + item.amount_formatted + '</td>' +
                                '<td class="text-right text-muted">' + item.balance_after_formatted + '</td>' +
                                '<td class="text-center font-size-xs text-muted">' + item.date + '</td>' +
                            '</tr>';
                        });
                    } else {
                        rows = '<tr><td colspan="6" class="text-center text-muted p-3">{{ translate("No cash orders found") }}</td></tr>';
                    }
                    $('#orders_modal_table_body').html(rows);
                }
            },
            error: function() {
                $('#orders_modal_table_body').html('<tr><td colspan="6" class="text-center text-danger p-3">{{ translate("Failed to load orders") }}</td></tr>');
            }
        });
    }
</script>
@endpush
