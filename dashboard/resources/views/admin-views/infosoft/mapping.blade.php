@extends('layouts.admin.app')

@section('title', translate('InfoSoft ERP Product Mappings & Variant Normalization'))

@push('css_or_js')
<style>
    .badge-soft-info { background-color: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    .badge-soft-primary { background-color: rgba(0, 123, 255, 0.15); color: #007bff; }
    .badge-soft-success { background-color: rgba(40, 167, 69, 0.15); color: #28a745; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .color-swatch { width: 14px; height: 14px; border-radius: 50%; display: inline-block; vertical-align: middle; border: 1px solid rgba(0,0,0,0.2); margin-right: 4px; }
    .dict-chip { display: inline-flex; align-items: center; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 20px; padding: 3px 10px; margin: 3px; font-size: 12px; }
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
                    <span>{{ translate('InfoSoft ERP Product & Variant Mappings') }}</span>
                </h1>
                <p class="text-muted mb-0">
                    {{ translate('Mapping between store catalog products, matrix variants (Color/Size), and InfoSoft unique compound item keys (cmbkey).') }}
                </p>
            </div>
            <div class="col-sm-auto">
                <button type="button" class="btn btn-outline-primary mr-2" data-toggle="modal" data-target="#addDictionaryModal">
                    <i class="tio-add"></i> {{ translate('Add Normalization Rule') }}
                </button>
                <a href="{{ route('admin.infosoft.index') }}" class="btn btn-outline-secondary">
                    <i class="tio-settings"></i> {{ translate('ERP Settings') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Variant Normalization Dictionary Collapsible Card -->
    <div class="card mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center" data-toggle="collapse" data-target="#collapseDictionary" style="cursor: pointer;">
            <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                <i class="tio-palette text-info"></i>
                <span>{{ translate('Variant Normalization Dictionary (قاموس مواءمة الألوان والمقاسات)') }}</span>
                <span class="badge badge-soft-info ml-2">{{ count($dictionaries ?? []) }} {{ translate('Rules') }}</span>
            </h5>
            <i class="tio-chevron-down"></i>
        </div>
        <div class="collapse show" id="collapseDictionary">
            <div class="card-body py-3">
                <div class="row">
                    <!-- Colors Dictionary -->
                    <div class="col-md-6 mb-2">
                        <h6 class="font-weight-bold d-flex align-items-center gap-1 mb-2">
                            <i class="tio-color-lens text-primary"></i> {{ translate('Color Aliases (مواءمة الألوان)') }}:
                        </h6>
                        <div class="d-flex flex-wrap" style="max-height: 180px; overflow-y: auto;">
                            @foreach($dictionaries->where('type', 'color') as $dict)
                                <div class="dict-chip">
                                    @if($dict->hex_color)
                                        <span class="color-swatch" style="background-color: {{ $dict->hex_color }};"></span>
                                    @endif
                                    <strong>{{ $dict->raw_keyword }}</strong>
                                    <span class="text-muted mx-1">&rarr;</span>
                                    <span>{{ $dict->normalized_name_ar }}</span>
                                    <form action="{{ route('admin.infosoft.delete-dictionary', [$dict->id]) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('{{ translate('Delete this rule?') }}')">
                                        @csrf
                                        <button type="submit" class="border-0 bg-transparent text-danger p-0 ml-1" style="line-height: 1;" title="{{ translate('Delete') }}">&times;</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Sizes Dictionary -->
                    <div class="col-md-6 mb-2">
                        <h6 class="font-weight-bold d-flex align-items-center gap-1 mb-2">
                            <i class="tio-straighten text-warning"></i> {{ translate('Size Aliases (مواءمة المقاسات)') }}:
                        </h6>
                        <div class="d-flex flex-wrap" style="max-height: 180px; overflow-y: auto;">
                            @foreach($dictionaries->where('type', 'size') as $dict)
                                <div class="dict-chip">
                                    <strong>{{ $dict->raw_keyword }}</strong>
                                    <span class="text-muted mx-1">&rarr;</span>
                                    <span>{{ $dict->normalized_name_ar }}</span>
                                    <form action="{{ route('admin.infosoft.delete-dictionary', [$dict->id]) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('{{ translate('Delete this rule?') }}')">
                                        @csrf
                                        <button type="submit" class="border-0 bg-transparent text-danger p-0 ml-1" style="line-height: 1;" title="{{ translate('Delete') }}">&times;</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
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
                        <th>{{ translate('Variant (Color/Size)') }}</th>
                        <th>{{ translate('ERP Variant Item ID') }}</th>
                        <th>{{ translate('Compound Key (cmbkey)') }}</th>
                        <th>{{ translate('Barcode') }}</th>
                        <th>{{ translate('Packaging Unit') }}</th>
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
                                    {{ Str::limit($map->product->name, 30) }}
                                </a>
                            @else
                                <span class="text-danger">{{ translate('Product Deleted') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($map->variation_type)
                                <span class="badge badge-soft-primary font-weight-bold">
                                    {{ $map->variation_type }}
                                </span>
                                @if($map->color)
                                    <small class="d-block text-muted">{{ translate('Color') }}: {{ $map->color }}</small>
                                @endif
                                @if($map->size)
                                    <small class="d-block text-muted">{{ translate('Size') }}: {{ $map->size }}</small>
                                @endif
                            @else
                                <span class="badge badge-soft-secondary">{{ translate('Standard (No Variants)') }}</span>
                            @endif
                        </td>
                        <td><code>{{ $map->erp_item_id }}</code></td>
                        <td><span class="badge badge-light font-weight-bold">{{ $map->erp_cmbkey }}</span></td>
                        <td>{{ $map->barcode ?? '-' }}</td>
                        <td>{{ $map->pack_name ?? 'حبة' }}</td>
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

<!-- Modal: Add Dictionary Rule -->
<div class="modal fade" id="addDictionaryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.infosoft.store-dictionary') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Add Normalization Rule (إضافة قاعدة مواءمة)') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label font-weight-bold">{{ translate('Type (النوع)') }} <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" id="dict_type" onchange="toggleHexField(this.value)" required>
                            <option value="color">{{ translate('Color (لون)') }}</option>
                            <option value="size">{{ translate('Size (مقاس)') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label font-weight-bold">{{ translate('Raw ERP Keyword (الكلمة الواردة من إنفوسوفت)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="raw_keyword" class="form-control" placeholder="e.g. BLK, BLACK, اسود, XXL, 2XL" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label font-weight-bold">{{ translate('Normalized Arabic Name (الاسم الموحد بالعربية)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="normalized_name_ar" class="form-control" placeholder="e.g. أسود, 2XL" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label font-weight-bold">{{ translate('Normalized English Name (الاسم بالإنجليزية)') }}</label>
                        <input type="text" name="normalized_name_en" class="form-control" placeholder="e.g. Black, 2XL">
                    </div>

                    <div class="form-group" id="hex_group">
                        <label class="form-label font-weight-bold">{{ translate('Color Hex Code (كود اللون هيكس)') }}</label>
                        <div class="input-group">
                            <input type="color" id="hex_picker" class="form-control" style="max-width: 60px; height: 38px; padding: 2px;"
                                   value="#000000" onchange="$('#hex_color').val(this.value)">
                            <input type="text" name="hex_color" id="hex_color" class="form-control" value="#000000" placeholder="#000000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Save Rule') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function toggleHexField(val) {
        if (val === 'color') {
            $('#hex_group').show();
        } else {
            $('#hex_group').hide();
        }
    }
</script>
@endpush
