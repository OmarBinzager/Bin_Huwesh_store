@extends('layouts.admin.app')

@section('title', translate('Size Guides'))

@push('css_or_js')
<style>
    .size-guide-footer-alert {
        background-color: #FAEFF0;
        border: 1px solid #F5C6CB;
        border-radius: 8px;
        padding: 14px 18px;
        color: #8E1521;
        font-weight: 500;
        font-size: 13.5px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
        <div>
            <h2 class="page-header-title mb-1" style="font-weight: 700; color: #171717;">
                {{ translate('Size Guides') }}
            </h2>
            <div class="text-muted fs-12">
                {{ translate('Bin Huwesh • Operations Panel') }} &bull; {{ translate('Store Management / Size Guides') }}
            </div>
        </div>
        <div>
            <a href="{{ route('admin.size-chart.create') }}" class="btn btn--primary" style="background-color: #A71927; border-color: #A71927; font-weight: 600; padding: 10px 22px; border-radius: 6px;">
                <i class="tio-add mr-1"></i> {{ translate('New Chart') }}
            </a>
        </div>
    </div>

    <!-- Filter and Search Card -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form action="{{ url()->current() }}" method="GET">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-7">
                        <div class="input-group input-group-merge input-group-flush">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="tio-search"></i>
                                </div>
                            </div>
                            <input type="search" name="search" class="form-control"
                                   placeholder="{{ translate('Search in size guides...') }}"
                                   value="{{ $search }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <select name="status" class="form-control js-select2-custom" onchange="this.form.submit()">
                            <option value="all" {{ $status == 'all' || $status === null ? 'selected' : '' }}>{{ translate('All Statuses') }}</option>
                            <option value="1" {{ $status === '1' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                            <option value="0" {{ $status === '0' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-2 text-right">
                        <button type="submit" class="btn btn-secondary w-100">
                            {{ translate('Filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Size Guides Table -->
    <div class="card">
        <div class="table-responsive datatable-custom">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th style="font-weight: 600; color: #555;">{{ translate('Chart Name') }}</th>
                        <th style="font-weight: 600; color: #555;">{{ translate('Piece Type') }}</th>
                        <th style="font-weight: 600; color: #555;">{{ translate('Linkage') }}</th>
                        <th style="font-weight: 600; color: #555;">{{ translate('Unit') }}</th>
                        <th style="font-weight: 600; color: #555;">{{ translate('Status') }}</th>
                        <th class="text-center" style="font-weight: 600; color: #555;">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sizeCharts as $key => $chart)
                    <tr>
                        <td class="font-weight-bold text-dark">
                            <a href="{{ route('admin.size-chart.edit', [$chart->id]) }}" class="text-dark text-hover-primary">
                                {{ $chart->name }}
                            </a>
                            <div class="small text-muted font-weight-normal">
                                {{ count($chart->columns ?? []) }} {{ translate('columns') }} &bull; {{ count($chart->rows ?? []) }} {{ translate('sizes') }}
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-soft-info px-2 py-1 font-size-12 font-weight-medium">
                                {{ $chart->garment_type ?: translate('Top') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-soft-dark px-2 py-1 font-size-12">
                                {{ $chart->linkage_text }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-soft-secondary font-weight-bold">
                                {{ $chart->unit }}
                            </span>
                        </td>
                        <td>
                            <label class="toggle-switch toggle-switch-sm">
                                <input type="checkbox" class="toggle-switch-input"
                                       onclick="location.href='{{ route('admin.size-chart.status', [$chart->id, $chart->is_active ? 0 : 1]) }}'"
                                       {{ $chart->is_active ? 'checked' : '' }}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                        </td>
                        <td class="text-center">
                            <div class="btn--container justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-info preview-chart-btn"
                                        data-id="{{ $chart->id }}"
                                        data-name="{{ $chart->name }}"
                                        data-unit="{{ $chart->unit }}"
                                        data-instructions="{{ $chart->instructions }}"
                                        data-columns='@json($chart->columns ?? [])'
                                        data-rows='@json($chart->rows ?? [])'
                                        title="{{ translate('Preview') }}">
                                    <i class="tio-visible"></i>
                                </button>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="{{ route('admin.size-chart.edit', [$chart->id]) }}"
                                   title="{{ translate('Edit') }}">
                                    <i class="tio-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="form_alert('size-chart-{{ $chart->id }}', '{{ translate('Want to delete this size chart?') }}')"
                                        title="{{ translate('Delete') }}">
                                    <i class="tio-delete"></i>
                                </button>
                                <form action="{{ route('admin.size-chart.delete', [$chart->id]) }}"
                                      method="post" id="size-chart-{{ $chart->id }}">
                                    @csrf @method('delete')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <img class="mb-3 w-120" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="{{ translate('No Data') }}">
                            <p class="mb-0 text-muted">{{ translate('No size guides found') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer border-top-0 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                {{ translate('Showing') }} {{ $sizeCharts->firstItem() ?? 0 }} - {{ $sizeCharts->lastItem() ?? 0 }} {{ translate('of') }} {{ $sizeCharts->total() }} {{ translate('size guides') }}
            </span>
            <div>
                {!! $sizeCharts->links() !!}
            </div>
        </div>
    </div>

    <!-- Informational Note Banner Matching Screenshot -->
    <div class="mt-4">
        <div class="size-guide-footer-alert d-flex align-items-center gap-2">
            <i class="tio-document-text font-size-18 mr-2" style="color: #A71927;"></i>
            <div>
                <strong style="color: #8E1521;">{{ translate('Automatic Reflection') }}:</strong>
                {{ translate('Values reflect automatically on linked products or categories upon saving the chart without developer intervention.') }}
            </div>
        </div>
    </div>
</div>

<!-- Size Chart Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #FAEFF0;">
                <h5 class="modal-title font-weight-bold" style="color: #8E1521;" id="modalChartTitle">
                    {{ translate('Size Chart Preview') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div id="modalInstructions" class="text-muted font-italic small"></div>
                    <span class="badge badge-soft-primary" id="modalUnit"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered text-center mb-0" id="modalMatrixTable">
                        <thead class="thead-light">
                            <tr id="modalTableHead"></tr>
                        </thead>
                        <tbody id="modalTableBody"></tbody>
                    </table>
                </div>
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
    $('.preview-chart-btn').on('click', function() {
        const name = $(this).data('name');
        const unit = $(this).data('unit');
        const instructions = $(this).data('instructions');
        const columns = $(this).data('columns') || [];
        const rows = $(this).data('rows') || [];

        $('#modalChartTitle').text(name);
        $('#modalUnit').text('{{ translate("Unit") }}: ' + unit);
        $('#modalInstructions').text(instructions || '{{ translate("Standard measurement guide.") }}');

        // Render Head
        let headHtml = '';
        columns.forEach(col => {
            headHtml += `<th class="font-weight-bold">${col}</th>`;
        });
        $('#modalTableHead').html(headHtml);

        // Render Body
        let bodyHtml = '';
        rows.forEach((row, idx) => {
            let rowClass = (idx === 1) ? 'style="background-color: #FAEFF0; color: #A71927; font-weight: bold;"' : '';
            bodyHtml += `<tr ${rowClass}>`;
            columns.forEach(col => {
                bodyHtml += `<td>${row[col] !== undefined ? row[col] : '-'}</td>`;
            });
            bodyHtml += `</tr>`;
        });
        $('#modalTableBody').html(bodyHtml);

        $('#previewModal').modal('show');
    });
</script>
@endpush
