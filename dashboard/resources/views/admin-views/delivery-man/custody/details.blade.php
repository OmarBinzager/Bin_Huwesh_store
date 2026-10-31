@extends('layouts.admin.app')

@section('title', translate('Cash Custody Statement - ') . $deliveryman->f_name . ' ' . $deliveryman->l_name)

@push('css_or_js')
    <style>
        .badge-soft-danger { background-color: rgba(237, 76, 120, .1); color: #ed4c78; }
        .badge-soft-success { background-color: rgba(0, 201, 167, .1); color: #00c9a7; }
        .badge-soft-warning { background-color: rgba(245, 202, 153, .25); color: #f5a623; }
        .badge-soft-info { background-color: rgba(0, 201, 219, .1); color: #00c9db; }

        .d-none-screen {
            display: none;
        }

        /* Dedicated Print Stylesheet */
        @media print {
            /* Hide non-printable layout chrome */
            #headerMain,
            #sidebarMain,
            #header,
            aside,
            .js-navbar-vertical-aside,
            .navbar-vertical-aside,
            .footer,
            footer,
            .breadcrumb,
            .page-header nav,
            .page-header .btn,
            .page-header .btn-group,
            .btn,
            .btn-group,
            .modal,
            .card-footer,
            .card-header .btn-group,
            .d-print-none,
            #popup-modal,
            #loading {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-family: 'Segoe UI', Tahoma, Arial, sans-serif !important;
                font-size: 12px !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Reset main wrapper offsets across RTL and LTR */
            #content,
            .main,
            .content,
            .container-fluid {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                position: static !important;
            }

            /* Show elements intended strictly for print */
            .d-none-screen {
                display: block !important;
            }

            .print-header {
                display: block !important;
                border-bottom: 2px solid #222 !important;
                padding-bottom: 12px !important;
                margin-bottom: 20px !important;
            }

            .page-header {
                display: none !important;
            }

            /* Clean printable cards */
            .card {
                border: 1px solid #777 !important;
                box-shadow: none !important;
                margin-bottom: 15px !important;
                break-inside: auto !important;
            }

            .card-header {
                border-bottom: 1px solid #777 !important;
                background-color: #f7f7f7 !important;
                padding: 10px 14px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-responsive {
                overflow: visible !important;
            }

            table.card-table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            table.card-table th,
            table.card-table td {
                border: 1px solid #666 !important;
                padding: 6px 8px !important;
                font-size: 11px !important;
                color: #000 !important;
            }

            table.card-table thead th {
                background-color: #ececec !important;
                color: #000 !important;
                font-weight: bold !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge {
                border: 1px solid #555 !important;
                color: #000 !important;
                background: transparent !important;
                font-weight: 600 !important;
                padding: 2px 5px !important;
            }

            /* Signature endorsement block */
            .print-signatures {
                display: flex !important;
                justify-content: space-between !important;
                margin-top: 40px !important;
                padding-top: 15px !important;
                page-break-inside: avoid !important;
            }

            .print-sig-col {
                width: 30% !important;
                text-align: center !important;
                border-top: 1px dashed #444 !important;
                padding-top: 8px !important;
                font-weight: bold !important;
                font-size: 12px !important;
            }

            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">

    @php($logo = \App\Model\BusinessSetting::where(['key' => 'logo'])->first()?->value)
    @php($restaurant_name = \App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()?->value ?? config('app.name'))
    @php($phone = \App\Model\BusinessSetting::where(['key' => 'phone'])->first()?->value)
    @php($address = \App\Model\BusinessSetting::where(['key' => 'address'])->first()?->value)

    <!-- Official Print Header (Visible ONLY when printing) -->
    <div class="print-header d-none-screen">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #222; padding-bottom: 12px; margin-bottom: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                @if($logo)
                    <img src="{{ App\CentralLogics\Helpers::onErrorImage($logo, asset('storage/app/public/restaurant') . '/' . $logo, asset('/public/assets/admin/img/food.png'), 'restaurant/') }}"
                         alt="{{ $restaurant_name }}" style="height: 55px; max-width: 140px; object-fit: contain;">
                @endif
                <div>
                    <h2 style="margin: 0; font-size: 20px; font-weight: bold; color: #111;">{{ $restaurant_name }}</h2>
                    @if($phone)
                        <div style="font-size: 11px; color: #555;">{{ translate('Phone') }}: {{ $phone }}</div>
                    @endif
                    @if($address)
                        <div style="font-size: 11px; color: #555;">{{ $address }}</div>
                    @endif
                </div>
            </div>
            <div style="text-align: left; direction: ltr;">
                <h3 style="margin: 0; font-size: 16px; font-weight: bold; color: #111; text-align: right; direction: rtl;">{{ translate('Official Cash Custody Statement') }}</h3>
                <div style="font-size: 11px; color: #333; margin-top: 4px; text-align: right; direction: rtl;">
                    <span>{{ translate('Statement Generation Date') }}: </span>
                    <span style="font-weight: bold;">{{ now()->format('Y-m-d h:i A') }}</span>
                </div>
                <div style="font-size: 11px; color: #333; text-align: right; direction: rtl;">
                    <span>{{ translate('Reference No') }}: </span>
                    <span style="font-weight: bold;">CUST-DM{{ $deliveryman->id }}-{{ now()->format('ymdHi') }}</span>
                </div>
            </div>
        </div>

        <!-- Delivery Man Details Banner in Print -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; background: #f8f9fa; border: 1px solid #ccc; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px;">
            <div>
                <div style="font-size: 10px; color: #666; font-weight: bold;">{{ translate('delivery_man') }}</div>
                <div style="font-size: 13px; font-weight: bold; color: #111;">{{ $deliveryman->f_name }} {{ $deliveryman->l_name }}</div>
            </div>
            <div>
                <div style="font-size: 10px; color: #666; font-weight: bold;">{{ translate('phone') }}</div>
                <div style="font-size: 12px; font-weight: bold; color: #111;" dir="ltr">{{ $deliveryman->phone }}</div>
            </div>
            <div>
                <div style="font-size: 10px; color: #666; font-weight: bold;">{{ translate('Branch') }}</div>
                <div style="font-size: 12px; font-weight: bold; color: #111;">{{ $deliveryman->branch ? $deliveryman->branch->name : translate('Main') }}</div>
            </div>
            <div>
                <div style="font-size: 10px; color: #666; font-weight: bold;">{{ translate('Settlement Status') }}</div>
                <div style="font-size: 12px; font-weight: bold; color: #111;">
                    @if($wallet->cash_in_hand <= 0)
                        {{ translate('Fully Settled (Clear)') }}
                    @elseif($wallet->total_settled_amount > 0 && $wallet->cash_in_hand > 0)
                        {{ translate('Partially Settled') }}
                    @else
                        {{ translate('Pending Custody') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header (Screen only) -->
    <div class="page-header pb-2 d-print-none">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item"><a class="breadcrumb-link" href="{{ route('admin.delivery-man.custody.index') }}">{{ translate('Delivery Men Wallets & Cash Custody') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ translate('Account Statement') }}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title d-flex align-items-center gap-2">
                    <span class="page-header-icon"><i class="tio-user-big text-primary"></i></span>
                    <span>{{ $deliveryman->f_name . ' ' . $deliveryman->l_name }}</span>
                    <span class="badge badge-soft-info badge-pill ml-2">{{ $deliveryman->phone }}</span>
                </h1>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                @if($wallet->cash_in_hand > 0)
                    <button type="button" class="btn btn-primary"
                        onclick="openSettlementModal('{{ $deliveryman->id }}', '{{ addslashes($deliveryman->f_name . ' ' . $deliveryman->l_name) }}', '{{ $wallet->cash_in_hand }}')">
                        <i class="tio-receipt mr-1"></i> {{ translate('Settle Custody') }}
                    </button>
                @endif
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="tio-print mr-1"></i> {{ translate('Print Statement') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">
                            <i class="tio-print mr-1"></i> {{ translate('Print Current Page') }}
                        </a>
                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['per_page' => 'all', 'autoprint' => '1']) }}">
                            <i class="tio-file-text mr-1"></i> {{ translate('Print Full Statement (All Transactions)') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <span class="text-muted font-weight-bold font-size-sm d-block mb-1">{{ translate('Remaining Cash Custody') }}</span>
                    <h2 class="{{ $wallet->cash_in_hand > 0 ? 'text-danger' : 'text-success' }} font-weight-bold mb-0">
                        {{ \App\CentralLogics\Helpers::set_symbol($wallet->cash_in_hand) }}
                    </h2>
                    <small class="text-muted">{{ translate('Amount currently owed by delivery man') }}</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <span class="text-muted font-weight-bold font-size-sm d-block mb-1">{{ translate('Total Collected Cash') }}</span>
                    <h2 class="text-primary font-weight-bold mb-0">
                        {{ \App\CentralLogics\Helpers::set_symbol($wallet->total_collected_cash) }}
                    </h2>
                    <small class="text-muted">{{ translate('from') }} {{ $wallet->collected_orders_count }} {{ translate('Cash on Delivery Orders') }}</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <span class="text-muted font-weight-bold font-size-sm d-block mb-1">{{ translate('Settled with Administration') }}</span>
                    <h2 class="text-success font-weight-bold mb-0">
                        {{ \App\CentralLogics\Helpers::set_symbol($wallet->total_settled_amount) }}
                    </h2>
                    <small class="text-muted">{{ translate('Recorded with official receipts and vouchers') }}</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <span class="text-muted font-weight-bold font-size-sm d-block mb-1">{{ translate('Settlement Status') }}</span>
                    <div class="mt-2">
                        @if($wallet->cash_in_hand <= 0)
                            <span class="badge badge-soft-success py-2 px-3 font-size-sm font-weight-bold">
                                <i class="tio-checkmark-circle mr-1"></i> {{ translate('Fully Settled (Clear)') }}
                            </span>
                        @elseif($wallet->total_settled_amount > 0 && $wallet->cash_in_hand > 0)
                            <span class="badge badge-soft-warning py-2 px-3 font-size-sm font-weight-bold">
                                <i class="tio-time mr-1"></i> {{ translate('Partially Settled') }}
                            </span>
                        @else
                            <span class="badge badge-soft-danger py-2 px-3 font-size-sm font-weight-bold">
                                <i class="tio-warning mr-1"></i> {{ translate('Pending Custody') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header border-0 pb-0">
            <div class="row align-items-center g-2 w-100">
                <div class="col-md-6">
                    <h3 class="card-title font-weight-bold">{{ translate('Financial Audit Ledger & Custody Transactions') }}</h3>
                </div>
                <div class="col-md-6 d-flex justify-content-md-end align-items-center flex-wrap gap-2 d-print-none">
                    <!-- Type Filter Tabs -->
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'all']) }}"
                            class="btn {{ $type == 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ translate('All Transactions') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'cash_collection']) }}"
                            class="btn {{ $type == 'cash_collection' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ translate('Collections (+)') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'custody_settlement']) }}"
                            class="btn {{ $type == 'custody_settlement' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ translate('Settlements (-)') }}
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
                            <th>{{ translate('Transaction Type') }}</th>
                            <th>{{ translate('Order / Voucher No') }}</th>
                            <th>{{ translate('Associated Party (Customer / Admin)') }}</th>
                            <th class="text-right">{{ translate('amount') }}</th>
                            <th class="text-right">{{ translate('Balance Before') }}</th>
                            <th class="text-right">{{ translate('Balance After') }}</th>
                            <th>{{ translate('Notes') }}</th>
                            <th class="text-center">{{ translate('Date & Time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $key => $tx)
                            <tr>
                                <td>{{ $transactions->firstItem() + $key }}</td>
                                <td>
                                    @if($tx->transaction_type === 'cash_collection')
                                        <span class="badge badge-soft-success py-1 px-2 font-weight-bold">
                                            <i class="tio-add mr-1"></i> {{ translate('Cash Collection') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger py-1 px-2 font-weight-bold">
                                            <i class="tio-remove mr-1"></i> {{ translate('Admin Settlement') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($tx->order_id)
                                        <a href="{{ route('admin.orders.details', ['id' => $tx->order_id]) }}" class="font-weight-bold text-primary">
                                            #{{ $tx->order_id }}
                                        </a>
                                    @elseif($tx->reference)
                                        <span class="badge badge-soft-info">{{ $tx->reference }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tx->transaction_type === 'cash_collection')
                                        <div>
                                            <span class="font-weight-bold text-dark d-block">{{ $tx->customer_name ?: translate('Customer') }}</span>
                                            @if($tx->customer_phone)
                                                <small class="text-muted" dir="ltr">{{ $tx->customer_phone }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <div>
                                            <span class="font-weight-bold text-dark d-block">
                                                <i class="tio-user-big mr-1"></i> {{ translate('Receiving Accountant:') }} {{ $tx->admin ? ($tx->admin->f_name . ' ' . $tx->admin->l_name) : translate('Admin Accountant') }}
                                            </span>
                                            <small class="badge badge-soft-secondary">{{ $tx->payment_method ?? 'cash' }}</small>
                                            @if($tx->settlement_type)
                                                <span class="badge {{ $tx->settlement_type === 'full' ? 'badge-soft-success' : 'badge-soft-warning' }} ml-1">
                                                    {{ $tx->settlement_type === 'full' ? translate('Fully Settled') : translate('Partially Settled') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-right font-weight-bold {{ $tx->transaction_type === 'cash_collection' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->transaction_type === 'cash_collection' ? '+' : '-' }} {{ \App\CentralLogics\Helpers::set_symbol($tx->amount) }}
                                </td>
                                <td class="text-right text-muted">
                                    {{ \App\CentralLogics\Helpers::set_symbol($tx->balance_before) }}
                                </td>
                                <td class="text-right font-weight-bold text-dark">
                                    {{ \App\CentralLogics\Helpers::set_symbol($tx->balance_after) }}
                                </td>
                                <td>
                                    <span class="text-muted font-size-sm">{{ $tx->notes ?: '-' }}</span>
                                </td>
                                <td class="text-center font-size-sm text-muted">
                                    {{ $tx->created_at->format('Y-m-d h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center p-4">
                                    <p class="mb-0 text-muted">{{ translate('No financial transactions found for this period') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer border-0 d-print-none">
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

    <!-- Official Signatures Block (Visible ONLY when printing) -->
    <div class="print-signatures d-none-screen">
        <div class="print-sig-col">
            <div>{{ translate('Delivery Man Signature') }}</div>
            <div style="height: 50px;"></div>
            <div style="font-size: 11px; color: #555;">{{ $deliveryman->f_name }} {{ $deliveryman->l_name }}</div>
        </div>
        <div class="print-sig-col">
            <div>{{ translate('Accountant / Administration Signature') }}</div>
            <div style="height: 50px;"></div>
            <div style="font-size: 11px; color: #555;">{{ auth('admin')->user()?->f_name }} {{ auth('admin')->user()?->l_name }}</div>
        </div>
        <div class="print-sig-col">
            <div>{{ translate('Official Stamp') }}</div>
            <div style="height: 50px;"></div>
            <div style="font-size: 11px; color: #555;">{{ $restaurant_name }}</div>
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
                            <span>{{ translate('Delivery Man:') }} <strong class="text-dark">{{ $deliveryman->f_name }} {{ $deliveryman->l_name }}</strong></span>
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
            $('#modal_remaining_display').removeClass('text-danger').addClass('text-success').text('0.00 (' + '{{ translate("Fully Settled") }}' + ')');
        } else {
            $('#modal_remaining_display').removeClass('text-success').addClass('text-danger');
        }
    }

    $('#modal_amount').on('input change keyup', updateRemainingDisplay);

    $('#btn_fill_full_amount').on('click', function() {
        $('#modal_amount').val(currentMaxCash.toFixed(2));
        updateRemainingDisplay();
    });

    @if(request('autoprint') == 1)
        $(window).on('load', function() {
            setTimeout(function() {
                window.print();
            }, 600);
        });
    @endif
</script>
@endpush
