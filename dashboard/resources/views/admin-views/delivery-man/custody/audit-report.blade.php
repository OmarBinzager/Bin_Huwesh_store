@extends('layouts.admin.app')

@section('title', translate('Settlements & Financial Audit Log'))

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
                        <i class="tio-chart-bar-4 text-primary"></i>
                    </span>
                    <span>{{ translate('Settlements & Financial Audit Log') }}</span>
                    <span class="badge badge-soft-primary badge-pill ml-2">{{ $transactions->total() }}</span>
                </h1>
                <p class="text-muted mb-0">{{ translate('Comprehensive financial audit of cash collections, expected vs actually received custody, and remaining differences.') }}</p>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                <a href="{{ route('admin.delivery-man.custody.index') }}" class="btn btn-outline-secondary">
                    <i class="tio-chevron-left mr-1"></i> {{ translate('Back to Custody Wallets') }}
                </a>
                <a href="{{ route('admin.delivery-man.custody.export-audit-report', request()->all()) }}" class="btn btn-outline-primary">
                    <i class="tio-download-to mr-1"></i> {{ translate('Export Audit Excel') }}
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="js-nav-scroller hs-nav-scroller-horizontal mt-3">
            <ul class="nav nav-tabs page-header-tabs">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.delivery-man.custody.index') }}">
                        <i class="tio-user-big mr-1"></i> {{ translate('Delivery Men Custody Overview') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('admin.delivery-man.custody.audit-report') }}">
                        <i class="tio-receipt-outlined mr-1"></i> {{ translate('Settlements & Discrepancies Audit Log') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Financial Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card kpi-card h-100 bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted font-weight-bold font-size-sm">{{ translate('COD Collections in Period') }}</span>
                        <span class="badge badge-soft-primary badge-pill p-2"><i class="tio-receipt font-size-lg"></i></span>
                    </div>
                    <h2 class="text-primary font-weight-bold mb-1">{{ \App\CentralLogics\Helpers::set_symbol($filteredCollectedCash) }}</h2>
                    <span class="text-muted font-size-xs">
                        {{ translate('Orders count:') }} <strong class="text-dark">{{ $filteredCollectedOrdersCount }}</strong>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card kpi-card h-100 bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted font-weight-bold font-size-sm">{{ translate('Actually Received by Admin') }}</span>
                        <span class="badge badge-soft-success badge-pill p-2"><i class="tio-checkmark-circle font-size-lg"></i></span>
                    </div>
                    <h2 class="text-success font-weight-bold mb-1">{{ \App\CentralLogics\Helpers::set_symbol($filteredSettledCash) }}</h2>
                    <span class="text-muted font-size-xs">{{ translate('Total amounts confirmed and received into treasury') }}</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card kpi-card h-100 bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted font-weight-bold font-size-sm">{{ translate('Expected Custody (Before)') }}</span>
                        <span class="badge badge-soft-info badge-pill p-2"><i class="tio-time font-size-lg"></i></span>
                    </div>
                    <h2 class="text-info font-weight-bold mb-1">{{ \App\CentralLogics\Helpers::set_symbol($filteredExpectedCustody) }}</h2>
                    <span class="text-muted font-size-xs">{{ translate('Total custody expected before settlement receipts') }}</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card kpi-card h-100 bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted font-weight-bold font-size-sm">{{ translate('Total Remaining in Field') }}</span>
                        <span class="badge badge-soft-danger badge-pill p-2"><i class="tio-money-vs font-size-lg"></i></span>
                    </div>
                    <h2 class="text-danger font-weight-bold mb-1">{{ \App\CentralLogics\Helpers::set_symbol($totalCashInHand) }}</h2>
                    <span class="text-muted font-size-xs">{{ translate('Current outstanding custody across all delivery men') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form action="{{ url()->current() }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="input-label font-size-sm font-weight-bold">{{ translate('Period / Date Range') }}</label>
                        <select name="date_type" id="date_type" class="form-control form-control-sm custom-select">
                            <option value="all" {{ $dateType == 'all' ? 'selected' : '' }}>{{ translate('All Time') }}</option>
                            <option value="today" {{ $dateType == 'today' ? 'selected' : '' }}>{{ translate('today') }}</option>
                            <option value="this_week" {{ $dateType == 'this_week' ? 'selected' : '' }}>{{ translate('This Week') }}</option>
                            <option value="this_month" {{ $dateType == 'this_month' ? 'selected' : '' }}>{{ translate('This Month') }}</option>
                            <option value="custom" {{ $dateType == 'custom' ? 'selected' : '' }}>{{ translate('Custom Range') }}</option>
                        </select>
                    </div>

                    <div class="col-md-2 custom-date-range {{ $dateType == 'custom' ? '' : 'd-none' }}">
                        <label class="input-label font-size-sm">{{ translate('Start Date') }}</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                    </div>

                    <div class="col-md-2 custom-date-range {{ $dateType == 'custom' ? '' : 'd-none' }}">
                        <label class="input-label font-size-sm">{{ translate('End Date') }}</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                    </div>

                    <div class="col-md-2">
                        <label class="input-label font-size-sm font-weight-bold">{{ translate('deliveryman') }}</label>
                        <select name="delivery_man_id" class="form-control form-control-sm custom-select">
                            <option value="all">{{ translate('All Delivery Men') }}</option>
                            @foreach($deliverymen as $dm)
                                <option value="{{ $dm->id }}" {{ $deliveryManId == $dm->id ? 'selected' : '' }}>
                                    {{ $dm->f_name }} {{ $dm->l_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="input-label font-size-sm font-weight-bold">{{ translate('Receiving Accountant') }}</label>
                        <select name="admin_id" class="form-control form-control-sm custom-select">
                            <option value="all">{{ translate('All Accountants') }}</option>
                            @foreach($admins as $adm)
                                <option value="{{ $adm->id }}" {{ $adminId == $adm->id ? 'selected' : '' }}>
                                    {{ $adm->f_name }} {{ $adm->l_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="input-label font-size-sm font-weight-bold">{{ translate('Transaction Type') }}</label>
                        <select name="transaction_type" class="form-control form-control-sm custom-select">
                            <option value="all" {{ $transactionType == 'all' ? 'selected' : '' }}>{{ translate('All Transactions') }}</option>
                            <option value="custody_settlement" {{ $transactionType == 'custody_settlement' ? 'selected' : '' }}>{{ translate('Custody Settlements Only') }}</option>
                            <option value="cash_collection" {{ $transactionType == 'cash_collection' ? 'selected' : '' }}>{{ translate('Cash Collections Only') }}</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="tio-filter-list mr-1"></i> {{ translate('filter') }}
                        </button>
                        <a href="{{ route('admin.delivery-man.custody.audit-report') }}" class="btn btn-sm btn-outline-secondary">
                            {{ translate('reset') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Audit Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header border-0 pb-0">
            <h5 class="card-title font-weight-bold">
                <i class="tio-history mr-1"></i> {{ translate('Financial Transactions & Settlement Discrepancies Log') }}
            </h5>
        </div>

        <div class="card-body p-0 mt-3">
            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('#') }}</th>
                            <th>{{ translate('Transaction ID') }}</th>
                            <th>{{ translate('deliveryman') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Order / Reference') }}</th>
                            <th>{{ translate('Customer / Receiving Accountant') }}</th>
                            <th class="text-right">{{ translate('Expected (Before)') }}</th>
                            <th class="text-right">{{ translate('Actually Received / Collected') }}</th>
                            <th class="text-right">{{ translate('Remaining Difference (After)') }}</th>
                            <th class="text-center">{{ translate('date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $key => $tx)
                            @php
                                $isCollection = ($tx->transaction_type === 'cash_collection');
                                $dm = $tx->deliveryMan;
                            @endphp
                            <tr>
                                <td>{{ $transactions->firstItem() + $key }}</td>
                                <td>
                                    <span class="font-weight-bold text-dark font-size-sm" dir="ltr">{{ $tx->transaction_id }}</span>
                                </td>
                                <td>
                                    @if($dm)
                                        <a href="{{ route('admin.delivery-man.custody.details', $dm->id) }}" class="font-weight-bold text-dark">
                                            {{ $dm->f_name }} {{ $dm->l_name }}
                                        </a>
                                        <small class="text-muted d-block" dir="ltr">{{ $dm->phone }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isCollection)
                                        <span class="badge badge-soft-success py-1 px-2 font-weight-bold">
                                            <i class="tio-arrow-downward mr-1"></i> {{ translate('Cash Collection') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-info py-1 px-2 font-weight-bold">
                                            <i class="tio-arrow-upward mr-1"></i> {{ translate('Custody Settlement') }}
                                        </span>
                                        @if($tx->settlement_type)
                                            <span class="badge {{ $tx->settlement_type === 'full' ? 'badge-soft-success' : 'badge-soft-warning' }} d-block mt-1 font-size-xs">
                                                {{ $tx->settlement_type === 'full' ? translate('Full Settlement') : translate('Partial Settlement') }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($tx->order_id)
                                        <a href="{{ route('admin.orders.details', ['id' => $tx->order_id]) }}" class="font-weight-bold text-primary">
                                            #{{ $tx->order_id }}
                                        </a>
                                    @elseif($tx->reference)
                                        <span class="badge badge-soft-secondary font-weight-bold">{{ $tx->reference }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isCollection)
                                        <div>
                                            <span class="font-weight-bold text-dark d-block">
                                                <i class="tio-user-outlined mr-1"></i> {{ $tx->customer_name ?: translate('Customer') }}
                                            </span>
                                            @if($tx->customer_phone)
                                                <small class="text-muted" dir="ltr">{{ $tx->customer_phone }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <div>
                                            <span class="font-weight-bold text-primary d-block">
                                                <i class="tio-user-big mr-1"></i> {{ translate('Accountant:') }} {{ $tx->admin ? ($tx->admin->f_name . ' ' . $tx->admin->l_name) : translate('Admin Accountant') }}
                                            </span>
                                            <small class="badge badge-soft-secondary">{{ $tx->payment_method ?? 'cash' }}</small>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-right text-muted font-weight-semibold">
                                    {{ \App\CentralLogics\Helpers::set_symbol($tx->balance_before) }}
                                </td>
                                <td class="text-right font-weight-bold {{ $isCollection ? 'text-success' : 'text-danger' }}">
                                    {{ $isCollection ? '+' : '-' }} {{ \App\CentralLogics\Helpers::set_symbol($tx->amount) }}
                                </td>
                                <td class="text-right font-weight-bold text-dark font-size-md">
                                    {{ \App\CentralLogics\Helpers::set_symbol($tx->balance_after) }}
                                </td>
                                <td class="text-center font-size-xs text-muted">
                                    {{ $tx->created_at->format('Y-m-d h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center p-4">
                                    <p class="mb-0 text-muted">{{ translate('No audit transactions found matching the filter.') }}</p>
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
                        {{ translate('showing') }} {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} {{ translate('of') }} {{ $transactions->total() }}
                    </div>
                </div>
                <div class="col-sm-auto">
                    {!! $transactions->links() !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    $('#date_type').on('change', function() {
        if ($(this).val() === 'custom') {
            $('.custom-date-range').removeClass('d-none');
        } else {
            $('.custom-date-range').addClass('d-none');
        }
    });
</script>
@endpush
