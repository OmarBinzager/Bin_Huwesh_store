@extends('layouts.admin.app')

@section('title', translate('InfoSoft ERP Integration'))

@push('css_or_js')
<style>
    .badge-soft-success { background-color: rgba(40, 167, 69, 0.15); color: #28a745; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .badge-soft-warning { background-color: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .badge-soft-info { background-color: rgba(23, 162, 184, 0.15); color: #17a2b8; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header pb-2">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center gap-2">
                    <i class="tio-cloud-outlined text-primary" style="font-size: 28px;"></i>
                    <span>{{ translate('InfoSoft ERP Integration') }}</span>
                    <span class="badge {{ $settings->is_enabled ? 'badge-soft-success' : 'badge-soft-danger' }} ml-2">
                        {{ $settings->is_enabled ? translate('Active / مفعل') : translate('Disabled / معطل') }}
                    </span>
                    <span class="badge badge-soft-info ml-1">ZATCA Phase 2</span>
                </h1>
                <p class="text-muted mb-0">
                    {{ translate('Manage connection credentials, branch/warehouse mapping, and live sync with InfoSoft ERP.') }}
                </p>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.infosoft.invoices') }}" class="btn btn-outline-primary mr-2">
                    <i class="tio-receipt-outlined"></i> {{ translate('Invoices Log') }}
                    @if($failedInvoices > 0)
                        <span class="badge badge-danger ml-1">{{ $failedInvoices }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.infosoft.mappings') }}" class="btn btn-outline-secondary">
                    <i class="tio-table"></i> {{ translate('Product Mappings') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <span class="text-muted font-size-sm">{{ translate('Connection Status') }}</span>
                            <h3 class="card-title mt-1 mb-0" id="live-connection-badge">
                                @if($settings->last_connection_status === 'success')
                                    <span class="text-success"><i class="tio-checkmark-circle"></i> {{ translate('Connected') }}</span>
                                @elseif($settings->last_connection_status === 'failed')
                                    <span class="text-danger"><i class="tio-clear-circle"></i> {{ translate('Disconnected') }}</span>
                                @else
                                    <span class="text-muted"><i class="tio-help-circle"></i> {{ translate('Untested') }}</span>
                                @endif
                            </h3>
                            <small class="text-muted" id="live-connection-db">
                                {{ $settings->database_name ? "DB: {$settings->database_name}" : translate('No database') }}
                            </small>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-soft-primary" id="btn-test-connection" onclick="testERPConnection()">
                                <i class="tio-refresh"></i> {{ translate('Test') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <span class="text-muted font-size-sm">{{ translate('Mapped Products') }}</span>
                            <h3 class="card-title mt-1 mb-0">{{ number_format($totalMapped) }}</h3>
                            <small class="text-muted">{{ translate('Linked to cmbkey') }}</small>
                        </div>
                        <div class="col-auto">
                            <span class="btn btn-icon btn-soft-info rounded-circle">
                                <i class="tio-barcode"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <span class="text-muted font-size-sm">{{ translate('Packaging Units') }}</span>
                            <h3 class="card-title mt-1 mb-0">{{ number_format($totalPackingUnits) }}</h3>
                            <small class="text-muted">{{ translate('Synced via /api/GetPacking') }}</small>
                        </div>
                        <div class="col-auto">
                            <form action="{{ route('admin.infosoft.sync-packing') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-soft-secondary" title="{{ translate('Refresh Units') }}">
                                    <i class="tio-sync"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <span class="text-muted font-size-sm">{{ translate('ERP Invoices Sent') }}</span>
                            <h3 class="card-title mt-1 mb-0">{{ number_format($totalInvoices) }}</h3>
                            <small class="text-danger font-weight-bold">
                                {{ $failedInvoices > 0 ? "{$failedInvoices} " . translate('Failed / DLQ') : translate('All healthy') }}
                            </small>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('admin.infosoft.invoices') }}" class="btn btn-icon btn-soft-success rounded-circle">
                                <i class="tio-receipt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Configuration Form -->
    <form action="{{ route('admin.infosoft.update-settings') }}" method="POST">
        @csrf

        <div class="row">
            <!-- Left Column: Credentials & Operational Mapping -->
            <div class="col-lg-8">
                <!-- Card 1: Connection & Authentication -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="tio-security-on text-primary"></i>
                            {{ translate('API Credentials & Authentication') }}
                        </h5>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_enabled" name="is_enabled" {{ $settings->is_enabled ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold" for="is_enabled">{{ translate('Enable ERP Integration') }}</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('ERP API Base URL') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="tio-globe"></i></span>
                                    </div>
                                    <input type="url" name="api_base_url" id="api_base_url" class="form-control"
                                           value="{{ old('api_base_url', $settings->api_base_url) }}"
                                           placeholder="https://erp.domain.com:8443 or http://IP:PORT" required>
                                </div>
                                <small class="text-muted">{{ translate('Base URL for InfoSoft endpoints: /api/Login, /api/GetAllItems, /api/PostInvoice, etc.') }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('API Username') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="tio-user"></i></span>
                                    </div>
                                    <input type="text" name="username" id="username" class="form-control"
                                           value="{{ old('username', $settings->username) }}"
                                           placeholder="Authorized API user" required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('API Password') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="tio-key"></i></span>
                                    </div>
                                    <input type="password" name="password" id="password" class="form-control"
                                           placeholder="{{ $settings->password ? '•••••••••••• (Leave blank to keep existing)' : 'Enter API password' }}">
                                </div>
                            </div>
                        </div>

                        <!-- Live test result banner -->
                        <div id="test-connection-alert" class="alert d-none mt-2 mb-0" role="alert"></div>
                    </div>
                </div>

                <!-- Card 2: Operational & Accounting Mapping -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="tio-tune text-primary"></i>
                            {{ translate('Operational & Accounting Mapping (InfoSoft Parameters)') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('Warehouse Code (warehouseNo)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="default_warehouse" class="form-control"
                                       value="{{ old('default_warehouse', $settings->default_warehouse) }}"
                                       placeholder="e.g. 01 or 0 for all" required>
                                <small class="text-muted">{{ translate('Send "0" to fetch aggregate balance from all warehouses') }}</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('Branch Code (brno / BRANCH)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="default_branch" class="form-control"
                                       value="{{ old('default_branch', $settings->default_branch) }}"
                                       placeholder="e.g. 01" required>
                                <small class="text-muted">{{ translate('Used for branch-specific promotional pricing') }}</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('Sales Center (SLCNTR)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="sales_center" class="form-control"
                                       value="{{ old('sales_center', $settings->sales_center) }}"
                                       placeholder="e.g. 01" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('Walk-in Customer Code (lcustcode)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="default_customer_code" class="form-control"
                                       value="{{ old('default_customer_code', $settings->default_customer_code) }}"
                                       placeholder="e.g. 01001" required>
                                <small class="text-muted">{{ translate('Default cash customer ledger in chart of accounts') }}</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('Online Sales Rep Code (SLPRSN)') }}</label>
                                <input type="text" name="sales_person" class="form-control"
                                       value="{{ old('sales_person', $settings->sales_person) }}"
                                       placeholder="e.g. WEBSITE">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('System User ID (USRID)') }}</label>
                                <input type="text" name="api_user" class="form-control"
                                       value="{{ old('api_user', $settings->api_user) }}"
                                       placeholder="e.g. API_USER">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('Default Invoice Type (ltrtype)') }} <span class="text-danger">*</span></label>
                                <select name="default_invoice_type" class="form-control">
                                    <option value="06" {{ $settings->default_invoice_type == '06' ? 'selected' : '' }}>
                                        06 - {{ translate('Simplified Cash Sales Invoice (B2C) - فاتورة نقدية مبسطة') }}
                                    </option>
                                    <option value="04" {{ $settings->default_invoice_type == '04' ? 'selected' : '' }}>
                                        04 - {{ translate('Standard Credit Sales Invoice (B2B) - فاتورة مبيعات آجل ضريبية') }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">{{ translate('Shipping Fee Ledger Account (other_acct)') }}</label>
                                <input type="text" name="shipping_account" class="form-control"
                                       value="{{ old('shipping_account', $settings->shipping_account) }}"
                                       placeholder="e.g. 410201 (Optional)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sync Controls & Live Validation -->
            <div class="col-lg-4">
                <!-- Synchronization Settings Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="tio-sync text-primary"></i>
                            {{ translate('Sync Rules & Scheduler') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="live_stock_check" name="live_stock_check" {{ $settings->live_stock_check ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="live_stock_check">
                                    {{ translate('Live Stock Check at Checkout') }}
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                {{ translate('Calls /api/GetStock (Qty - RsvQty) before completing orders to prevent overselling.') }}
                            </small>
                        </div>

                        <div class="mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_barcode" name="is_barcode" {{ $settings->is_barcode ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="is_barcode">
                                    {{ translate('Package Barcodes Mode (isBarcode)') }}
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                {{ translate('true: searches barcodes & packaging units; false: primary item units.') }}
                            </small>
                        </div>

                        <div class="mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="variant_grouping_enabled" name="variant_grouping_enabled" {{ $settings->variant_grouping_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-primary" for="variant_grouping_enabled">
                                    {{ translate('Auto Variant Matrix Grouping') }}
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                {{ translate('Automatically groups InfoSoft flat variant items (e.g. 100-BLACK-S, 100-RED-XL) under one single parent Product.') }}
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('Variant Code Separator') }}</label>
                            <input type="text" name="variant_separator" class="form-control" style="max-width: 100px;"
                                   value="{{ old('variant_separator', $settings->variant_separator ?? '-') }}" maxlength="5" required>
                            <small class="text-muted">{{ translate('Delimiter used in InfoSoft items, e.g. "-" in "100-BLACK-S"') }}</small>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('Incremental Sync Lookback (Days)') }}</label>
                            <input type="number" name="sync_days_lookback" class="form-control" min="1" max="30"
                                   value="{{ old('sync_days_lookback', $settings->sync_days_lookback) }}">
                            <small class="text-muted">{{ translate('Days parameter sent to /api/GetEditedItems') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('Sync Interval (Minutes)') }}</label>
                            <select name="sync_frequency_minutes" class="form-control">
                                <option value="15" {{ $settings->sync_frequency_minutes == 15 ? 'selected' : '' }}>{{ translate('Every 15 Minutes') }}</option>
                                <option value="30" {{ $settings->sync_frequency_minutes == 30 ? 'selected' : '' }}>{{ translate('Every 30 Minutes') }}</option>
                                <option value="60" {{ $settings->sync_frequency_minutes == 60 ? 'selected' : '' }}>{{ translate('Hourly (كل ساعة)') }}</option>
                                <option value="360" {{ $settings->sync_frequency_minutes == 360 ? 'selected' : '' }}>{{ translate('Every 6 Hours') }}</option>
                                <option value="1440" {{ $settings->sync_frequency_minutes == 1440 ? 'selected' : '' }}>{{ translate('Daily (يومياً)') }}</option>
                            </select>
                        </div>

                        <div class="p-2 bg-light rounded text-center">
                            <small class="text-muted d-block">{{ translate('Last Sync Timestamp') }}:</small>
                            <strong>{{ $settings->last_sync_at ? $settings->last_sync_at->diffForHumans() : translate('Never') }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Manual Trigger Buttons Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="tio-bolt text-warning"></i>
                            {{ translate('Manual Trigger Actions') }}
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column gap-2">
                        <button type="button" class="btn btn-outline-primary btn-block text-left" onclick="$('#form-sync-delta').submit()">
                            <i class="tio-refresh"></i> {{ translate('Sync Recent Changes Now (/api/GetEditedItems)') }}
                        </button>
                        <button type="button" class="btn btn-outline-info btn-block text-left" onclick="$('#form-sync-units').submit()">
                            <i class="tio-layers"></i> {{ translate('Sync Packaging Units (/api/GetPacking)') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-block text-left" onclick="confirmFullSync()">
                            <i class="tio-download-to"></i> {{ translate('Sync Full Catalog (/api/GetAllItems)') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn-primary px-5 btn-lg">
                <i class="tio-save"></i> {{ translate('Save InfoSoft Configuration') }}
            </button>
        </div>
    </form>

    <!-- Hidden Action Forms -->
    <form id="form-sync-delta" action="{{ route('admin.infosoft.sync-incremental') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="days" value="{{ $settings->sync_days_lookback ?? 1 }}">
    </form>
    <form id="form-sync-units" action="{{ route('admin.infosoft.sync-packing') }}" method="POST" class="d-none">
        @csrf
    </form>
    <form id="form-sync-full" action="{{ route('admin.infosoft.sync-all') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Recent Invoices Widget -->
    @if($recentLogs->count() > 0)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ translate('Recent ERP Invoices Dispatched') }}</h5>
            <a href="{{ route('admin.infosoft.invoices') }}" class="btn btn-sm btn-link">{{ translate('View All') }} &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-nowrap mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Order ID') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('ERP Invoice #') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>
                            <a href="{{ route('admin.orders.details', ['id' => $log->order_id]) }}" class="font-weight-bold">
                                #{{ $log->order_id }}
                            </a>
                        </td>
                        <td>
                            <span class="badge {{ $log->type == 'invoice' ? 'badge-soft-primary' : 'badge-soft-warning' }}">
                                {{ $log->type == 'invoice' ? translate('Invoice') : translate('Return') }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $log->erp_invoice_id ?? '-' }}</strong>
                        </td>
                        <td>
                            @if($log->status === 'success')
                                <span class="badge badge-soft-success"><i class="tio-checkmark"></i> {{ translate('Success') }}</span>
                            @elseif($log->status === 'failed')
                                <span class="badge badge-soft-danger"><i class="tio-clear"></i> {{ translate('Failed') }} ({{ $log->error_code }})</span>
                            @else
                                <span class="badge badge-soft-warning">{{ translate('Pending') }}</span>
                            @endif
                        </td>
                        <td>{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('script_2')
<script>
    function testERPConnection() {
        var btn = $('#btn-test-connection');
        var alertBox = $('#test-connection-alert');
        var badge = $('#live-connection-badge');
        var dbLabel = $('#live-connection-db');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> {{ translate("Testing...") }}');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: "{{ route('admin.infosoft.test-connection') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status) {
                    alertBox.removeClass('d-none').addClass('alert-success').html(
                        '<i class="tio-checkmark-circle"></i> <strong>' + res.message + '</strong> (Token Expiry: ' + res.expireIn + ')'
                    );
                    badge.html('<span class="text-success"><i class="tio-checkmark-circle"></i> {{ translate("Connected") }}</span>');
                    dbLabel.text('DB: ' + res.database);
                    toastr.success(res.message);
                } else {
                    alertBox.removeClass('d-none').addClass('alert-danger').html(
                        '<i class="tio-clear-circle"></i> <strong>' + res.message + '</strong>'
                    );
                    badge.html('<span class="text-danger"><i class="tio-clear-circle"></i> {{ translate("Failed") }}</span>');
                    toastr.error(res.message);
                }
            },
            error: function(err) {
                alertBox.removeClass('d-none').addClass('alert-danger').html(
                    '<i class="tio-clear-circle"></i> {{ translate("Server request error. Check server logs.") }}'
                );
                toastr.error('{{ translate("Connection test failed.") }}');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="tio-refresh"></i> {{ translate("Test") }}');
            }
        });
    }

    function confirmFullSync() {
        if (confirm("{{ translate('Full catalog sync can process hundreds of items. Do you want to proceed?') }}")) {
            $('#form-sync-full').submit();
        }
    }
</script>
@endpush
