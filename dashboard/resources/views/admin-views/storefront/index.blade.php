@extends('layouts.admin.app')

@section('title', translate('إدارة واجهة المتجر 3D'))

@push('css_or_js')
<style>
    .sf-hotspot-stage {
        position: relative;
        display: inline-block;
        max-width: 320px;
        background: repeating-conic-gradient(rgba(0,0,0,.07) 0 25%, transparent 0 50%) 0 0 / 16px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        cursor: crosshair;
    }
    .sf-hotspot-stage img {
        display: block;
        width: 100%;
        height: auto;
        user-select: none;
    }
    .sf-hotspot-dot {
        position: absolute;
        width: 24px;
        height: 24px;
        transform: translate(-50%, -50%);
        border-radius: 50%;
        background: #d0101f;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: grid;
        place-items: center;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.35);
        pointer-events: none;
    }
    .sf-checker {
        background: repeating-conic-gradient(rgba(0,0,0,.07) 0 25%, transparent 0 50%) 0 0 / 14px 14px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .sf-example-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 10px 12px;
    }
    .sf-example-thumb {
        width: 68px;
        height: 84px;
        object-fit: contain;
        cursor: zoom-in;
        transition: transform .15s ease;
    }
    .sf-example-thumb:hover {
        transform: scale(1.06);
    }
    .sf-example-thumb-wide {
        width: 112px;
        height: 68px;
        object-fit: contain;
        cursor: zoom-in;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-cube"></i></span>
            <span>{{ translate('إدارة واجهة المتجر التفاعلية (3D Storefront)') }}</span>
        </h1>
        <span class="badge badge-soft-info px-3 py-2">{{ translate('أي حفظ يحدّث كاش المتجر تلقائيًا') }}</span>
    </div>

    {{-- Navigation Tabs --}}
    <ul class="nav nav-tabs page-header-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'looks' ? 'active' : '' }}" href="{{ route('admin.storefront.index', ['tab' => 'looks']) }}">
                {{ translate('إطلالات الهيرو (3D Ring)') }} ({{ $looks->count() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'virtual-fit' ? 'active' : '' }}" href="{{ route('admin.storefront.index', ['tab' => 'virtual-fit']) }}">
                {{ translate('وصل حديثًا (VirtualFit)') }} ({{ $virtualFit->count() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'shoes' ? 'active' : '' }}" href="{{ route('admin.storefront.index', ['tab' => 'shoes']) }}">
                {{ translate('رفوف الأحذية (ShoeShelf)') }} ({{ $shoes->count() }}/10)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'rail' ? 'active' : '' }}" href="{{ route('admin.storefront.index', ['tab' => 'rail']) }}">
                {{ translate('علاقة الملابس (3D Rail)') }} ({{ $rail->count() }}/10)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'categories' ? 'active' : '' }}" href="{{ route('admin.storefront.index', ['tab' => 'categories']) }}">
                {{ translate('بطاقات الأقسام (WebGL)') }} ({{ $categoryCards->count() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'testimonials' ? 'active' : '' }}" href="{{ route('admin.storefront.index', ['tab' => 'testimonials']) }}">
                {{ translate('آراء العملاء') }} ({{ count($testimonials ?? []) }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'try-on' ? 'active' : '' }}" href="{{ route('admin.storefront.index', ['tab' => 'try-on']) }}">
                <i class="tio-camera mr-1"></i>{{ translate('التجربة الافتراضية (Anywear)') }}
                @if(!empty($tryOnSettings['enabled']))
                    <span class="badge badge-soft-success ml-1">{{ translate('مفعّل') }}</span>
                @endif
            </a>
        </li>
    </ul>

    {{-- TAB 1: LOOKS --}}
    @if($tab === 'looks')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('الإطلالات المسجلة') }}</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('الإطلالة') }}</th>
                                    <th>{{ translate('القطع') }}</th>
                                    <th>{{ translate('الحالة') }}</th>
                                    <th class="text-center">{{ translate('إجراء') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($looks as $l)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $l->image_url }}" alt="" class="sf-checker" style="width:42px;height:60px;object-fit:contain;">
                                            <div>
                                                <div class="font-weight-bold">{{ $l->title }}</div>
                                                <small class="text-muted">{{ $l->subtitle }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $l->items->count() }}</td>
                                    <td>
                                        <span class="badge {{ $l->published ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                            {{ $l->published ? translate('منشورة') : translate('مسودة') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick='editLook(@json($l->load("items")))'>{{ translate('تعديل') }}</button>
                                        <form action="{{ route('admin.storefront.looks.delete', $l->id) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف الإطلالة؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">{{ translate('لا توجد إطلالات مضافة بعد — يستخدم المتجر البيانات الافتراضية حاليًا.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('إضافة / تعديل إطلالة وتحديد نقاط القطع (Hotspots)') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.looks.save') }}" method="POST" id="lookForm">
                        @csrf
                        <input type="hidden" name="id" id="look_id">
                        <input type="hidden" name="image_path" id="look_image_path" required>
                        <input type="hidden" name="image_width" id="look_image_width" value="900">
                        <input type="hidden" name="image_height" id="look_image_height" value="1400">
                        <input type="hidden" name="items_json" id="look_items_json" value="[]">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('عنوان الإطلالة') }} *</label>
                                <input type="text" name="title" id="look_title" class="form-control" required placeholder="مثال: إطلالة عدن الكلاسيكية">
                            </div>
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('الوصف المختصر') }}</label>
                                <input type="text" name="subtitle" id="look_subtitle" class="form-control" placeholder="جاكيت كتان مع بنطال قماشي">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('خصم الإطلالة كاملة (%)') }}</label>
                                <input type="number" name="whole_look_discount" id="look_discount" class="form-control" min="0" max="90">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('الترتيب') }}</label>
                                <input type="number" name="sort_order" id="look_order" class="form-control" value="1">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <label class="toggle-switch d-flex align-items-center mb-2">
                                    <input type="checkbox" name="published" id="look_published" value="1" class="toggle-switch-input" checked>
                                    <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                    <span class="ms-2">{{ translate('منشورة في الموقع') }}</span>
                                </label>
                            </div>

                            <div class="col-12">
                                <label class="input-label">{{ translate('صورة المودل المفرغة (WebP/PNG بخلفية شفافة)') }}</label>
                                <input type="file" accept="image/png,image/webp" class="form-control" onchange="uploadProcessedLook(this)">
                                <small class="text-muted d-block mt-1">{{ translate('يتم قص الهوامش الشفافة وتوحيد الارتفاع إلى 1400px بصيغة WebP تلقائيًا في المتصفح.') }}</small>
                                <div class="sf-example-box d-flex align-items-center gap-3 mt-2">
                                    <img src="{{ asset('public/assets/admin/img/storefront-examples/look-example.webp') }}"
                                         alt="مثال صورة الإطلالة"
                                         class="sf-checker sf-example-thumb"
                                         onclick="openExampleModal(this.src, 'مثال صورة الإطلالة الكاملة (Hero Look)', 'صورة كاملة الجسم من الرأس حتى الحذاء على خلفية شفافة (PNG/WebP)، بدون قص الرأس أو القدمين، ارتفاع مثالي 1400px.')">
                                    <div class="small">
                                        <div class="fw-bold text-dark">{{ translate('مثال مثالي لصورة الإطلالة:') }}</div>
                                        <div class="text-muted">{{ translate('مودل كامل الجسم (من الرأس للحذاء) بخلفية شفافة PNG/WebP · ارتفاع 1400px · اضغط على الصورة للتكبير') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5 text-center">
                                <div class="sf-hotspot-stage" id="lookStage" onclick="placeHotspot(event)">
                                    <img id="lookPreviewImg" src="{{ asset('public/assets/admin/img/160x160/2.png') }}" alt="">
                                    <div id="lookDotsLayer"></div>
                                </div>
                                <p class="text-muted small mt-2">{{ translate('انقر على الصورة لإضافة نقطة قطعة (Hotspot)') }}</p>
                            </div>

                            <div class="col-md-7">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>{{ translate('القطع المرتبطة بالإطلالة') }}</strong>
                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="addLookItemRow()">+ {{ translate('إضافة قطعة') }}</button>
                                </div>
                                <div id="lookItemsContainer" class="d-flex flex-column gap-2"></div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" onclick="resetLookForm()">{{ translate('جديد') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('حفظ الإطلالة') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TAB 2: VIRTUAL FIT --}}
    @if($tab === 'virtual-fit')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('قطع تجربة القياس (وصل حديثًا)') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-align-middle mb-0">
                        <thead class="thead-light"><tr><th>{{ translate('القطعة') }}</th><th>{{ translate('الفئة') }}</th><th>{{ translate('الألوان') }}</th><th></th></tr></thead>
                        <tbody>
                        @forelse($virtualFit as $vf)
                            @php
                                $vfColorsArr = is_array($vf->colors) ? $vf->colors : [];
                                $vfResolvedColors = array_map(function($vc) {
                                    $vc['garment_url'] = \App\Model\StorefrontVirtualFitItem::resolveMediaUrl($vc['garment_path'] ?? '', 'storefront/virtual-fit');
                                    $vc['model_url'] = \App\Model\StorefrontVirtualFitItem::resolveMediaUrl($vc['model_path'] ?? '', 'storefront/virtual-fit');
                                    return $vc;
                                }, $vfColorsArr);
                                $vfEditPayload = [
                                    'id' => $vf->id,
                                    'product_id' => $vf->product_id,
                                    'name' => $vf->name,
                                    'category_key' => $vf->category_key,
                                    'category_name' => $vf->category_name,
                                    'sizes' => is_array($vf->sizes) ? implode(',', $vf->sizes) : 'S,M,L,XL,XXL',
                                    'colors' => $vfResolvedColors,
                                ];
                            @endphp
                            <tr>
                                <td><strong>{{ $vf->name }}</strong><br><small class="text-muted">{{ $vf->product ? 'مرتبط: #'.$vf->product_id : 'غير مرتبط بمنتج' }}</small></td>
                                <td>{{ $vf->category_name }}</td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge badge-soft-dark">{{ count($vfResolvedColors) }}</span>
                                        @foreach($vfResolvedColors as $vc)
                                            @php
                                                $gUrl = $vc['garment_url'] ?? '';
                                                $mUrl = $vc['model_url'] ?? '';
                                            @endphp
                                            <div class="d-inline-flex align-items-center gap-1 border rounded p-1 bg-light" title="{{ $vc['name'] ?? '' }}">
                                                @if($gUrl)
                                                    <img src="{{ $gUrl }}" alt="{{ $vc['name'] ?? '' }}" class="sf-checker rounded" style="width:28px;height:36px;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, '{{ e($vf->name . ' - ' . ($vc['name'] ?? '') . ' (القطعة)') }}', '')">
                                                @endif
                                                @if($mUrl)
                                                    <img src="{{ $mUrl }}" alt="{{ $vc['name'] ?? '' }}" class="sf-checker rounded" style="width:28px;height:36px;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, '{{ e($vf->name . ' - ' . ($vc['name'] ?? '') . ' (المودل)') }}', '')">
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick='editVirtualFit(@json($vfEditPayload))'>{{ translate('تعديل') }}</button>
                                    <form action="{{ route('admin.storefront.virtual-fit.delete', $vf->id) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">{{ translate('لا توجد عناصر مضافة بعد') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('إضافة قطعة لتجربة القياس على المودل') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.virtual-fit.save') }}" method="POST" id="vfForm">
                        @csrf
                        <input type="hidden" name="id" id="vf_id">
                        <input type="hidden" name="colors_json" id="vf_colors_json" value="[]">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('ربط بمنتج من الكتالوج') }}</label>
                                <select name="product_id" id="vf_product_id" class="form-control js-select2-custom">
                                    <option value="">-- {{ translate('اختياري: ربط بمنتج حقيقي') }} --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->name }} ({{ number_format($p->price) }} ر.ي)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('اسم القطعة في العرض') }} *</label>
                                <input type="text" name="name" id="vf_name" class="form-control" required placeholder="تيشيرت بولو قطني">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('معرف التبويب (إنجليزي)') }} *</label>
                                <input type="text" name="category_key" id="vf_category_key" class="form-control" required value="shirts" placeholder="shirts / jackets">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('اسم التبويب') }} *</label>
                                <input type="text" name="category_name" id="vf_category_name" class="form-control" required value="قمصان">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('المقاسات الافتراضية') }}</label>
                                <input type="text" name="sizes" id="vf_sizes" class="form-control" value="S,M,L,XL,XXL">
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-3 bg-light">
                                    <strong>{{ translate('إضافة لون (صورتان شفافتـان WebP: القطعة + المودل بنفس الوقفة)') }}</strong>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4"><input type="text" id="vf_c_name" class="form-control" placeholder="اسم اللون (أبيض)"></div>
                                        <div class="col-md-2"><input type="color" id="vf_c_swatch" class="form-control form-control-color" value="#111111"></div>
                                        <div class="col-md-3">
                                            <label class="small">صورة القطعة</label>
                                            <input type="file" id="vf_c_garment" accept="image/png,image/webp" class="form-control form-control-sm" onchange="previewVfFile(this, 'vf_garment_preview', 'vf_garment_empty')">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small">صورة المودل (1360px)</label>
                                            <input type="file" id="vf_c_model" accept="image/png,image/webp" class="form-control form-control-sm" onchange="previewVfFile(this, 'vf_model_preview', 'vf_model_empty')">
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-2 bg-white text-center">
                                                <div class="small text-muted mb-1">{{ translate('معاينة صورة القطعة المختارة') }}</div>
                                                <div class="sf-checker rounded d-flex align-items-center justify-content-center mx-auto" style="height:120px;">
                                                    <img id="vf_garment_preview" src="" alt="" class="d-none" style="max-height:112px;max-width:100%;object-fit:contain;">
                                                    <span id="vf_garment_empty" class="small text-muted">{{ translate('لم يتم اختيار صورة') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-2 bg-white text-center">
                                                <div class="small text-muted mb-1">{{ translate('معاينة صورة المودل المختارة') }}</div>
                                                <div class="sf-checker rounded d-flex align-items-center justify-content-center mx-auto" style="height:120px;">
                                                    <img id="vf_model_preview" src="" alt="" class="d-none" style="max-height:112px;max-width:100%;object-fit:contain;">
                                                    <span id="vf_model_empty" class="small text-muted">{{ translate('لم يتم اختيار صورة') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12"><button type="button" class="btn btn-sm btn-outline-primary" onclick="addVfColor()">+ {{ translate('معالجة ورفع اللون') }}</button></div>
                                    </div>
                                    <div id="vfColorsList" class="mt-3 d-flex flex-wrap gap-2"></div>
                                    <div class="sf-example-box d-flex flex-wrap align-items-center gap-3 mt-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ asset('public/assets/admin/img/storefront-examples/vf-garment-example.webp') }}"
                                                 alt="مثال صورة القطعة"
                                                 class="sf-checker sf-example-thumb"
                                                 onclick="openExampleModal(this.src, 'مثال صورة القطعة (Ghost Mannequin)', 'صورة القطعة وحدها مفرغة على خلفية شفافة PNG/WebP، بنسبة مربعة (800×800px مثالي) لعرضها على القوس الدائري.')">
                                            <img src="{{ asset('public/assets/admin/img/storefront-examples/vf-model-example.webp') }}"
                                                 alt="مثال صورة المودل"
                                                 class="sf-checker sf-example-thumb"
                                                 onclick="openExampleModal(this.src, 'مثال صورة المودل للقياس الافتراضي (VirtualFit)', 'صورة مفرغة للمودل من الرأس حتى منتصف الفخذ بارتفاع 1360px، الرأس متمركز بالأعلى (~25px) وبنفس الوقفة والإضاءة لجميع الألوان.')">
                                        </div>
                                        <div class="small flex-grow-1" style="min-width:220px;">
                                            <div class="fw-bold text-dark">{{ translate('مثال مثالي للقياس الافتراضي (صورتان متطابقتان):') }}</div>
                                            <div class="text-muted">{{ translate('1) القطعة مفرغة مربعة 800×800 · 2) المودل مفرغ بارتفاع 1360px من الرأس لمنتصف الفخذ بنفس الوقفة. اضغط للتكبير') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" onclick="resetVfForm()">{{ translate('جديد') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('حفظ القطعة') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TAB 3: SHOES --}}
    @if($tab === 'shoes')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('الأحذية على الرفوف (حد أقصى 10)') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-align-middle mb-0">
                        <thead class="thead-light"><tr><th>{{ translate('الحذاء') }}</th><th>{{ translate('الصور') }}</th><th>{{ translate('المرتبط') }}</th><th></th></tr></thead>
                        <tbody>
                        @forelse($shoes as $sh)
                            @php
                                $shColorsArr = is_array($sh->colors) ? $sh->colors : [];
                                $shResolvedColors = array_map(function($sc) {
                                    $sc['image_url'] = \App\Model\StorefrontShoeShelfItem::resolveMediaUrl($sc['image_path'] ?? '', 'storefront/shoes');
                                    return $sc;
                                }, $shColorsArr);
                                $shEditPayload = [
                                    'id' => $sh->id,
                                    'product_id' => $sh->product_id,
                                    'name' => $sh->name,
                                    'description' => $sh->description,
                                    'sizes' => is_array($sh->sizes) ? implode(',', $sh->sizes) : '39,40,41,42,43,44',
                                    'colors' => $shResolvedColors,
                                ];
                            @endphp
                            <tr>
                                <td><strong>{{ $sh->name }}</strong></td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        @foreach($shResolvedColors as $sc)
                                            @if(!empty($sc['image_url']))
                                                <img src="{{ $sc['image_url'] }}" alt="{{ $sc['name'] ?? '' }}" title="{{ $sc['name'] ?? '' }}" class="sf-checker rounded border" style="width:46px;height:28px;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, '{{ e($sh->name . ' - ' . ($sc['name'] ?? '')) }}', '')">
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td>{{ $sh->product ? '#'.$sh->product_id : 'تجريبي' }}</td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick='editShoe(@json($shEditPayload))'>{{ translate('تعديل') }}</button>
                                    <form action="{{ route('admin.storefront.shoes.delete', $sh->id) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">{{ translate('لا توجد أحذية مضافة بعد') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('إضافة حذاء للرف المضيء (معايرة 1400×800 تلقائية)') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.shoes.save') }}" method="POST" id="shForm">
                        @csrf
                        <input type="hidden" name="id" id="sh_id">
                        <input type="hidden" name="colors_json" id="sh_colors_json" value="[]">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('ربط بمنتج من الكتالوج') }}</label>
                                <select name="product_id" id="sh_product_id" class="form-control js-select2-custom">
                                    <option value="">-- {{ translate('اختياري') }} --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('اسم الحذاء') }} *</label>
                                <input type="text" name="name" id="sh_name" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="input-label">{{ translate('الوصف المختصر') }}</label>
                                <input type="text" name="description" id="sh_description" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('المقاسات (أوروبي)') }}</label>
                                <input type="text" name="sizes" id="sh_sizes" class="form-control" value="39,40,41,42,43,44">
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-3 bg-light">
                                    <strong>{{ translate('صورة جانبية شفافة للحذاء (المقدمة لليسار)') }}</strong>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4"><input type="text" id="sh_c_name" class="form-control" placeholder="أسود / بني"></div>
                                        <div class="col-md-2"><input type="color" id="sh_c_swatch" class="form-control form-control-color" value="#222222"></div>
                                        <div class="col-md-4"><input type="file" id="sh_c_file" accept="image/png,image/webp" class="form-control form-control-sm" onchange="previewVfFile(this, 'sh_file_preview', 'sh_file_empty')"></div>
                                        <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="addShoeColor()">+ {{ translate('رفع') }}</button></div>
                                        <div class="col-12">
                                            <div class="border rounded p-2 bg-white text-center">
                                                <div class="small text-muted mb-1">{{ translate('معاينة صورة الحذاء المختارة') }}</div>
                                                <div class="sf-checker rounded d-flex align-items-center justify-content-center mx-auto" style="height:105px;">
                                                    <img id="sh_file_preview" src="" alt="" class="d-none" style="max-height:96px;max-width:100%;object-fit:contain;">
                                                    <span id="sh_file_empty" class="small text-muted">{{ translate('لم يتم اختيار صورة') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="shColorsList" class="mt-3 d-flex flex-wrap gap-2"></div>
                                    <div class="sf-example-box d-flex align-items-center gap-3 mt-3">
                                        <img src="{{ asset('public/assets/admin/img/storefront-examples/shoe-example.webp') }}"
                                             alt="مثال صورة الحذاء"
                                             class="sf-checker sf-example-thumb-wide"
                                             onclick="openExampleModal(this.src, 'مثال صورة الحذاء (ShoeShelf 3D)', 'لقطة جانبية مفرغة على خلفية شفافة PNG/WebP، المقدمة متجهة لليسار، ويتم ضبط المقاس إلى 1400×800px مع محاذاة خط النعل عند 88%.')">
                                        <div class="small">
                                            <div class="fw-bold text-dark">{{ translate('مثال مثالي لصورة الحذاء:') }}</div>
                                            <div class="text-muted">{{ translate('لقطة جانبية مفرغة (المقدمة لليسار) · يتم توحيدها تلقائيًا إلى 1400×800px مع خط نعل ثابت · اضغط للتكبير') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" onclick="resetShoeForm()">{{ translate('جديد') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('حفظ الحذاء') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TAB 4: RAIL --}}
    @if($tab === 'rail')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('قطع علاقة الملابس (حد أقصى 10)') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-align-middle mb-0">
                        <thead class="thead-light"><tr><th>{{ translate('القطعة') }}</th><th>{{ translate('المرتبط') }}</th><th></th></tr></thead>
                        <tbody>
                        @forelse($rail as $r)
                            <tr>
                                <td><strong>{{ $r->name }}</strong></td>
                                <td>{{ $r->product ? '#'.$r->product_id : 'تجريبي' }}</td>
                                <td class="text-nowrap">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick='editRail(@json($r))'>{{ translate('تعديل') }}</button>
                                        <form action="{{ route('admin.storefront.rail.delete', $r->id) }}" method="POST" onsubmit="return confirm('حذف؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="{{ translate('حذف') }}"><i class="tio-delete-outlined"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">{{ translate('لا توجد قطع مضافة بعد') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ translate('إضافة / تعديل قطعة لعلاقة الملابس (3 زوايا WebP)') }}</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetRailForm()">{{ translate('جديد') }}</button>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.rail.save') }}" method="POST" id="railForm">
                        @csrf
                        <input type="hidden" name="id" id="rail_id">
                        <input type="hidden" name="front_image_path" id="rail_front_path" required>
                        <input type="hidden" name="back_image_path" id="rail_back_path" required>
                        <input type="hidden" name="side_image_path" id="rail_side_path" required>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('المنتج المرتبط') }}</label>
                                <select name="product_id" id="rail_product_id" class="form-control js-select2-custom">
                                    <option value="">-- {{ translate('اختياري') }} --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('اسم القطعة') }} *</label>
                                <input type="text" name="name" id="rail_name" class="form-control" required placeholder="قميص أوكسفورد سماوي">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">أمامي (Front)</label>
                                <input type="file" id="rail_front_file" accept="image/png,image/webp" class="form-control" onchange="uploadSimple(this, 'rail', 'rail_front_path', 'rail_front_preview')">
                                <div class="mt-2 sf-checker rounded p-1 text-center d-none" id="rail_front_preview_wrap" style="height:82px;display:flex;align-items:center;justify-content:center;">
                                    <img id="rail_front_preview" src="" alt="أمامي" style="max-height:74px;max-width:100%;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, 'الصورة الأمامية', '')">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">خلفي (Back)</label>
                                <input type="file" id="rail_back_file" accept="image/png,image/webp" class="form-control" onchange="uploadSimple(this, 'rail', 'rail_back_path', 'rail_back_preview')">
                                <div class="mt-2 sf-checker rounded p-1 text-center d-none" id="rail_back_preview_wrap" style="height:82px;display:flex;align-items:center;justify-content:center;">
                                    <img id="rail_back_preview" src="" alt="خلفي" style="max-height:74px;max-width:100%;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, 'الصورة الخلفية', '')">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">جانبي (Side)</label>
                                <input type="file" id="rail_side_file" accept="image/png,image/webp" class="form-control" onchange="uploadSimple(this, 'rail', 'rail_side_path', 'rail_side_preview')">
                                <div class="mt-2 sf-checker rounded p-1 text-center d-none" id="rail_side_preview_wrap" style="height:82px;display:flex;align-items:center;justify-content:center;">
                                    <img id="rail_side_preview" src="" alt="جانبي" style="max-height:74px;max-width:100%;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, 'الصورة الجانبية', '')">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="sf-example-box d-flex flex-wrap align-items-center gap-3">
                                    <div class="d-flex gap-2">
                                        <img src="{{ asset('public/assets/admin/img/storefront-examples/rail-front-example.webp') }}" alt="أمامي" class="sf-checker sf-example-thumb" onclick="openExampleModal(this.src, 'مثال زاوية أمامية (Rail Front)', 'صورة أمامية مفرغة على خلفية شفافة PNG/WebP، متمركزة في نفس الإطار مع الخلفية والجانبية.')">
                                        <img src="{{ asset('public/assets/admin/img/storefront-examples/rail-back-example.webp') }}" alt="خلفي" class="sf-checker sf-example-thumb" onclick="openExampleModal(this.src, 'مثال زاوية خلفية (Rail Back)', 'صورة خلفية مفرغة بنفس الأبعاد والتمركز لضمان دوران سلس 360°.')">
                                        <img src="{{ asset('public/assets/admin/img/storefront-examples/rail-side-example.webp') }}" alt="جانبي" class="sf-checker sf-example-thumb" onclick="openExampleModal(this.src, 'مثال زاوية جانبية (Rail Side)', 'صورة جانبية مفرغة بنفس ارتفاع الإطار وبخلفية شفافة PNG/WebP.')">
                                    </div>
                                    <div class="small flex-grow-1" style="min-width:220px;">
                                        <div class="fw-bold text-dark">{{ translate('مثال مثالي لزوايا العلّاقة الثلاث (أمامي · خلفي · جانبي):') }}</div>
                                        <div class="text-muted">{{ translate('3 صور مفرغة PNG/WebP بنفس الأبعاد والتمركز حتى تدور القطعة بسلاسة دون اهتزاز · اضغط للتكبير') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8"><label class="input-label">{{ translate('الوصف') }}</label><input type="text" name="description" id="rail_description" class="form-control"></div>
                            <div class="col-md-4"><label class="input-label">{{ translate('التفاصيل (مفصولة بفاصلة)') }}</label><input type="text" name="details" id="rail_details" class="form-control" placeholder="قطن 100%,قصة مريحة"></div>
                        </div>
                        <div class="mt-3 text-end"><button type="submit" class="btn btn-primary">{{ translate('حفظ على العلّاقة') }}</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TAB 5: CATEGORIES --}}
    @if($tab === 'categories')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('بطاقات الأقسام في العرض المنحني (WebGL)') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-align-middle mb-0">
                        <thead class="thead-light"><tr><th>{{ translate('القسم') }}</th><th>Slug</th><th></th></tr></thead>
                        <tbody>
                        @forelse($categoryCards as $cc)
                            <tr>
                                <td><strong>{{ $cc->name }}</strong></td>
                                <td><code>{{ $cc->slug }}</code></td>
                                <td class="text-nowrap">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick='editCategoryCard(@json($cc))'>{{ translate('تعديل') }}</button>
                                        <form action="{{ route('admin.storefront.categories.delete', $cc->id) }}" method="POST" onsubmit="return confirm('حذف؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="{{ translate('حذف') }}"><i class="tio-delete-outlined"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">{{ translate('لا توجد بطاقات مضافة بعد') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ translate('إضافة / تعديل بطاقة قسم') }}</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetCategoryForm()">{{ translate('جديد') }}</button>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.categories.save') }}" method="POST" id="catForm">
                        @csrf
                        <input type="hidden" name="id" id="cat_id">
                        <input type="hidden" name="image_path" id="cat_image_path" required>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('القسم في النظام') }}</label>
                                <select name="category_id" id="cat_category_id" class="form-control">
                                    <option value="">-- {{ translate('اختياري') }} --</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">#{{ $c->id }} — {{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label class="input-label">{{ translate('الاسم المعروض') }} *</label><input type="text" name="name" id="cat_name" class="form-control" required></div>
                            <div class="col-md-4"><label class="input-label">Slug *</label><input type="text" name="slug" id="cat_slug" class="form-control" required placeholder="men / shoes"></div>
                            <div class="col-md-4"><label class="input-label">{{ translate('تركيز X (%)') }}</label><input type="number" name="focus_x" id="cat_focus_x" class="form-control" value="50" min="0" max="100"></div>
                            <div class="col-md-4"><label class="input-label">{{ translate('تركيز Y (%)') }}</label><input type="number" name="focus_y" id="cat_focus_y" class="form-control" value="50" min="0" max="100"></div>
                            <div class="col-12">
                                <label class="input-label">{{ translate('صورة البطاقة (4:5)') }}</label>
                                <input type="file" id="cat_image_file" accept="image/*" class="form-control" onchange="uploadSimple(this, 'categories', 'cat_image_path', 'cat_image_preview')">
                                <div class="mt-2 sf-checker rounded p-1 text-center d-none" id="cat_image_preview_wrap" style="height:110px;display:flex;align-items:center;justify-content:center;">
                                    <img id="cat_image_preview" src="" alt="صورة البطاقة" style="max-height:100px;max-width:100%;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, 'صورة بطاقة القسم', '')">
                                </div>
                                <div class="sf-example-box d-flex align-items-center gap-3 mt-2">
                                    <img src="{{ asset('public/assets/admin/img/storefront-examples/category-example.webp') }}"
                                         alt="مثال بطاقة القسم"
                                         class="sf-checker sf-example-thumb"
                                         style="object-fit: cover;"
                                         onclick="openExampleModal(this.src, 'مثال صورة بطاقة القسم (نسبة 4:5)', 'صورة تحريرية عمودية بنسبة 4:5 (مثلاً 1000×1250px) بخلفية كاملة، مع تحديد نقطة التركيز X/Y لحماية الوجه أو القطعة عند القص.')">
                                    <div class="small">
                                        <div class="fw-bold text-dark">{{ translate('مثال مثالي لصورة القسم:') }}</div>
                                        <div class="text-muted">{{ translate('صورة عمودية بنسبة 4:5 (مثل 1000×1250px) · حدد تركيز X/Y على الوجه أو العنصر الأساسي · اضغط للتكبير') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-end"><button type="submit" class="btn btn-primary">{{ translate('حفظ البطاقة') }}</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TAB 6: TESTIMONIALS --}}
    @if($tab === 'testimonials')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('إعدادات عنوان قسم آراء العملاء') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.testimonials.settings') }}" method="POST">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="input-label">{{ translate('عنوان القسم') }} *</label>
                                <input type="text" name="testimonials_title" class="form-control" required value="{{ $closingSettings['testimonials_title'] ?? 'قالوا عن بن هويش' }}">
                            </div>
                            <div class="col-md-5">
                                <label class="input-label">{{ translate('الوصف الفرعي') }}</label>
                                <input type="text" name="testimonials_subtitle" class="form-control" value="{{ $closingSettings['testimonials_subtitle'] ?? 'آراء عملائنا الموثقة' }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">{{ translate('حفظ') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('آراء العملاء المضافة') }}</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('العميل') }}</th>
                                    <th>{{ translate('الرأي') }}</th>
                                    <th>{{ translate('التقييم') }}</th>
                                    <th>{{ translate('الحالة') }}</th>
                                    <th class="text-center">{{ translate('إجراء') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($testimonials ?? [] as $tm)
                                <tr>
                                    <td>
                                        <strong>{{ $tm['name'] ?? '' }}</strong><br>
                                        <small class="text-muted">{{ $tm['source'] ?? '' }}</small>
                                        @if(!empty($tm['verified']))
                                            <span class="badge badge-soft-success">{{ translate('موثق') }}</span>
                                        @endif
                                    </td>
                                    <td style="max-width:260px;"><small>{{ \Illuminate\Support\Str::limit($tm['quote'] ?? '', 90) }}</small></td>
                                    <td><span class="badge badge-soft-warning">{{ (int)($tm['rating'] ?? 5) }} ★</span></td>
                                    <td>
                                        <span class="badge {{ !empty($tm['visible']) ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                            {{ !empty($tm['visible']) ? translate('ظاهر') : translate('مخفي') }}
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='editTestimonial(@json($tm))'>{{ translate('تعديل') }}</button>
                                        <form action="{{ route('admin.storefront.testimonials.delete', $tm['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف هذا الرأي؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">{{ translate('لم تتم إضافة آراء مخصصة بعد — يعرض المتجر تقييمات المنتجات المفعلة تلقائيًا عند توفرها.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('إضافة / تعديل رأي عميل') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.testimonials.save') }}" method="POST" id="tmForm">
                        @csrf
                        <input type="hidden" name="id" id="tm_id">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="input-label">{{ translate('اسم العميل') }} *</label>
                                <input type="text" name="name" id="tm_name" class="form-control" required placeholder="مثال: أبو محمد">
                            </div>
                            <div class="col-md-7">
                                <label class="input-label">{{ translate('مصدر التقييم') }}</label>
                                <input type="text" name="source" id="tm_source" class="form-control" value="تقييم متجر بن هويش" placeholder="مثال: تقييم Google / واتساب">
                            </div>
                            <div class="col-md-5">
                                <label class="input-label">{{ translate('التقييم (نجوم)') }}</label>
                                <select name="rating" id="tm_rating" class="form-control">
                                    <option value="5" selected>5 نجوم</option>
                                    <option value="4">4 نجوم</option>
                                    <option value="3">3 نجوم</option>
                                    <option value="2">2 نجوم</option>
                                    <option value="1">1 نجمة</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="input-label">{{ translate('نص رأي العميل') }} *</label>
                                <textarea name="quote" id="tm_quote" rows="4" class="form-control" required placeholder="اكتب رأي العميل هنا..."></textarea>
                            </div>
                            <div class="col-6">
                                <label class="toggle-switch d-flex align-items-center">
                                    <input type="checkbox" name="verified" id="tm_verified" value="1" class="toggle-switch-input" checked>
                                    <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                    <span class="ms-2">{{ translate('تقييم موثق') }}</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="toggle-switch d-flex align-items-center">
                                    <input type="checkbox" name="visible" id="tm_visible" value="1" class="toggle-switch-input" checked>
                                    <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                    <span class="ms-2">{{ translate('ظاهر في الموقع') }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" onclick="resetTestimonialForm()">{{ translate('جديد') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('حفظ الرأي') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TAB 7: VIRTUAL TRY-ON (DECART ANYWEAR) --}}
    @if($tab === 'try-on')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ translate('إعدادات ميزة «جرّبها عليك» (Decart Anywear / Lucy VTON)') }}</h5>
                    <span class="badge badge-soft-primary">{{ translate('المنتجات المفعّلة:') }} {{ $tryOnEnabledCount ?? 0 }}</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.try-on.settings') }}" method="POST">
                        @csrf
                        <div class="d-flex align-items-center justify-content-between p-3 mb-4 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div>
                                <h6 class="mb-1">{{ translate('تفعيل ميزة «جرّبها عليك» في الموقع والتطبيق') }}</h6>
                                <small class="text-muted">{{ translate('يظهر الزر فقط للمنتجات التي تم تفعيل خيار التجربة الافتراضية لها من صفحة المنتج.') }}</small>
                            </div>
                            <label class="toggle-switch my-0">
                                <input type="checkbox" class="toggle-switch-input" name="enabled" value="1" {{ !empty($tryOnSettings['enabled']) ? 'checked' : '' }}>
                                <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                            </label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="input-label">{{ translate('مفتاح Decart API Key') }}</label>
                                <input type="password" name="api_key" class="form-control" autocomplete="new-password"
                                       placeholder="{{ !empty($tryOnSettings['api_key']) ? '•••••••••••••••••••••••• (محفوظ — اتركه فارغًا للإبقاء عليه)' : 'dct_...' }}">
                                <small class="text-muted">{{ translate('يبقى المفتاح على الخادم فقط، ويتم إصدار رمز مؤقت قصير العمر للعميل عند كل جلسة.') }}</small>
                            </div>

                            <div class="col-md-4">
                                <label class="input-label">{{ translate('نموذج الذكاء الاصطناعي') }}</label>
                                <select name="model" class="form-control">
                                    @foreach(['lucy-vton-latest' => 'Lucy VTON Latest (موصى به)', 'lucy-vton-3.6' => 'Lucy VTON 3.6', 'lucy-vton-3.5' => 'Lucy VTON 3.5'] as $mKey => $mLabel)
                                        <option value="{{ $mKey }}" {{ ($tryOnSettings['model'] ?? 'lucy-vton-latest') === $mKey ? 'selected' : '' }}>{{ $mLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="input-label">{{ translate('وضع السرعة والتكلفة') }}</label>
                                <select name="speed" class="form-control">
                                    <option value="standard" {{ ($tryOnSettings['speed'] ?? 'standard') === 'standard' ? 'selected' : '' }}>Standard 720p ($0.02 / ثانية)</option>
                                    <option value="fast" {{ ($tryOnSettings['speed'] ?? 'standard') === 'fast' ? 'selected' : '' }}>Fast Mode ($0.04 / ثانية)</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="input-label">{{ translate('الحد الأقصى للجلسة (ثانية)') }}</label>
                                <input type="number" name="max_session_seconds" class="form-control" min="15" max="180"
                                       value="{{ $tryOnSettings['max_session_seconds'] ?? 45 }}" required>
                                <small class="text-muted">{{ translate('بين 15 و 180 ثانية لضبط التكلفة.') }}</small>
                            </div>

                            <div class="col-md-12">
                                <label class="input-label">{{ translate('النطاقات المسموحة (Allowed Origins) — اختياري') }}</label>
                                <input type="text" name="allowed_origins" class="form-control" dir="ltr"
                                       value="{{ is_array($tryOnSettings['allowed_origins'] ?? null) ? implode(', ', $tryOnSettings['allowed_origins']) : ($tryOnSettings['allowed_origins'] ?? '') }}"
                                       placeholder="https://bin-huwesh.com, https://www.bin-huwesh.com">
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">{{ translate('حفظ إعدادات التجربة الافتراضية') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('إرشادات التشغيل والتكلفة') }}</h5></div>
                <div class="card-body d-flex flex-column gap-3">
                    <div class="p-3 rounded" style="background: #f8fafc;">
                        <h6 class="mb-1">{{ translate('تفعيل المنتجات') }}</h6>
                        <p class="small text-muted mb-2">{{ translate('لضمان أفضل جودة، فعّل الميزة فقط للقمصان والبولو والجواكت التي تعطي نتيجة واضحة من صفحة قائمة المنتجات أو تعديل المنتج.') }}</p>
                        <a href="{{ route('admin.product.list') }}" class="btn btn-sm btn-outline-primary">{{ translate('الذهاب إلى قائمة المنتجات') }}</a>
                    </div>
                    <div class="p-3 rounded" style="background: #fff8f8; border: 1px solid #f5c2c7;">
                        <h6 class="mb-1 text-danger">{{ translate('الخصوصية وإخلاء المسؤولية') }}</h6>
                        <p class="small text-muted mb-0">{{ translate('تظهر للعميل شاشة موافقة صريحة قبل تشغيل الكاميرا، مع عبارة «معاينة تقريبية للشكل، وليست توصية بالمقاس»، وتتوقف الكاميرا والاتصال فور إغلاق النافذة.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('script_2')
<script>
const UPLOAD_URL = "{{ route('admin.storefront.upload-media') }}";
const CSRF = "{{ csrf_token() }}";
const PRODUCTS_LIST = @json($products);

async function uploadBlob(blob, folder, filename) {
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('folder', folder);
    fd.append('file', blob, filename);
    const res = await fetch(UPLOAD_URL, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd
    });
    const rawText = (await res.text()).replace(/^\uFEFF/, '').trim();
    const data = JSON.parse(rawText);
    if (!res.ok || !data.ok) throw new Error(data.message || 'فشل رفع الصورة');
    return data;
}

const SF_MEDIA_BASE = "{{ rtrim(asset('storage/app/public/storefront'), '/') }}";
function sfMediaUrl(folder, path) {
    if (!path) return '';
    if (/^(https?:)?\/\//.test(path) || path.startsWith('/')) return path;
    return `${SF_MEDIA_BASE}/${folder}/${String(path).replace(/^\/+/, '')}`;
}
function setImgPreview(imgId, url) {
    const img = document.getElementById(imgId);
    const wrap = document.getElementById(imgId + '_wrap');
    if (!img) return;
    if (url) {
        img.src = url;
        img.classList.remove('d-none');
        if (wrap) wrap.classList.remove('d-none');
    } else {
        img.src = '';
        if (wrap) wrap.classList.add('d-none');
    }
}

async function uploadSimple(input, folder, targetId, previewId = null) {
    const file = input.files?.[0];
    if (!file) return;
    try {
        const res = await uploadBlob(file, folder, file.name);
        document.getElementById(targetId).value = res.path;
        if (previewId) setImgPreview(previewId, res.url || sfMediaUrl(folder, res.path));
        toastr.success('تم رفع الصورة بنجاح');
    } catch (e) {
        toastr.error(e.message);
    }
}

/* --- Look Image Processor (trim alpha + 1400px WebP) --- */
async function uploadProcessedLook(input) {
    const file = input.files?.[0];
    if (!file) return;
    try {
        const bmp = await createImageBitmap(file);
        const src = document.createElement('canvas');
        src.width = bmp.width; src.height = bmp.height;
        const ctx = src.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(bmp, 0, 0);
        const img = ctx.getImageData(0, 0, src.width, src.height);
        const d = img.data;
        let x0 = src.width, y0 = src.height, x1 = -1, y1 = -1;
        for (let y = 0; y < src.height; y++) {
            for (let x = 0; x < src.width; x++) {
                const i = (y * src.width + x) * 4 + 3;
                if (d[i] < 12) d[i] = 0;
                if (d[i] > 16) {
                    if (x < x0) x0 = x; if (x > x1) x1 = x;
                    if (y < y0) y0 = y; if (y > y1) y1 = y;
                }
            }
        }
        ctx.putImageData(img, 0, 0);
        if (x1 < 0) throw new Error('الصورة فارغة');
        const pad = Math.round((y1 - y0) * 0.02);
        const cx = Math.max(0, x0 - pad), cy = Math.max(0, y0 - pad);
        const cw = Math.min(src.width, x1 + pad + 1) - cx;
        const ch = Math.min(src.height, y1 + pad + 1) - cy;
        const scale = Math.min(1, 1400 / ch);
        const out = document.createElement('canvas');
        out.width = Math.round(cw * scale); out.height = Math.round(ch * scale);
        out.getContext('2d').drawImage(src, cx, cy, cw, ch, 0, 0, out.width, out.height);
        const blob = await new Promise(r => out.toBlob(r, 'image/webp', 0.86));
        const res = await uploadBlob(blob, 'looks', 'look.webp');
        document.getElementById('look_image_path').value = res.path;
        document.getElementById('look_image_width').value = out.width;
        document.getElementById('look_image_height').value = out.height;
        document.getElementById('lookPreviewImg').src = res.url;
        toastr.success(`تمت معالجة ورفع الصورة (${out.width}×${out.height})`);
    } catch (e) {
        toastr.error(e.message);
    }
}

/* --- Looks Hotspot Picker --- */
let lookItems = [];
function renderLookItems() {
    document.getElementById('look_items_json').value = JSON.stringify(lookItems);
    const dots = document.getElementById('lookDotsLayer');
    const box = document.getElementById('lookItemsContainer');
    if (!dots || !box) return;
    dots.innerHTML = lookItems.map((it, idx) =>
        `<span class="sf-hotspot-dot" style="left:${it.hotspot_x}%;top:${it.hotspot_y}%">${idx + 1}</span>`
    ).join('');
    box.innerHTML = lookItems.map((it, idx) => `
        <div class="border rounded p-2 bg-light">
            <div class="d-flex justify-content-between mb-1">
                <span class="badge badge-dark">#${idx + 1} (${it.hotspot_x}%, ${it.hotspot_y}%)</span>
                <button type="button" class="btn btn-xs btn-danger" onclick="lookItems.splice(${idx},1);renderLookItems()">×</button>
            </div>
            <div class="row g-1">
                <div class="col-6"><input class="form-control form-control-sm" value="${it.label}" placeholder="اسم القطعة" onchange="lookItems[${idx}].label=this.value;syncLookJson()"></div>
                <div class="col-6"><input class="form-control form-control-sm" value="${it.category}" placeholder="الفئة (جاكيت)" onchange="lookItems[${idx}].category=this.value;syncLookJson()"></div>
                <div class="col-12 mt-1">
                    <select class="form-control form-control-sm" onchange="lookItems[${idx}].product_id=this.value||null;syncLookJson()">
                        <option value="">-- ربط بمنتج حقيقي --</option>
                        ${PRODUCTS_LIST.map(p => `<option value="${p.id}" ${String(it.product_id)===String(p.id)?'selected':''}>#${p.id} — ${p.name}</option>`).join('')}
                    </select>
                </div>
            </div>
        </div>
    `).join('');
}
function syncLookJson() { document.getElementById('look_items_json').value = JSON.stringify(lookItems); }
function addLookItemRow(x = 50, y = 50) {
    lookItems.push({ label: 'قطعة جديدة', category: 'ملابس', hotspot_x: x, hotspot_y: y, product_id: null, sizes: ['S','M','L','XL'] });
    renderLookItems();
}
function placeHotspot(e) {
    const rect = e.currentTarget.getBoundingClientRect();
    const x = Math.round(((e.clientX - rect.left) / rect.width) * 1000) / 10;
    const y = Math.round(((e.clientY - rect.top) / rect.height) * 1000) / 10;
    addLookItemRow(x, y);
}
function editLook(l) {
    document.getElementById('look_id').value = l.id;
    document.getElementById('look_title').value = l.title || '';
    document.getElementById('look_subtitle').value = l.subtitle || '';
    document.getElementById('look_image_path').value = l.image_path || '';
    document.getElementById('look_image_width').value = l.image_width || 900;
    document.getElementById('look_image_height').value = l.image_height || 1400;
    document.getElementById('look_discount').value = l.whole_look_discount || '';
    document.getElementById('look_order').value = l.sort_order || 1;
    document.getElementById('look_published').checked = Boolean(l.published);
    const lookImgUrl = l.image_url || sfMediaUrl('looks', l.image_path);
    if (lookImgUrl) document.getElementById('lookPreviewImg').src = lookImgUrl;
    lookItems = (l.items || []).map(it => ({
        uid: it.uid, label: it.label, category: it.category,
        hotspot_x: it.hotspot_x, hotspot_y: it.hotspot_y,
        product_id: it.product_id, sizes: it.sizes || ['S','M','L','XL']
    }));
    renderLookItems();
}
function resetLookForm() {
    document.getElementById('lookForm').reset();
    document.getElementById('look_id').value = '';
    lookItems = [];
    renderLookItems();
}

/* --- VirtualFit Cutout Processor --- */
let vfColors = [];
function editVirtualFit(vf) {
    document.getElementById('vf_id').value = vf.id || '';
    const $prodSelect = $('#vf_product_id');
    if ($prodSelect.length) {
        $prodSelect.val(vf.product_id ? String(vf.product_id) : '').trigger('change');
    }
    document.getElementById('vf_name').value = vf.name || '';
    document.getElementById('vf_category_key').value = vf.category_key || 'shirts';
    document.getElementById('vf_category_name').value = vf.category_name || 'قمصان';
    document.getElementById('vf_sizes').value = vf.sizes || 'S,M,L,XL,XXL';
    vfColors = Array.isArray(vf.colors) ? vf.colors.map((c, i) => ({
        id: c.id || ('c' + (i + 1)),
        name: c.name || '',
        swatch: c.swatch || '#111111',
        garment_path: c.garment_path || '',
        model_path: c.model_path || '',
        garment_url: c.garment_url || sfMediaUrl('virtual-fit', c.garment_path),
        model_url: c.model_url || sfMediaUrl('virtual-fit', c.model_path)
    })) : [];
    renderVfColorsList();
    document.getElementById('vfForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function resetVfForm() {
    document.getElementById('vfForm')?.reset();
    document.getElementById('vf_id').value = '';
    const $prodSelect = $('#vf_product_id');
    if ($prodSelect.length) {
        $prodSelect.val('').trigger('change');
    }
    vfColors = [];
    renderVfColorsList();
    previewVfFile(document.getElementById('vf_c_garment'), 'vf_garment_preview', 'vf_garment_empty');
    previewVfFile(document.getElementById('vf_c_model'), 'vf_model_preview', 'vf_model_empty');
}
function previewVfFile(input, previewId, emptyId) {
    const img = document.getElementById(previewId);
    const empty = document.getElementById(emptyId);
    const file = input.files?.[0];
    if (!img || !empty) return;
    if (file) {
        img.src = URL.createObjectURL(file);
        img.classList.remove('d-none');
        empty.classList.add('d-none');
    } else {
        img.src = '';
        img.classList.add('d-none');
        empty.classList.remove('d-none');
    }
}
function renderVfColorsList() {
    document.getElementById('vf_colors_json').value = JSON.stringify(vfColors.map(({ garment_url, model_url, ...rest }) => rest));
    document.getElementById('vfColorsList').innerHTML = vfColors.map((c, idx) => `
        <div class="border rounded p-2 bg-white d-flex flex-column gap-2" style="min-width:170px;">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block border" style="width:14px;height:14px;background:${c.swatch};"></span>
                    <strong class="small">${c.name}</strong>
                </div>
                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" onclick="vfColors.splice(${idx},1);renderVfColorsList()" title="حذف">×</button>
            </div>
            <div class="d-flex gap-2">
                <div class="text-center flex-fill">
                    <div class="sf-checker rounded p-1" style="height:82px;display:flex;align-items:center;justify-content:center;">
                        <img src="${c.garment_url}" alt="القطعة" style="max-height:74px;max-width:100%;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, '${c.name} - صورة القطعة', '')">
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:10px;">القطعة</small>
                </div>
                <div class="text-center flex-fill">
                    <div class="sf-checker rounded p-1" style="height:82px;display:flex;align-items:center;justify-content:center;">
                        <img src="${c.model_url}" alt="المودل" style="max-height:74px;max-width:100%;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, '${c.name} - صورة المودل', '')">
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:10px;">المودل</small>
                </div>
            </div>
        </div>
    `).join('');
}
async function processCutoutCanvas(file, kind) {
    const bmp = await createImageBitmap(file);
    if (kind === 'model' && bmp.width >= bmp.height) {
        throw new Error('صورة المودل يجب أن تكون طولية (الارتفاع أكبر من العرض، مثل 640×1360 أو 524×1360)');
    }
    const limit = kind === 'model' ? 1360 / bmp.height : 800 / Math.max(bmp.width, bmp.height);
    const scale = Math.min(1, limit);
    const out = document.createElement('canvas');
    out.width = Math.round(bmp.width * scale); out.height = Math.round(bmp.height * scale);
    const ctx = out.getContext('2d');
    ctx.drawImage(bmp, 0, 0, out.width, out.height);
    return await new Promise(r => out.toBlob(r, 'image/webp', 0.9));
}
async function addVfColor() {
    const nameInput = document.getElementById('vf_c_name');
    const name = (nameInput.value || '').trim();
    const swatch = document.getElementById('vf_c_swatch').value;
    const gInput = document.getElementById('vf_c_garment');
    const mInput = document.getElementById('vf_c_model');
    const gFile = gInput.files?.[0];
    const mFile = mInput.files?.[0];
    if (!name || !gFile || !mFile) {
        toastr.error('أدخل اسم اللون وارفع صورتي القطعة والمودل');
        return false;
    }
    try {
        const gBlob = await processCutoutCanvas(gFile, 'garment');
        const mBlob = await processCutoutCanvas(mFile, 'model');
        const gUp = await uploadBlob(gBlob, 'virtual-fit', 'garment.webp');
        const mUp = await uploadBlob(mBlob, 'virtual-fit', 'model.webp');
        vfColors.push({ id: 'c' + (vfColors.length + 1), name, swatch, garment_path: gUp.path, model_path: mUp.path, garment_url: gUp.url, model_url: mUp.url });
        renderVfColorsList();
        nameInput.value = '';
        gInput.value = '';
        mInput.value = '';
        previewVfFile(gInput, 'vf_garment_preview', 'vf_garment_empty');
        previewVfFile(mInput, 'vf_model_preview', 'vf_model_empty');
        toastr.success('تم رفع لون المودل بنجاح');
        return true;
    } catch (e) {
        toastr.error(e.message);
        return false;
    }
}

document.getElementById('vfForm')?.addEventListener('submit', async function (e) {
    const gFile = document.getElementById('vf_c_garment')?.files?.[0];
    const mFile = document.getElementById('vf_c_model')?.files?.[0];
    const hasPendingColor = Boolean(gFile || mFile);

    if (hasPendingColor) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const origText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'جاري معالجة ورفع اللون...';
        }
        const ok = await addVfColor();
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
        }
        if (ok && vfColors.length > 0) {
            form.submit();
        }
        return;
    }

    if (vfColors.length === 0) {
        e.preventDefault();
        toastr.error('يجب إضافة لون واحد على الأقل مع صورتي القطعة والمودل.');
    }
});

/* --- ShoeShelf 1400x800 Processor --- */
let shColors = [];
function renderShColorsList() {
    document.getElementById('sh_colors_json').value = JSON.stringify(shColors.map(({ image_url, ...rest }) => rest));
    document.getElementById('shColorsList').innerHTML = shColors.map((c, idx) => `
        <div class="border rounded p-2 bg-white d-flex flex-column gap-2" style="min-width:150px;">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block border" style="width:14px;height:14px;background:${c.swatch};"></span>
                    <strong class="small">${c.name}</strong>
                </div>
                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" onclick="shColors.splice(${idx},1);renderShColorsList()" title="حذف">×</button>
            </div>
            <div class="sf-checker rounded p-1 d-flex align-items-center justify-content-center" style="height:72px;">
                <img src="${c.image_url}" alt="${c.name}" style="max-height:64px;max-width:100%;object-fit:contain;cursor:pointer;" onclick="openExampleModal(this.src, '${c.name}', '')">
            </div>
        </div>
    `).join('');
}
function editShoe(sh) {
    document.getElementById('sh_id').value = sh.id || '';
    const $prodSelect = $('#sh_product_id');
    if ($prodSelect.length) {
        $prodSelect.val(sh.product_id ? String(sh.product_id) : '').trigger('change');
    }
    document.getElementById('sh_name').value = sh.name || '';
    document.getElementById('sh_description').value = sh.description || '';
    document.getElementById('sh_sizes').value = sh.sizes || '39,40,41,42,43,44';
    shColors = Array.isArray(sh.colors) ? sh.colors.map((c, i) => ({
        id: c.id || ('c' + (i + 1)),
        name: c.name || '',
        swatch: c.swatch || '#222222',
        image_path: c.image_path || '',
        image_url: c.image_url || sfMediaUrl('shoes', c.image_path)
    })) : [];
    renderShColorsList();
    document.getElementById('shForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function editRail(r) {
    document.getElementById('rail_id').value = r.id || '';
    const $prodSelect = $('#rail_product_id');
    if ($prodSelect.length) {
        $prodSelect.val(r.product_id ? String(r.product_id) : '').trigger('change');
    }
    document.getElementById('rail_name').value = r.name || '';
    document.getElementById('rail_description').value = r.description || '';
    const details = Array.isArray(r.details) ? r.details.join(',') : (r.details || '');
    document.getElementById('rail_details').value = details;
    document.getElementById('rail_front_path').value = r.front_image_path || '';
    document.getElementById('rail_back_path').value = r.back_image_path || '';
    document.getElementById('rail_side_path').value = r.side_image_path || '';
    setImgPreview('rail_front_preview', r.front_image_url || sfMediaUrl('rail', r.front_image_path));
    setImgPreview('rail_back_preview', r.back_image_url || sfMediaUrl('rail', r.back_image_path));
    setImgPreview('rail_side_preview', r.side_image_url || sfMediaUrl('rail', r.side_image_path));
    document.getElementById('railForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function resetRailForm() {
    document.getElementById('railForm')?.reset();
    document.getElementById('rail_id').value = '';
    document.getElementById('rail_front_path').value = '';
    document.getElementById('rail_back_path').value = '';
    document.getElementById('rail_side_path').value = '';
    const $prodSelect = $('#rail_product_id');
    if ($prodSelect.length) {
        $prodSelect.val('').trigger('change');
    }
    setImgPreview('rail_front_preview', '');
    setImgPreview('rail_back_preview', '');
    setImgPreview('rail_side_preview', '');
}

function editCategoryCard(cc) {
    document.getElementById('cat_id').value = cc.id || '';
    document.getElementById('cat_category_id').value = cc.category_id ? String(cc.category_id) : '';
    document.getElementById('cat_name').value = cc.name || '';
    document.getElementById('cat_slug').value = cc.slug || '';
    document.getElementById('cat_focus_x').value = cc.focus_x ?? 50;
    document.getElementById('cat_focus_y').value = cc.focus_y ?? 50;
    document.getElementById('cat_image_path').value = cc.image_path || '';
    setImgPreview('cat_image_preview', cc.image_url || sfMediaUrl('categories', cc.image_path));
    document.getElementById('catForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function resetCategoryForm() {
    document.getElementById('catForm')?.reset();
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_image_path').value = '';
    setImgPreview('cat_image_preview', '');
}
function resetShoeForm() {
    document.getElementById('shForm')?.reset();
    document.getElementById('sh_id').value = '';
    const $prodSelect = $('#sh_product_id');
    if ($prodSelect.length) {
        $prodSelect.val('').trigger('change');
    }
    shColors = [];
    renderShColorsList();
    previewVfFile(document.getElementById('sh_c_file'), 'sh_file_preview', 'sh_file_empty');
}
async function addShoeColor() {
    const nameInput = document.getElementById('sh_c_name');
    const name = (nameInput.value || '').trim();
    const swatch = document.getElementById('sh_c_swatch').value;
    const fileInput = document.getElementById('sh_c_file');
    const file = fileInput.files?.[0];
    if (!name || !file) {
        toastr.error('أدخل اسم اللون وارفع صورة الحذاء');
        return false;
    }
    try {
        const bmp = await createImageBitmap(file);
        const src = document.createElement('canvas');
        src.width = bmp.width; src.height = bmp.height;
        const sctx = src.getContext('2d', { willReadFrequently: true });
        sctx.drawImage(bmp, 0, 0);
        const { data } = sctx.getImageData(0, 0, src.width, src.height);
        let x0 = src.width, y0 = src.height, x1 = -1, y1 = -1;
        for (let y = 0; y < src.height; y++) for (let x = 0; x < src.width; x++) {
            if (data[(y * src.width + x) * 4 + 3] > 8) {
                if (x < x0) x0 = x; if (x > x1) x1 = x; if (y < y0) y0 = y; if (y > y1) y1 = y;
            }
        }
        const bw = x1 - x0 + 1, bh = y1 - y0 + 1;
        const scale = Math.min(1180 / bw, 620 / bh);
        const dw = Math.round(bw * scale), dh = Math.round(bh * scale);
        const out = document.createElement('canvas');
        out.width = 1400; out.height = 800;
        out.getContext('2d').drawImage(src, x0, y0, bw, bh, Math.round((1400 - dw) / 2), 760 - dh, dw, dh);
        const blob = await new Promise(r => out.toBlob(r, 'image/webp', 0.9));
        const up = await uploadBlob(blob, 'shoes', 'shoe.webp');
        shColors.push({ id: 'c' + (shColors.length + 1), name, swatch, image_path: up.path, image_url: up.url });
        renderShColorsList();
        nameInput.value = '';
        fileInput.value = '';
        previewVfFile(fileInput, 'sh_file_preview', 'sh_file_empty');
        toastr.success('تمت معايرة الحذاء (1400×800) ورفعه');
        return true;
    } catch (e) {
        toastr.error(e.message);
        return false;
    }
}
document.getElementById('shForm')?.addEventListener('submit', async function (e) {
    const file = document.getElementById('sh_c_file')?.files?.[0];
    if (file) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const origText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'جاري معالجة ورفع الصورة...';
        }
        const ok = await addShoeColor();
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
        }
        if (ok && shColors.length > 0) {
            form.submit();
        }
        return;
    }
    if (shColors.length === 0) {
        e.preventDefault();
        toastr.error('يجب رفع صورة جانبية شفافة واحدة على الأقل للحذاء.');
    }
});

function editTestimonial(tm) {
    document.getElementById('tm_id').value = tm.id || '';
    document.getElementById('tm_name').value = tm.name || '';
    document.getElementById('tm_source').value = tm.source || 'تقييم متجر بن هويش';
    document.getElementById('tm_rating').value = String(tm.rating || 5);
    document.getElementById('tm_quote').value = tm.quote || '';
    document.getElementById('tm_verified').checked = Boolean(tm.verified);
    document.getElementById('tm_visible').checked = Boolean(tm.visible);
    document.getElementById('tmForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetTestimonialForm() {
    document.getElementById('tmForm')?.reset();
    document.getElementById('tm_id').value = '';
    document.getElementById('tm_rating').value = '5';
    document.getElementById('tm_verified').checked = true;
    document.getElementById('tm_visible').checked = true;
}

function openExampleModal(src, title, desc) {
    document.getElementById('sfExModalImg').src = src;
    document.getElementById('sfExModalTitle').textContent = title;
    document.getElementById('sfExModalDesc').textContent = desc;
    if (window.jQuery && jQuery.fn.modal) {
        jQuery('#sfExampleModal').modal('show');
    } else if (window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('sfExampleModal')).show();
    }
}
</script>

<div class="modal fade" id="sfExampleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sfExModalTitle"></h5>
                <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="sfExModalImg" src="" alt="" class="sf-checker img-fluid p-2" style="max-height: 65vh; object-fit: contain;">
                <p id="sfExModalDesc" class="text-muted mt-3 mb-0"></p>
            </div>
        </div>
    </div>
</div>
@endpush
