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
                                            <button class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
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
                                <small class="text-muted">{{ translate('يتم قص الهوامش الشفافة وتوحيد الارتفاع إلى 1400px بصيغة WebP تلقائيًا في المتصفح.') }}</small>
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
                            <tr>
                                <td><strong>{{ $vf->name }}</strong><br><small class="text-muted">{{ $vf->product ? 'مرتبط: #'.$vf->product_id : 'غير مرتبط بمنتج' }}</small></td>
                                <td>{{ $vf->category_name }}</td>
                                <td>{{ count($vf->colors ?? []) }}</td>
                                <td>
                                    <form action="{{ route('admin.storefront.virtual-fit.delete', $vf->id) }}" method="POST" onsubmit="return confirm('حذف؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
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
                    <form action="{{ route('admin.storefront.virtual-fit.save') }}" method="POST">
                        @csrf
                        <input type="hidden" name="colors_json" id="vf_colors_json" value="[]">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('ربط بمنتج من الكتالوج') }}</label>
                                <select name="product_id" class="form-control js-select2-custom">
                                    <option value="">-- {{ translate('اختياري: ربط بمنتج حقيقي') }} --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->name }} ({{ number_format($p->price) }} ر.ي)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('اسم القطعة في العرض') }} *</label>
                                <input type="text" name="name" class="form-control" required placeholder="تيشيرت بولو قطني">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('معرف التبويب (إنجليزي)') }} *</label>
                                <input type="text" name="category_key" class="form-control" required value="shirts" placeholder="shirts / jackets">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('اسم التبويب') }} *</label>
                                <input type="text" name="category_name" class="form-control" required value="قمصان">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('المقاسات الافتراضية') }}</label>
                                <input type="text" name="sizes" class="form-control" value="S,M,L,XL,XXL">
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-3 bg-light">
                                    <strong>{{ translate('إضافة لون (صورتان شفافتـان WebP: القطعة + المودل بنفس الوقفة)') }}</strong>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4"><input type="text" id="vf_c_name" class="form-control" placeholder="اسم اللون (أبيض)"></div>
                                        <div class="col-md-2"><input type="color" id="vf_c_swatch" class="form-control form-control-color" value="#111111"></div>
                                        <div class="col-md-3"><label class="small">صورة القطعة</label><input type="file" id="vf_c_garment" accept="image/png,image/webp" class="form-control form-control-sm"></div>
                                        <div class="col-md-3"><label class="small">صورة المودل (1360px)</label><input type="file" id="vf_c_model" accept="image/png,image/webp" class="form-control form-control-sm"></div>
                                        <div class="col-12"><button type="button" class="btn btn-sm btn-outline-primary" onclick="addVfColor()">+ {{ translate('معالجة ورفع اللون') }}</button></div>
                                    </div>
                                    <div id="vfColorsList" class="mt-2 d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-end"><button type="submit" class="btn btn-primary">{{ translate('حفظ القطعة') }}</button></div>
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
                        <thead class="thead-light"><tr><th>{{ translate('الحذاء') }}</th><th>{{ translate('المرتبط') }}</th><th></th></tr></thead>
                        <tbody>
                        @forelse($shoes as $sh)
                            <tr>
                                <td><strong>{{ $sh->name }}</strong></td>
                                <td>{{ $sh->product ? '#'.$sh->product_id : 'تجريبي' }}</td>
                                <td>
                                    <form action="{{ route('admin.storefront.shoes.delete', $sh->id) }}" method="POST" onsubmit="return confirm('حذف؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">{{ translate('لا توجد أحذية مضافة بعد') }}</td></tr>
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
                    <form action="{{ route('admin.storefront.shoes.save') }}" method="POST">
                        @csrf
                        <input type="hidden" name="colors_json" id="sh_colors_json" value="[]">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('ربط بمنتج من الكتالوج') }}</label>
                                <select name="product_id" class="form-control js-select2-custom">
                                    <option value="">-- {{ translate('اختياري') }} --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('اسم الحذاء') }} *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="input-label">{{ translate('الوصف المختصر') }}</label>
                                <input type="text" name="description" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">{{ translate('المقاسات (أوروبي)') }}</label>
                                <input type="text" name="sizes" class="form-control" value="39,40,41,42,43,44">
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-3 bg-light">
                                    <strong>{{ translate('صورة جانبية شفافة للحذاء (المقدمة لليسار)') }}</strong>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4"><input type="text" id="sh_c_name" class="form-control" placeholder="أسود / بني"></div>
                                        <div class="col-md-2"><input type="color" id="sh_c_swatch" class="form-control form-control-color" value="#222222"></div>
                                        <div class="col-md-4"><input type="file" id="sh_c_file" accept="image/png,image/webp" class="form-control form-control-sm"></div>
                                        <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="addShoeColor()">+ {{ translate('رفع') }}</button></div>
                                    </div>
                                    <div id="shColorsList" class="mt-2 d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-end"><button type="submit" class="btn btn-primary">{{ translate('حفظ الحذاء') }}</button></div>
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
                                <td>
                                    <form action="{{ route('admin.storefront.rail.delete', $r->id) }}" method="POST" onsubmit="return confirm('حذف؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                    </form>
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
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('إضافة قطعة لعلاقة الملابس (3 زوايا WebP)') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.rail.save') }}" method="POST">
                        @csrf
                        <input type="hidden" name="front_image_path" id="rail_front_path" required>
                        <input type="hidden" name="back_image_path" id="rail_back_path" required>
                        <input type="hidden" name="side_image_path" id="rail_side_path" required>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('المنتج المرتبط') }}</label>
                                <select name="product_id" class="form-control js-select2-custom">
                                    <option value="">-- {{ translate('اختياري') }} --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('اسم القطعة') }} *</label>
                                <input type="text" name="name" class="form-control" required placeholder="قميص أوكسفورد سماوي">
                            </div>
                            <div class="col-md-4"><label class="input-label">أمامي (Front)</label><input type="file" accept="image/png,image/webp" class="form-control" onchange="uploadSimple(this, 'rail', 'rail_front_path')"></div>
                            <div class="col-md-4"><label class="input-label">خلفي (Back)</label><input type="file" accept="image/png,image/webp" class="form-control" onchange="uploadSimple(this, 'rail', 'rail_back_path')"></div>
                            <div class="col-md-4"><label class="input-label">جانبي (Side)</label><input type="file" accept="image/png,image/webp" class="form-control" onchange="uploadSimple(this, 'rail', 'rail_side_path')"></div>
                            <div class="col-md-8"><label class="input-label">{{ translate('الوصف') }}</label><input type="text" name="description" class="form-control"></div>
                            <div class="col-md-4"><label class="input-label">{{ translate('التفاصيل (مفصولة بفاصلة)') }}</label><input type="text" name="details" class="form-control" placeholder="قطن 100%,قصة مريحة"></div>
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
                                <td>
                                    <form action="{{ route('admin.storefront.categories.delete', $cc->id) }}" method="POST" onsubmit="return confirm('حذف؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                    </form>
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
                <div class="card-header"><h5 class="card-title mb-0">{{ translate('إضافة بطاقة قسم') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.storefront.categories.save') }}" method="POST">
                        @csrf
                        <input type="hidden" name="image_path" id="cat_image_path" required>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="input-label">{{ translate('القسم في النظام') }}</label>
                                <select name="category_id" class="form-control">
                                    <option value="">-- {{ translate('اختياري') }} --</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">#{{ $c->id }} — {{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label class="input-label">{{ translate('الاسم المعروض') }} *</label><input type="text" name="name" class="form-control" required></div>
                            <div class="col-md-4"><label class="input-label">Slug *</label><input type="text" name="slug" class="form-control" required placeholder="men / shoes"></div>
                            <div class="col-md-4"><label class="input-label">{{ translate('تركيز X (%)') }}</label><input type="number" name="focus_x" class="form-control" value="50" min="0" max="100"></div>
                            <div class="col-md-4"><label class="input-label">{{ translate('تركيز Y (%)') }}</label><input type="number" name="focus_y" class="form-control" value="50" min="0" max="100"></div>
                            <div class="col-12"><label class="input-label">{{ translate('صورة البطاقة (4:5)') }}</label><input type="file" accept="image/*" class="form-control" onchange="uploadSimple(this, 'categories', 'cat_image_path')"></div>
                        </div>
                        <div class="mt-3 text-end"><button type="submit" class="btn btn-primary">{{ translate('حفظ البطاقة') }}</button></div>
                    </form>
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
    const res = await fetch(UPLOAD_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (!res.ok || !data.ok) throw new Error(data.message || 'فشل رفع الصورة');
    return data;
}

async function uploadSimple(input, folder, targetId) {
    const file = input.files?.[0];
    if (!file) return;
    try {
        const res = await uploadBlob(file, folder, file.name);
        document.getElementById(targetId).value = res.path;
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
    if (l.image_url) document.getElementById('lookPreviewImg').src = l.image_url;
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
async function processCutoutCanvas(file, kind) {
    const bmp = await createImageBitmap(file);
    const ratio = bmp.width / bmp.height;
    if (kind === 'model' && (ratio < 0.3 || ratio > 0.5)) throw new Error('صورة المودل يجب أن تكون طولية (640×1360 أو 524×1360)');
    const limit = kind === 'model' ? 1360 / bmp.height : 800 / Math.max(bmp.width, bmp.height);
    const scale = Math.min(1, limit);
    const out = document.createElement('canvas');
    out.width = Math.round(bmp.width * scale); out.height = Math.round(bmp.height * scale);
    const ctx = out.getContext('2d');
    ctx.drawImage(bmp, 0, 0, out.width, out.height);
    return await new Promise(r => out.toBlob(r, 'image/webp', 0.9));
}
async function addVfColor() {
    const name = document.getElementById('vf_c_name').value.trim();
    const swatch = document.getElementById('vf_c_swatch').value;
    const gFile = document.getElementById('vf_c_garment').files?.[0];
    const mFile = document.getElementById('vf_c_model').files?.[0];
    if (!name || !gFile || !mFile) return toastr.error('أدخل اسم اللون وارفع الصورتين');
    try {
        const gBlob = await processCutoutCanvas(gFile, 'garment');
        const mBlob = await processCutoutCanvas(mFile, 'model');
        const gUp = await uploadBlob(gBlob, 'virtual-fit', 'garment.webp');
        const mUp = await uploadBlob(mBlob, 'virtual-fit', 'model.webp');
        vfColors.push({ id: 'c' + (vfColors.length + 1), name, swatch, garment_path: gUp.path, model_path: mUp.path });
        document.getElementById('vf_colors_json').value = JSON.stringify(vfColors);
        document.getElementById('vfColorsList').innerHTML = vfColors.map(c => `<span class="badge badge-soft-dark p-2">${c.name}</span>`).join('');
        toastr.success('تم رفع لون المودل بنجاح');
    } catch (e) { toastr.error(e.message); }
}

/* --- ShoeShelf 1400x800 Processor --- */
let shColors = [];
async function addShoeColor() {
    const name = document.getElementById('sh_c_name').value.trim();
    const swatch = document.getElementById('sh_c_swatch').value;
    const file = document.getElementById('sh_c_file').files?.[0];
    if (!name || !file) return toastr.error('أدخل اسم اللون وارفع صورة الحذاء');
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
        shColors.push({ id: 'c' + (shColors.length + 1), name, swatch, image_path: up.path });
        document.getElementById('sh_colors_json').value = JSON.stringify(shColors);
        document.getElementById('shColorsList').innerHTML = shColors.map(c => `<span class="badge badge-soft-dark p-2">${c.name}</span>`).join('');
        toastr.success('تمت معايرة الحذاء (1400×800) ورفعه');
    } catch (e) { toastr.error(e.message); }
}
</script>
@endpush
