@extends('layouts.admin.app')

@section('title', translate('InfoSoft ERP Product Mappings'))

@push('css_or_js')
<style>
    .badge-soft-info { background-color: rgba(23, 162, 184, 0.15); color: #17a2b8; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Header -->
    <div class="page-header pb-2">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center gap-2">
                    <i class="tio-table text-primary" style="font-size: 28px;"></i>
                    <span>{{ translate('InfoSoft ERP Product & SKU Mappings') }}</span>
                </h1>
                <p class="text-muted mb-0">
                    {{ translate('Mapping between store catalog products and InfoSoft unique compound item keys (cmbkey).') }}
                </p>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.infosoft.index') }}" class="btn btn-outline-secondary">
                    <i class="tio-settings"></i> {{ translate('ERP Settings') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Packaging Units Pill Bar -->
    @if($packingUnits->count() > 0)
    <div class="card mb-3">
        <div class="card-body py-2">
            <span class="font-weight-bold mr-2"><i class="tio-layers"></i> {{ translate('Synced Packaging Units') }}:</span>
            @foreach($packingUnits as $unit)
                <span class="badge badge-soft-info mr-1 mb-1 p-2">
                    {{ $unit->name_ar }} ({{ $unit->pack_id }}) &times; {{ $unit->default_pk_qty }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Mappings Table -->
    <div class="card">
        <div class="card-header border-0">
            <div class="row justify-content-between align-items-center flex-grow-1">
                <div class="col-md-4">
                    <h5 class="card-title mb-0">{{ translate('Mapped Product Variations') }} ({{ $mappings->total() }})</h5>
                </div>
                <div class="col-md-4">
                    <form action="{{ route('admin.infosoft.mappings') }}" method="GET">
                        <div class="input-group input-group-merge input-group-flush">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><i class="tio-search"></i></div>
                            </div>
                            <input type="search" name="search" class="form-control"
                                   placeholder="{{ translate('Search by Item ID, cmbkey, barcode...') }}"
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
                        <th>{{ translate('Store Product') }}</th>
                        <th>{{ translate('ERP Item ID') }}</th>
                        <th>{{ translate('Compound Key (cmbkey)') }}</th>
                        <th>{{ translate('Barcode') }}</th>
                        <th>{{ translate('Packaging Unit') }}</th>
                        <th>{{ translate('Pack Factor') }}</th>
                        <th>{{ translate('Synced Price') }}</th>
                        <th>{{ translate('Synced Stock') }}</th>
                        <th>{{ translate('Last Synced') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $map)
                    <tr>
                        <td>{{ $map->id }}</td>
                        <td>
                            @if($map->product)
                                <a href="{{ route('admin.product.view', [$map->product->id]) }}" class="font-weight-bold">
                                    {{ Str::limit($map->product->name, 35) }}
                                </a>
                            @else
                                <span class="text-danger">{{ translate('Product Deleted') }}</span>
                            @endif
                        </td>
                        <td><code>{{ $map->erp_item_id }}</code></td>
                        <td><span class="badge badge-light font-weight-bold">{{ $map->erp_cmbkey }}</span></td>
                        <td>{{ $map->barcode ?? '-' }}</td>
                        <td>{{ $map->pack_name ?? 'حبة' }}</td>
                        <td>{{ $map->pack_qty }}</td>
                        <td>{{ number_format($map->last_synced_price, 2) }}</td>
                        <td>
                            <span class="badge {{ $map->last_synced_stock > 0 ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                {{ $map->last_synced_stock }}
                            </span>
                        </td>
                        <td>{{ $map->synced_at ? $map->synced_at->diffForHumans() : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <img class="mb-3 w-one-c2" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="" style="width: 7rem;">
                            <p class="mb-0 text-muted">{{ translate('No product mappings found. Sync catalog from ERP to create mappings.') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer border-0">
            {!! $mappings->links() !!}
        </div>
    </div>
</div>
@endsection
