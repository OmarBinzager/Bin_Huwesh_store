@extends('layouts.admin.app')

@section('title', 'إدارة الأشكال الفريدة - ' . $product->name)

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-header-title mb-1">
                    <i class="tio-image mr-2"></i>
                    <span>إدارة الأشكال والنقشات الفريدة: {{ $product->name }}</span>
                </h1>
                <p class="text-muted mb-0">
                    كل شكل/نقشة يظهر كقطعة مستقلة بصورتها للعميل، وتختفي تلقائياً عند نفاد كميتها.
                </p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('admin.product.patterns.update-stock', [$product->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="_mode">
                    <input type="hidden" name="action" value="toggle_mode">
                    <button type="submit" class="btn {{ $product->isUniquePatterns() ? 'btn-success' : 'btn-outline-secondary' }}">
                        {{ $product->isUniquePatterns() ? 'نمط الأشكال الفريدة: مفعّل ✓' : 'تفعيل نمط الأشكال الفريدة' }}
                    </button>
                </form>
                <a href="{{ route('admin.product.list') }}" class="btn btn-outline-primary">
                    <i class="tio-back-ui mr-1"></i> العودة للمنتجات
                </a>
            </div>
        </div>

        @php($discrepancy = $product->erp_stock_discrepancy)
        @if($discrepancy)
            @if($discrepancy['status'] === 'missing_photos')
                <div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <div>
                        <strong>تنبيه مزامنة InfoSoft:</strong>
                        رصيد الـ ERP هو <strong>{{ $discrepancy['erp_stock'] }}</strong> قطعة، بينما إجمالي الأشكال المصوّرة في المتجر <strong>{{ $discrepancy['patterns_stock'] }}</strong> فقط.
                        يوجد <strong>{{ $discrepancy['diff'] }}</strong> قطعة بدون تصوير!
                    </div>
                </div>
            @elseif($discrepancy['status'] === 'sold_in_pos')
                <div class="alert alert-danger d-flex justify-content-between align-items-center">
                    <div>
                        <strong>تنبيه بيع من الكاشير (InfoSoft):</strong>
                        رصيد الـ ERP نقص إلى <strong>{{ $discrepancy['erp_stock'] }}</strong> قطعة، بينما المتجر ما زال يعرض <strong>{{ $discrepancy['patterns_stock'] }}</strong> قطعة.
                        يرجى الضغط على «تم البيع (-1)» أمام القطعة المباعة من المحل!
                    </div>
                </div>
            @else
                <div class="alert alert-soft-success">
                    <strong>متطابق مع InfoSoft:</strong> رصيد الـ ERP ({{ $discrepancy['erp_stock'] }}) يطابق إجمالي الأشكال المتاحة ({{ $discrepancy['patterns_stock'] }}).
                </div>
            @endif
        @endif

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">رفع جماعي سريع للأشكال الفريدة</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.product.patterns.bulk-upload', [$product->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-5 mb-3 mb-md-0">
                            <label class="input-label">اختر صور الأشكال / النقشات (يمكن اختيار عدة صور دفعة واحدة)</label>
                            <input type="file" name="pattern_images[]" class="form-control" accept="image/*" multiple required>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="input-label">بادئة التسمية</label>
                            <input type="text" name="prefix" class="form-control" value="نقشة" placeholder="مثال: نقشة أو P">
                        </div>
                        <div class="col-md-2 mb-3 mb-md-0">
                            <label class="input-label">الكمية الافتراضية</label>
                            <select name="default_stock" class="form-control">
                                <option value="1" selected>1 (قطعة وحيدة)</option>
                                <option value="2">2 (قطعتان)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="tio-upload mr-1"></i> رفع وإضافة
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    الأشكال المسجلة ({{ count($product->patterns_list) }}) — إجمالي المخزون المتاح: {{ $product->total_stock }}
                </h5>
            </div>
            <div class="card-body">
                @if(empty($product->patterns_list))
                    <div class="text-center py-5 text-muted">
                        لا توجد أشكال مضافة بعد. استخدم نموذج الرفع الجماعي أعلاه لإضافة صور القطع.
                    </div>
                @else
                    <div class="row">
                        @foreach($product->patterns_list as $pattern)
                            <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                                <div class="card h-100 {{ $pattern['is_sold'] ? 'border-danger opacity-75' : 'border' }}">
                                    <div class="position-relative" style="height: 220px; overflow: hidden; background: #f8f9fa;">
                                        <img src="{{ $pattern['image_url'] }}" alt="{{ $pattern['name'] }}" class="w-100 h-100" style="object-fit: cover;">
                                        <span class="badge {{ $pattern['is_sold'] ? 'badge-danger' : 'badge-success' }} position-absolute" style="top: 10px; right: 10px; font-size: 0.85rem;">
                                            {{ $pattern['is_sold'] ? 'نفد / تم البيع' : 'متاح: ' . $pattern['stock'] }}
                                        </span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>{{ $pattern['name'] }}</strong>
                                            <span class="text-muted small">{{ number_format($pattern['price']) }}</span>
                                        </div>
                                        <div class="d-flex gap-1 justify-content-between">
                                            <form action="{{ route('admin.product.patterns.update-stock', [$product->id]) }}" method="POST" class="flex-fill mr-1">
                                                @csrf
                                                <input type="hidden" name="type" value="{{ $pattern['type'] }}">
                                                <input type="hidden" name="action" value="decrement">
                                                <button type="submit" class="btn btn-sm btn-outline-warning btn-block" {{ $pattern['stock'] <= 0 ? 'disabled' : '' }}>
                                                    -1 تم البيع
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.product.patterns.update-stock', [$product->id]) }}" method="POST" class="mr-1">
                                                @csrf
                                                <input type="hidden" name="type" value="{{ $pattern['type'] }}">
                                                <input type="hidden" name="action" value="increment">
                                                <button type="submit" class="btn btn-sm btn-outline-success">+1</button>
                                            </form>
                                            <form action="{{ route('admin.product.patterns.update-stock', [$product->id]) }}" method="POST" onsubmit="return confirm('حذف هذا الشكل نهائياً؟')">
                                                @csrf
                                                <input type="hidden" name="type" value="{{ $pattern['type'] }}">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
