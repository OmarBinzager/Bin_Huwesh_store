@extends('layouts.admin.app')

@section('title', translate('InfoSoft ERP Invoices & Returns Log'))

@push('css_or_js')
<style>
    .badge-soft-success { background-color: rgba(40, 167, 69, 0.15); color: #28a745; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .badge-soft-warning { background-color: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .badge-soft-info { background-color: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    pre.payload-box { max-height: 250px; overflow-y: auto; background: #2d3748; color: #a0aec0; padding: 12px; border-radius: 6px; font-size: 12px; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Header -->
    <div class="page-header pb-2">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center gap-2">
                    <i class="tio-receipt-outlined text-primary" style="font-size: 28px;"></i>
                    <span>{{ translate('InfoSoft ERP Invoices & Dead-Letter Queue') }}</span>
                </h1>
                <p class="text-muted mb-0">
                    {{ translate('Audit trail of sales invoices and credit notes submitted to InfoSoft ERP with ZATCA Phase 2 compliance.') }}
                </p>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.infosoft.index') }}" class="btn btn-outline-secondary">
                    <i class="tio-settings"></i> {{ translate('ERP Settings') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Tabs & Stats -->
    <div class="row g-2 mb-3">
        <div class="col-sm-4">
            <div class="card card-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted font-size-sm">{{ translate('Total Invoices') }}</span>
                        <h3 class="mb-0">{{ number_format($totalCount) }}</h3>
                    </div>
                    <span class="btn btn-icon btn-soft-primary rounded-circle"><i class="tio-receipt"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card card-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted font-size-sm">{{ translate('Successfully Synced') }}</span>
                        <h3 class="mb-0 text-success">{{ number_format($successCount) }}</h3>
                    </div>
                    <span class="btn btn-icon btn-soft-success rounded-circle"><i class="tio-checkmark"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card card-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted font-size-sm">{{ translate('Failed / Needs Attention (DLQ)') }}</span>
                        <h3 class="mb-0 text-danger">{{ number_format($failedCount) }}</h3>
                    </div>
                    <span class="btn btn-icon btn-soft-danger rounded-circle"><i class="tio-warning"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card">
        <div class="card-header border-0">
            <div class="row justify-content-between align-items-center flex-grow-1">
                <div class="col-md-6 mb-2 mb-md-0">
                    <ul class="nav nav-segment">
                        <li class="nav-item">
                            <a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.infosoft.invoices') }}">
                                {{ translate('All') }} ({{ $totalCount }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') === 'success' ? 'active' : '' }}" href="{{ route('admin.infosoft.invoices', ['status' => 'success']) }}">
                                {{ translate('Success') }} ({{ $successCount }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') === 'failed' ? 'active' : '' }}" href="{{ route('admin.infosoft.invoices', ['status' => 'failed']) }}">
                                {{ translate('Failed') }} ({{ $failedCount }})
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <form action="{{ route('admin.infosoft.invoices') }}" method="GET">
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <div class="input-group input-group-merge input-group-flush">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><i class="tio-search"></i></div>
                            </div>
                            <input type="search" name="search" class="form-control"
                                   placeholder="{{ translate('Search by Order ID, Invoice #, Error code...') }}"
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">{{ translate('Search') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive datatable-custom">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Order ID') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('ERP Invoice ID') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Attempts') }}</th>
                        <th>{{ translate('Error / Response Message') }}</th>
                        <th>{{ translate('Timestamp') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>
                            <a href="{{ route('admin.orders.details', ['id' => $log->order_id]) }}" class="font-weight-bold">
                                #{{ $log->order_id }}
                            </a>
                        </td>
                        <td>
                            <span class="badge {{ $log->type == 'invoice' ? 'badge-soft-primary' : 'badge-soft-warning' }}">
                                {{ $log->type == 'invoice' ? translate('Invoice (مبيعات)') : translate('Return (مردود)') }}
                            </span>
                        </td>
                        <td>
                            @if($log->erp_invoice_id)
                                <span class="badge badge-soft-info font-weight-bold">#{{ $log->erp_invoice_id }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($log->status === 'success')
                                <span class="badge badge-soft-success"><i class="tio-checkmark"></i> {{ translate('Success') }}</span>
                            @elseif($log->status === 'failed')
                                <span class="badge badge-soft-danger"><i class="tio-clear"></i> {{ translate('Failed') }}</span>
                            @else
                                <span class="badge badge-soft-warning">{{ translate('Pending') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-light">{{ $log->attempts }}</span>
                        </td>
                        <td>
                            @if($log->error_code)
                                <span class="badge badge-soft-danger mr-1">{{ $log->error_code }}</span>
                            @endif
                            <span class="text-truncate d-inline-block" style="max-width: 250px;" title="{{ $log->error_message }}">
                                {{ $log->error_message ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-info" title="{{ translate('View JSON Payload') }}"
                                        onclick="viewPayload({{ $log->id }})">
                                    <i class="tio-visible-outlined"></i>
                                </button>
                                @if($log->status !== 'success')
                                <form action="{{ route('admin.infosoft.retry-invoice', ['id' => $log->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="{{ translate('Retry Now') }}">
                                        <i class="tio-refresh"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <img class="mb-3 w-one-c2" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="" style="width: 7rem;">
                            <p class="mb-0 text-muted">{{ translate('No invoice logs found matching your criteria.') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer border-0">
            {!! $logs->links() !!}
        </div>
    </div>
</div>

<!-- Modal: View JSON Payloads -->
<div class="modal fade" id="payloadModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payloadModalTitle">{{ translate('Invoice Payload Details') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>{{ translate('Request Payload Sent to /api/PostInvoice') }}:</h6>
                <pre id="modal-request-payload" class="payload-box"></pre>

                <h6 class="mt-3">{{ translate('Response Payload Received from InfoSoft') }}:</h6>
                <pre id="modal-response-payload" class="payload-box"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function viewPayload(logId) {
        var url = "{{ url('admin/infosoft/invoice-payload') }}/" + logId;
        $.getJSON(url, function(data) {
            $('#payloadModalTitle').text('{{ translate("Order #") }}' + data.order_id + ' - {{ translate("Payload Details") }}');
            $('#modal-request-payload').text(JSON.stringify(data.request_payload, null, 2));
            $('#modal-response-payload').text(JSON.stringify(data.response_payload, null, 2));
            $('#payloadModal').modal('show');
        });
    }
</script>
@endpush
