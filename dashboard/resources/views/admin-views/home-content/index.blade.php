@extends('layouts.admin.app')

@section('title', translate('محتوى الرئيسية'))

@push('css_or_js')
<style>
    /* Bin Huwesh Brand System */
    :root {
        --bh-primary: #A71927;
        --bh-primary-hover: #8C1420;
        --bh-dark: #171717;
        --bh-bg-light: #F9F9F8;
        --bh-border: #E5E7EB;
    }

    .bh-card {
        background: #FFFFFF;
        border: 1px solid var(--bh-border);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    /* Segmented Controls */
    .segmented-toggle-group {
        display: inline-flex;
        background: #F3F4F6;
        padding: 4px;
        border-radius: 8px;
        border: 1px solid var(--bh-border);
        gap: 4px;
    }
    .segmented-btn {
        border: none;
        padding: 6px 18px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        background: transparent;
        color: #4B5563;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .segmented-btn.active {
        background: var(--bh-dark);
        color: #FFFFFF !important;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }
    .segmented-btn.active i {
        color: #FFFFFF !important;
    }

    /* Web Style Selector Cards */
    .style-option-card {
        border: 2px solid var(--bh-border);
        border-radius: 10px;
        padding: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #FAFAFA;
        position: relative;
    }
    .style-option-card:hover {
        border-color: #D1D5DB;
        background: #FFFFFF;
    }
    .style-option-card.active {
        border-color: var(--bh-primary) !important;
        background: #FDF2F2 !important;
    }
    .style-option-card.active .style-check-badge {
        display: flex !important;
    }
    .style-check-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--bh-primary);
        color: #FFFFFF;
        font-size: 11px;
        display: none;
        align-items: center;
        justify-content: center;
    }
    .style-diagram-preview {
        height: 52px;
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
        margin-bottom: 8px;
        overflow: hidden;
        position: relative;
    }

    /* Upload Dropzones */
    .hero-upload-dropzone {
        border: 2px dashed #D1D5DB;
        background: #F9FAFB;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        min-height: 110px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-bottom: 0;
        user-select: none;
    }
    .hero-upload-dropzone:hover, .hero-upload-dropzone.drag-over {
        border-color: var(--bh-primary) !important;
        background-color: #FDF2F2 !important;
    }

    /* Mobile Mockup Frame */
    .mobile-mockup-wrapper {
        position: sticky;
        top: 85px;
    }
    .mobile-frame {
        width: 100%;
        max-width: 350px;
        background: #FFFFFF;
        border: 1px solid var(--bh-border);
        border-radius: 32px;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.09);
        margin: 0 auto;
        position: relative;
    }
    .mobile-notch {
        width: 115px;
        height: 18px;
        background: #171717;
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
        z-index: 20;
    }
    .mobile-screen-content {
        background: #FFFFFF;
        min-height: 520px;
        position: relative;
    }
    .mobile-hero-media {
        width: 100%;
        height: 250px;
        object-fit: cover;
        display: block;
        background: #F3F4F6;
    }
    .mobile-hero-details {
        padding: 24px 20px 20px;
        text-align: right;
    }
    .mobile-hero-title {
        font-size: 22px;
        font-weight: 800;
        color: #171717;
        line-height: 1.3;
        margin-bottom: 8px;
    }
    .mobile-hero-desc {
        font-size: 13px;
        color: #6B7280;
        line-height: 1.5;
        margin-bottom: 18px;
    }
    .mobile-hero-btn {
        display: inline-block;
        background: var(--bh-primary);
        color: #FFFFFF !important;
        font-size: 13px;
        font-weight: 700;
        padding: 10px 24px;
        border-radius: 6px;
        text-decoration: none;
        transition: opacity 0.2s;
    }
    .mobile-floating-product {
        margin-top: 24px;
        background: #FFFFFF;
        border: 1px solid #F3F4F6;
        border-radius: 10px;
        padding: 10px 14px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        transition: all 0.3s ease;
    }

    /* Desktop Browser Mockup Frame */
    .desktop-frame {
        width: 100%;
        background: #FFFFFF;
        border: 1px solid var(--bh-border);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
    }
    .browser-header-bar {
        background: #F3F4F6;
        padding: 8px 14px;
        border-bottom: 1px solid var(--bh-border);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .browser-dots {
        display: flex;
        gap: 6px;
    }
    .browser-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .browser-url-bar {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
        padding: 3px 12px;
        font-size: 11px;
        color: #6B7280;
        flex-grow: 1;
        max-width: 280px;
        direction: ltr;
        text-align: left;
    }

    /* Exact Desktop Navbar Replica from Screenshot */
    .desktop-store-topbar {
        height: 3px;
        background: var(--bh-primary);
        width: 100%;
    }
    .desktop-store-header {
        padding: 12px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FFFFFF;
        border-bottom: 1px solid #F3F4F6;
    }
    .desktop-header-search {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
        padding: 5px 12px;
        font-size: 11px;
        color: #9CA3AF;
        width: 160px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .desktop-logo-box {
        text-align: center;
    }
    .desktop-store-menu {
        padding: 8px 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        border-bottom: 1px solid #F3F4F6;
        background: #FFFFFF;
        font-size: 11px;
        font-weight: 600;
        color: #4B5563;
    }
    .desktop-menu-item.active {
        color: var(--bh-primary);
        position: relative;
        font-weight: 700;
    }
    .desktop-menu-item.active::after {
        content: '';
        position: absolute;
        bottom: -9px;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--bh-primary);
    }

    /* Desktop Hero Styles Visibility & Isolation */
    .desktop-style-view.d-none {
        display: none !important;
    }

    /* 1. Split Style: Strict 50% Text on Right + 50% Media on Left in RTL */
    .desktop-hero-split {
        display: flex;
        flex-direction: row;
        width: 100%;
        height: 330px;
        background: #FFFFFF;
        overflow: hidden;
    }
    .desktop-hero-split-content {
        flex: 0 0 50%;
        width: 50%;
        max-width: 50%;
        height: 100%;
        background: #F9F9F8;
        padding: 35px 28px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: right;
    }
    .desktop-hero-split-media-col {
        flex: 0 0 50%;
        width: 50%;
        max-width: 50%;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .desktop-hero-split-media {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* 2. Full Cover Style */
    .desktop-hero-cover {
        position: relative;
        width: 100%;
        height: 330px;
        background: #171717;
        overflow: hidden;
    }
    .desktop-hero-cover-media {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        opacity: 0.9;
        display: block;
    }
    .desktop-hero-cover-overlay {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: linear-gradient(to left, rgba(23,23,23,0.85) 0%, rgba(23,23,23,0.4) 60%, rgba(23,23,23,0.1) 100%);
        padding: 35px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: right;
        color: #FFFFFF;
    }

    /* 3. Framed Card Style */
    .desktop-hero-framed {
        padding: 16px;
        background: #FAF9F6;
        height: 330px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .desktop-hero-framed-box {
        position: relative;
        width: 100%;
        height: 100%;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
    .desktop-hero-framed-media {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block;
    }
    .desktop-hero-framed-glass {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 10px;
        padding: 18px 22px;
        max-width: 380px;
        text-align: right;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    /* 4. Minimal Centered Style */
    .desktop-hero-minimal {
        position: relative;
        width: 100%;
        height: 330px;
        background: #171717;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .desktop-hero-minimal-media {
        position: absolute;
        top: 0;
        left: 0;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        opacity: 0.75;
        display: block;
    }
    .desktop-hero-minimal-content {
        position: relative;
        z-index: 5;
        max-width: 500px;
        padding: 20px;
        color: #FFFFFF;
    }

    /* Floating Product Card Component for Desktop Styles */
    .desktop-floating-product-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 10px;
        padding: 8px 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .desktop-floating-product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
    }
    .desktop-floating-product-card .desktopProductImg {
        width: 44px;
        height: 44px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #E5E7EB;
        flex-shrink: 0;
    }

    /* Sections Order & Visibility Management Styles */
    .sections-list-container {
        width: 100%;
        display: flex;
        flex-direction: column;
    }
    .section-row-item {
        width: 100% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 16px 20px !important;
        border-bottom: 1px solid #F3F4F6;
        background: #FFFFFF;
        transition: background 0.15s ease, transform 0.15s ease;
        user-select: none;
    }
    .section-row-item:last-child {
        border-bottom: none;
    }
    .section-row-item:hover {
        background: #F9FAFB;
    }
    .section-row-item.dragging {
        opacity: 0.45;
        background: #EFF6FF !important;
        border: 2px dashed #3B82F6 !important;
    }
    .section-drag-handle {
        cursor: grab;
        color: #9CA3AF;
        display: inline-flex;
        align-items: center;
        padding: 6px;
        border-radius: 4px;
        transition: color 0.15s, background 0.15s;
    }
    .section-drag-handle:hover {
        color: #171717;
        background: #F3F4F6;
    }
    .section-drag-handle:active {
        cursor: grabbing;
    }
    .btn-order-arrow {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #E5E7EB;
        background: #FFFFFF;
        color: #4B5563;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.15s ease;
    }
    .btn-order-arrow:hover:not(:disabled) {
        background: #F3F4F6;
        color: #111827;
        border-color: #D1D5DB;
    }
    .btn-order-arrow:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .order-badge {
        background: #F3F4F6;
        color: #374151;
        font-size: 12px;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 6px;
        min-width: 28px;
        text-align: center;
        display: inline-block;
    }

    /* Custom Switch Toggle */
    .switch-toggle-custom {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        margin-bottom: 0;
        flex-shrink: 0;
    }
    .switch-toggle-custom input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #D1D5DB;
        transition: 0.3s;
        border-radius: 24px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    input:checked + .toggle-slider {
        background-color: #10B981;
    }
    input:checked + .toggle-slider:before {
        transform: translateX(20px);
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <form action="{{ route('admin.home-content.update') }}" method="POST" enctype="multipart/form-data" id="homeContentForm">
        @csrf

        <!-- Top Header & Action -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-1 font-size-12">
                        <li class="breadcrumb-item text-muted">{{ translate('إدارة المتجر') }}</li>
                        <li class="breadcrumb-item active text-dark font-weight-bold" aria-current="page">{{ translate('محتوى الرئيسية') }}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title mb-1" style="font-weight: 800; font-size: 26px; color: #171717;">
                    {{ translate('محتوى الرئيسية') }}
                </h1>
                <small class="text-muted font-size-13">{{ translate('بن هويش • لوحة العمليات') }}</small>
            </div>

            <div>
                <button type="submit" class="btn text-white px-4 py-2 font-weight-bold shadow-xs" style="background-color: #A71927; border-radius: 6px; font-size: 14px;">
                    <i class="tio-save mr-1"></i> {{ translate('حفظ ومعاينة') }}
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Live Mockup Previews (Mobile & Desktop) -->
            <div class="col-lg-5 col-xl-5 order-lg-1 order-2">
                <div class="mobile-mockup-wrapper">
                    <!-- Preview Mode Switcher (Mobile vs Desktop) -->
                    <div class="d-flex align-items-center justify-content-between mb-3 bg-white p-2 border rounded shadow-xs">
                        <span class="font-weight-bold font-size-13 text-dark d-flex align-items-center gap-1">
                            <i class="tio-visible-outlined text-primary"></i>
                            <span>{{ translate('المعاينة المباشرة') }}</span>
                        </span>
                        
                        <div class="segmented-toggle-group">
                            <button type="button" class="segmented-btn" id="btnPreviewMobile" title="{{ translate('معاينة الموبايل') }}">
                                <i class="tio-mobile"></i> {{ translate('موبايل') }}
                            </button>
                            <button type="button" class="segmented-btn active" id="btnPreviewDesktop" title="{{ translate('معاينة الويب ديسكتوب') }}">
                                <i class="tio-laptop"></i> {{ translate('ديسكتوب') }}
                            </button>
                        </div>
                    </div>

                    <!-- 1. MOBILE PHONE MOCKUP -->
                    <div id="mobileMockupContainer" class="d-none">
                        <div class="mobile-frame">
                            <div class="mobile-notch"></div>

                            <div class="mobile-screen-content">
                                <div class="position-relative" id="mobileHeroMediaContainer">
                                    <img id="mobilePreviewImg" src="{{ $heroData['image_url'] ?? asset('public/assets/admin/img/bin-huwesh-hero.jpg') }}" class="mobile-hero-media {{ ($heroData['media_type'] ?? 'image') === 'image' ? '' : 'd-none' }}" alt="Hero Mobile Preview">
                                    
                                    <video id="mobilePreviewVid" src="{{ $heroData['video_url'] ?? '' }}" poster="{{ $heroData['video_poster_url'] ?? '' }}" class="mobile-hero-media {{ ($heroData['media_type'] ?? 'image') === 'video' ? '' : 'd-none' }}" autoplay loop muted playsinline></video>
                                </div>

                                <div class="mobile-hero-details">
                                    <small class="text-muted font-size-11 font-weight-bold text-uppercase d-block mb-1 previewEditionTagText">{{ $heroData['edition_tag'] ?? 'THE NEW EDIT / 01' }}</small>
                                    <h2 class="mobile-hero-title" id="mobilePreviewTitle">{{ $heroData['title'] ?? 'تفاصيل تصنع حضورك' }}</h2>
                                    <p class="mobile-hero-desc" id="mobilePreviewDesc">{{ $heroData['description'] ?? 'تشكيلة جديدة. اختيارات تشبهك.' }}</p>
                                    
                                    <a href="javascript:" class="mobile-hero-btn shadow-xs" id="mobilePreviewBtn">
                                        {{ $heroData['button_text'] ?? 'اكتشف التشكيلة' }}
                                    </a>

                                    <!-- Floating Product Card (Mobile) -->
                                    <div class="mobile-floating-product {{ !empty($heroData['show_featured_product']) ? '' : 'd-none' }}" id="mobileFloatingCard">
                                        <div class="text-right">
                                            <div class="font-weight-bold font-size-13 text-dark mb-1" id="mobileProductTitle">
                                                {{ $heroData['featured_product_title'] ?? 'بولو محبوك' }}
                                            </div>
                                            <div class="text-muted font-size-11 mb-1" id="mobileProductVariant">
                                                {{ $heroData['featured_product_subtitle'] ?? 'زيتي داكن / M' }}
                                            </div>
                                            <div class="font-weight-bold text-dark font-size-12" id="mobileProductPrice">
                                                {{ $heroData['featured_product_price'] ?? '12,000 ر.ي' }}
                                            </div>
                                        </div>
                                        <img id="mobileProductImg" src="{{ $heroData['featured_product_image'] ?? asset('public/assets/admin/img/bin-huwesh-hero.jpg') }}" style="width: 52px; height: 52px; object-fit: cover; border-radius: 8px; border: 1px solid #E5E7EB;" alt="Product Thumb">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. DESKTOP BROWSER MOCKUP (Exact Layout from User Screenshot) -->
                    <div id="desktopMockupContainer">
                        <div class="desktop-frame">
                            <!-- Top Burgundy Accent Line -->
                            <div class="desktop-store-topbar"></div>

                            <!-- Browser Window Header -->
                            <div class="browser-header-bar">
                                <div class="browser-dots">
                                    <span class="browser-dot" style="background: #EF4444;"></span>
                                    <span class="browser-dot" style="background: #F59E0B;"></span>
                                    <span class="browser-dot" style="background: #10B981;"></span>
                                </div>
                                <div class="browser-url-bar">
                                    https://binhuwesh.com
                                </div>
                            </div>

                            <!-- Store Navbar (Matching Screenshot) -->
                            <div class="desktop-store-header">
                                <!-- Icons Left -->
                                <div class="d-flex align-items-center gap-2 text-dark font-size-14">
                                    <i class="tio-shopping-cart-outlined"></i>
                                    <i class="tio-heart-outlined"></i>
                                    <i class="tio-user-outlined"></i>
                                </div>

                                <!-- Logo Center -->
                                <div class="desktop-logo-box">
                                    <div class="font-weight-bold text-dark font-size-13" style="letter-spacing: 1px;">BIN HUWESH</div>
                                    <small class="text-muted font-size-9 d-block" style="margin-top: -3px;">شركة بن هويش التجارية</small>
                                </div>

                                <!-- Search Right -->
                                <div class="desktop-header-search">
                                    <i class="tio-search font-size-11"></i>
                                    <span>ابحث عن قطعتك المفضلة</span>
                                </div>
                            </div>

                            <!-- Store Navigation Menu -->
                            <div class="desktop-store-menu">
                                <span class="desktop-menu-item active">الرئيسية</span>
                                <span class="desktop-menu-item">رجالي</span>
                                <span class="desktop-menu-item">ولادي</span>
                                <span class="desktop-menu-item">أحذية</span>
                                <span class="desktop-menu-item">إكسسوارات</span>
                                <span class="desktop-menu-item">وصل حديثاً</span>
                                <span class="desktop-menu-item">تتبع طلبك</span>
                            </div>

                            <!-- DYNAMIC DESKTOP HERO CONTAINER -->
                            <div id="desktopHeroDynamicContainer">
                                @php
                                    $currentStyle = $heroData['web_hero_style'] ?? 'split';
                                    $isImg = ($heroData['media_type'] ?? 'image') === 'image';
                                    $imgUrl = $heroData['image_url'] ?? asset('public/assets/admin/img/bin-huwesh-hero.jpg');
                                    $vidUrl = $heroData['video_url'] ?? '';
                                    $posterUrl = $heroData['video_poster_url'] ?? '';
                                    $hasCard = !empty($heroData['show_featured_product']);
                                    $cardTitle = $heroData['featured_product_title'] ?? 'بولو محبوك';
                                    $cardSubtitle = $heroData['featured_product_subtitle'] ?? 'زيتي داكن / M';
                                    $cardPrice = $heroData['featured_product_price'] ?? '12,000 ر.ي';
                                    $cardImg = $heroData['featured_product_image'] ?? asset('public/assets/admin/img/bin-huwesh-hero.jpg');
                                @endphp

                                <!-- STYLE 1: SPLIT 50/50 (User Screenshot Exact Replica) -->
                                <!-- Right: Text 50% | Left: Media 50% -->
                                <div class="desktop-hero-split desktop-style-view {{ $currentStyle === 'split' ? '' : 'd-none' }}" id="styleView_split">
                                    <!-- Right Column (Text 50%) -->
                                    <div class="desktop-hero-split-content">
                                        <small class="text-muted font-size-10 font-weight-bold text-uppercase mb-2 previewEditionTagText" style="letter-spacing: 1px;">
                                            {{ $heroData['edition_tag'] ?? 'THE NEW EDIT / 01' }}
                                        </small>
                                        <h2 class="font-weight-bold text-dark mb-2 desktopPreviewTitleText" style="font-size: 26px; line-height: 1.25;">
                                            {{ $heroData['title'] ?? 'تفاصيل تصنع حضورك' }}
                                        </h2>
                                        <p class="text-muted font-size-12 mb-3 desktopPreviewDescText">
                                            {{ $heroData['description'] ?? 'تشكيلة جديدة. اختيارات تشبهك.' }}
                                        </p>
                                        <div>
                                            <a href="javascript:" class="btn text-white font-weight-bold px-4 py-2 desktopPreviewBtnText" style="background-color: #A71927; border-radius: 4px; font-size: 13px;">
                                                {{ $heroData['button_text'] ?? 'اكتشف التشكيلة' }}
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Left Column (Media 50%) -->
                                    <div class="desktop-hero-split-media-col">
                                        <img src="{{ $imgUrl }}" class="desktop-shared-img desktop-hero-split-media {{ $isImg ? '' : 'd-none' }}" alt="Split Hero Image">
                                        <video src="{{ $vidUrl }}" poster="{{ $posterUrl }}" class="desktop-shared-vid desktop-hero-split-media {{ $isImg ? 'd-none' : '' }}" autoplay loop muted playsinline></video>

                                        <!-- Floating Product Card (Style 1) -->
                                        <div class="desktop-floating-product-card {{ $hasCard ? '' : 'd-none' }}" style="position: absolute; bottom: 16px; left: 16px;">
                                            <div class="text-right">
                                                <div class="font-weight-bold font-size-12 text-dark mb-0 desktopProductTitle text-truncate" style="max-width: 120px;">
                                                    {{ $cardTitle }}
                                                </div>
                                                <div class="text-muted font-size-10 desktopProductVariant">
                                                    {{ $cardSubtitle }}
                                                </div>
                                                <div class="font-weight-bold text-dark font-size-11 desktopProductPrice">
                                                    {{ $cardPrice }}
                                                </div>
                                            </div>
                                            <img class="desktopProductImg" src="{{ $cardImg }}" alt="Product Thumb">
                                        </div>
                                    </div>
                                </div>

                                <!-- STYLE 2: FULL COVER OVERLAY -->
                                <div class="desktop-hero-cover desktop-style-view {{ $currentStyle === 'full_cover' ? '' : 'd-none' }}" id="styleView_full_cover">
                                    <img src="{{ $imgUrl }}" class="desktop-shared-img desktop-hero-cover-media {{ $isImg ? '' : 'd-none' }}" alt="Cover Hero Image">
                                    <video src="{{ $vidUrl }}" poster="{{ $posterUrl }}" class="desktop-shared-vid desktop-hero-cover-media {{ $isImg ? 'd-none' : '' }}" autoplay loop muted playsinline></video>

                                    <div class="desktop-hero-cover-overlay">
                                        <div style="max-width: 60%;">
                                            <small class="text-light font-size-10 font-weight-bold text-uppercase mb-1 d-block previewEditionTagText" style="letter-spacing: 1px; opacity: 0.85;">
                                                {{ $heroData['edition_tag'] ?? 'THE NEW EDIT / 01' }}
                                            </small>
                                            <h2 class="font-weight-bold mb-2 text-white desktopPreviewTitleText" style="font-size: 24px; line-height: 1.3;">
                                                {{ $heroData['title'] ?? 'تفاصيل تصنع حضورك' }}
                                            </h2>
                                            <p class="font-size-12 text-light mb-3 desktopPreviewDescText" style="opacity: 0.9;">
                                                {{ $heroData['description'] ?? 'تشكيلة جديدة. اختيارات تشبهك.' }}
                                            </p>
                                            <a href="javascript:" class="btn text-white font-weight-bold px-3 py-1 desktopPreviewBtnText" style="background-color: #A71927; border-radius: 4px; font-size: 12px;">
                                                {{ $heroData['button_text'] ?? 'اكتشف التشكيلة' }}
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Floating Product Card (Style 2) -->
                                    <div class="desktop-floating-product-card {{ $hasCard ? '' : 'd-none' }}" style="position: absolute; bottom: 20px; left: 20px;">
                                        <div class="text-right">
                                            <div class="font-weight-bold font-size-12 text-dark mb-0 desktopProductTitle text-truncate" style="max-width: 120px;">
                                                {{ $cardTitle }}
                                            </div>
                                            <div class="text-muted font-size-10 desktopProductVariant">
                                                {{ $cardSubtitle }}
                                            </div>
                                            <div class="font-weight-bold text-dark font-size-11 desktopProductPrice">
                                                {{ $cardPrice }}
                                            </div>
                                        </div>
                                        <img class="desktopProductImg" src="{{ $cardImg }}" alt="Product Thumb">
                                    </div>
                                </div>

                                <!-- STYLE 3: FRAMED BOUTIQUE WITH GLASS CARD -->
                                <div class="desktop-hero-framed desktop-style-view {{ $currentStyle === 'framed_card' ? '' : 'd-none' }}" id="styleView_framed_card">
                                    <div class="desktop-hero-framed-box">
                                        <img src="{{ $imgUrl }}" class="desktop-shared-img desktop-hero-framed-media {{ $isImg ? '' : 'd-none' }}" alt="Framed Hero Image">
                                        <video src="{{ $vidUrl }}" poster="{{ $posterUrl }}" class="desktop-shared-vid desktop-hero-framed-media {{ $isImg ? 'd-none' : '' }}" autoplay loop muted playsinline></video>

                                        <div class="desktop-hero-framed-glass">
                                            <small class="text-muted font-size-10 font-weight-bold text-uppercase mb-1 d-block previewEditionTagText" style="letter-spacing: 1px;">
                                                {{ $heroData['edition_tag'] ?? 'THE NEW EDIT / 01' }}
                                            </small>
                                            <h3 class="font-weight-bold text-dark mb-1 desktopPreviewTitleText" style="font-size: 19px;">
                                                {{ $heroData['title'] ?? 'تفاصيل تصنع حضورك' }}
                                            </h3>
                                            <p class="text-muted font-size-11 mb-2 desktopPreviewDescText">
                                                {{ $heroData['description'] ?? 'تشكيلة جديدة. اختيارات تشبهك.' }}
                                            </p>
                                            <a href="javascript:" class="btn btn-sm text-white font-weight-bold px-3 py-1 desktopPreviewBtnText" style="background-color: #A71927; border-radius: 4px; font-size: 11px;">
                                                {{ $heroData['button_text'] ?? 'اكتشف التشكيلة' }}
                                            </a>
                                        </div>

                                        <!-- Floating Product Card (Style 3) -->
                                        <div class="desktop-floating-product-card {{ $hasCard ? '' : 'd-none' }}" style="position: absolute; bottom: 20px; left: 20px;">
                                            <div class="text-right">
                                                <div class="font-weight-bold font-size-12 text-dark mb-0 desktopProductTitle text-truncate" style="max-width: 120px;">
                                                    {{ $cardTitle }}
                                                </div>
                                                <div class="text-muted font-size-10 desktopProductVariant">
                                                    {{ $cardSubtitle }}
                                                </div>
                                                <div class="font-weight-bold text-dark font-size-11 desktopProductPrice">
                                                    {{ $cardPrice }}
                                                </div>
                                            </div>
                                            <img class="desktopProductImg" src="{{ $cardImg }}" alt="Product Thumb">
                                        </div>
                                    </div>
                                </div>

                                <!-- STYLE 4: MINIMALIST CENTERED SHOWCASE -->
                                <div class="desktop-hero-minimal desktop-style-view {{ $currentStyle === 'minimal_center' ? '' : 'd-none' }}" id="styleView_minimal_center">
                                    <img src="{{ $imgUrl }}" class="desktop-shared-img desktop-hero-minimal-media {{ $isImg ? '' : 'd-none' }}" alt="Minimal Hero Image">
                                    <video src="{{ $vidUrl }}" poster="{{ $posterUrl }}" class="desktop-shared-vid desktop-hero-minimal-media {{ $isImg ? 'd-none' : '' }}" autoplay loop muted playsinline></video>

                                    <div class="desktop-hero-minimal-content">
                                        <div class="badge badge-pill badge-light px-3 py-1 font-size-10 font-weight-bold text-uppercase mb-2 previewEditionTagText" style="letter-spacing: 1.5px; color: #171717;">
                                            {{ $heroData['edition_tag'] ?? 'THE NEW EDIT / 01' }}
                                        </div>
                                        <h2 class="font-weight-bold mb-2 text-white desktopPreviewTitleText" style="font-size: 26px; line-height: 1.25;">
                                            {{ $heroData['title'] ?? 'تفاصيل تصنع حضورك' }}
                                        </h2>
                                        <p class="font-size-12 text-light mb-3 desktopPreviewDescText" style="opacity: 0.9;">
                                            {{ $heroData['description'] ?? 'تشكيلة جديدة. اختيارات تشبهك.' }}
                                        </p>
                                        <a href="javascript:" class="btn text-white font-weight-bold px-4 py-2 desktopPreviewBtnText" style="background-color: #A71927; border-radius: 30px; font-size: 13px; box-shadow: 0 4px 15px rgba(167,25,39,0.4);">
                                            {{ $heroData['button_text'] ?? 'اكتشف التشكيلة' }}
                                        </a>
                                    </div>

                                    <!-- Floating Product Card (Style 4) -->
                                    <div class="desktop-floating-product-card {{ $hasCard ? '' : 'd-none' }}" style="position: absolute; bottom: 20px; left: 20px;">
                                        <div class="text-right">
                                            <div class="font-weight-bold font-size-12 text-dark mb-0 desktopProductTitle text-truncate" style="max-width: 120px;">
                                                {{ $cardTitle }}
                                            </div>
                                            <div class="text-muted font-size-10 desktopProductVariant">
                                                {{ $cardSubtitle }}
                                            </div>
                                            <div class="font-weight-bold text-dark font-size-11 desktopProductPrice">
                                                {{ $cardPrice }}
                                            </div>
                                        </div>
                                        <img class="desktopProductImg" src="{{ $cardImg }}" alt="Product Thumb">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settings & Forms -->
            <div class="col-lg-7 col-xl-7 order-lg-2 order-1">
                <!-- Card 1: Web Hero Style Selection -->
                <div class="bh-card mb-4 p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h4 class="font-weight-bold text-dark mb-1" style="font-size: 17px;">
                                {{ translate('نمط تصميم الهيرو للويب (Web Hero Style)') }}
                            </h4>
                            <small class="text-muted font-size-12">
                                {{ translate('اختر شكل تخطيط العرض الرئيسي على المتجر الإلكتروني وشاشات الكمبيوتر.') }}
                            </small>
                        </div>
                    </div>

                    <input type="hidden" name="web_hero_style" id="webHeroStyleInput" value="{{ $heroData['web_hero_style'] ?? 'split' }}">

                    <!-- 4 Visual Style Selector Cards -->
                    <div class="row g-2">
                        <!-- Style 1: Split (Screenshot Replica) -->
                        <div class="col-6 col-sm-3">
                            <div class="style-option-card {{ ($heroData['web_hero_style'] ?? 'split') === 'split' ? 'active' : '' }}" data-style="split">
                                <div class="style-check-badge"><i class="tio-checkmark"></i></div>
                                <div class="style-diagram-preview d-flex">
                                    <div style="width: 50%; height: 100%; background: #F9F9F8; padding: 6px;">
                                        <div style="width: 70%; height: 6px; background: #171717; margin-bottom: 4px; border-radius: 2px;"></div>
                                        <div style="width: 40%; height: 4px; background: #A71927; border-radius: 2px;"></div>
                                    </div>
                                    <div style="width: 50%; height: 100%; background: #D1D5DB;"></div>
                                </div>
                                <div class="font-weight-bold font-size-12 text-dark text-center">
                                    {{ translate('منقسم تحريري 50/50') }}
                                </div>
                                <small class="text-muted font-size-10 text-center d-block">{{ translate('(المطابق للتصميم)') }}</small>
                            </div>
                        </div>

                        <!-- Style 2: Full Cover -->
                        <div class="col-6 col-sm-3">
                            <div class="style-option-card {{ ($heroData['web_hero_style'] ?? 'split') === 'full_cover' ? 'active' : '' }}" data-style="full_cover">
                                <div class="style-check-badge"><i class="tio-checkmark"></i></div>
                                <div class="style-diagram-preview" style="background: linear-gradient(to left, #4B5563, #1F2937); padding: 8px;">
                                    <div style="width: 60%; height: 6px; background: #FFFFFF; margin-bottom: 4px; border-radius: 2px;"></div>
                                    <div style="width: 35%; height: 4px; background: #A71927; border-radius: 2px;"></div>
                                </div>
                                <div class="font-weight-bold font-size-12 text-dark text-center">
                                    {{ translate('غلاف كامل عريض') }}
                                </div>
                                <small class="text-muted font-size-10 text-center d-block">{{ translate('(نصوص عائمة)') }}</small>
                            </div>
                        </div>

                        <!-- Style 3: Framed Boutique -->
                        <div class="col-6 col-sm-3">
                            <div class="style-option-card {{ ($heroData['web_hero_style'] ?? 'split') === 'framed_card' ? 'active' : '' }}" data-style="framed_card">
                                <div class="style-check-badge"><i class="tio-checkmark"></i></div>
                                <div class="style-diagram-preview p-1" style="background: #E5E7EB;">
                                    <div style="width: 100%; height: 100%; background: #9CA3AF; border-radius: 4px; position: relative;">
                                        <div style="position: absolute; bottom: 3px; right: 3px; width: 45%; height: 16px; background: rgba(255,255,255,0.9); border-radius: 2px;"></div>
                                    </div>
                                </div>
                                <div class="font-weight-bold font-size-12 text-dark text-center">
                                    {{ translate('إطار بوتيكي فاخر') }}
                                </div>
                                <small class="text-muted font-size-10 text-center d-block">{{ translate('(بطاقة زجاجية)') }}</small>
                            </div>
                        </div>

                        <!-- Style 4: Minimalist Centered -->
                        <div class="col-6 col-sm-3">
                            <div class="style-option-card {{ ($heroData['web_hero_style'] ?? 'split') === 'minimal_center' ? 'active' : '' }}" data-style="minimal_center">
                                <div class="style-check-badge"><i class="tio-checkmark"></i></div>
                                <div class="style-diagram-preview d-flex flex-column align-items-center justify-content-center" style="background: #374151;">
                                    <div style="width: 50%; height: 5px; background: #FFFFFF; margin-bottom: 4px; border-radius: 2px;"></div>
                                    <div style="width: 25%; height: 4px; background: #A71927; border-radius: 2px;"></div>
                                </div>
                                <div class="font-weight-bold font-size-12 text-dark text-center">
                                    {{ translate('عرض متمركز فائق') }}
                                </div>
                                <small class="text-muted font-size-10 text-center d-block">{{ translate('(أناقة عصرية)') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Hero Section Media & Content -->
                <div class="bh-card mb-4 p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                        <h4 class="font-weight-bold text-dark mb-0" style="font-size: 18px;">
                            {{ translate('القسم الرئيسي') }}
                        </h4>

                        <!-- Segmented Toggle: صورة | فيديو -->
                        <div class="segmented-toggle-group">
                            <input type="hidden" name="media_type" id="mediaTypeInput" value="{{ $heroData['media_type'] ?? 'image' }}">
                            <button type="button" class="segmented-btn {{ ($heroData['media_type'] ?? 'image') === 'image' ? 'active' : '' }}" data-type="image" id="typeImgBtn">
                                <i class="tio-image"></i> {{ translate('صورة') }}
                            </button>
                            <button type="button" class="segmented-btn {{ ($heroData['media_type'] ?? 'image') === 'video' ? 'active' : '' }}" data-type="video" id="typeVidBtn">
                                <i class="tio-video-camera"></i> {{ translate('فيديو') }}
                            </button>
                        </div>
                    </div>

                    <!-- Image Upload Dropzone -->
                    <div class="mb-4 {{ ($heroData['media_type'] ?? 'image') === 'image' ? '' : 'd-none' }}" id="imageUploadWrapper">
                        <label class="font-size-13 font-weight-bold text-dark mb-2 d-block">
                            {{ translate('صورة القسم الرئيسي') }}
                        </label>

                        <label for="heroImageFile" class="hero-upload-dropzone p-3 text-center" id="imageDropzone">
                            <i class="tio-camera text-muted mb-2" style="font-size: 30px;"></i>
                            <div class="font-size-13 font-weight-bold text-dark mb-1">
                                {{ translate('انقر هنا لاختيار صورة (أو اسحب وأفلت الملف)') }}
                            </div>
                            <small class="text-muted font-size-11">JPG, PNG, WEBP (النسبة المثالية: 16:9 أو دقة 1200x675)</small>
                            <input type="file" name="image" id="heroImageFile" style="display: none;" accept="image/*">
                        </label>
                        <div class="mt-2 font-size-12 text-success d-none font-weight-bold" id="imageFileName"></div>
                    </div>

                    <!-- Video Upload Dropzone -->
                    <div class="mb-4 {{ ($heroData['media_type'] ?? 'image') === 'video' ? '' : 'd-none' }}" id="videoUploadWrapper">
                        <label class="font-size-13 font-weight-bold text-dark mb-2 d-block">
                            {{ translate('فيديو القسم الرئيسي') }}
                        </label>

                        <label for="heroVideoFile" class="hero-upload-dropzone p-3 text-center" id="videoDropzone">
                            <i class="tio-video-camera text-muted mb-2" style="font-size: 30px;"></i>
                            <div class="font-size-13 font-weight-bold text-dark mb-1">
                                {{ translate('انقر هنا لاختيار ملف فيديو (أو اسحب وأفلت الملف)') }}
                            </div>
                            <small class="text-muted font-size-11">MP4, WebM (كحد أقصى 50 ميغابايت - يفضل فيديو عالي الدقة بدون صوت)</small>
                            <input type="file" name="video" id="heroVideoFile" style="display: none;" accept="video/*">
                        </label>
                        <div class="mt-2 font-size-12 text-success d-none font-weight-bold" id="videoFileName"></div>

                        <!-- Video Poster Input (Optional) -->
                        <div class="mt-3">
                            <label class="font-size-12 font-weight-bold text-muted mb-1 d-block">
                                {{ translate('صورة غلاف الفيديو (Poster - اختياري):') }}
                            </label>
                            <input type="file" name="video_poster" id="heroPosterFile" class="form-control form-control-sm" accept="image/*" style="border-radius: 6px;">
                        </div>
                    </div>

                    <!-- Edition Tag Input -->
                    <div class="form-group mb-3">
                        <label class="font-size-13 font-weight-bold text-dark mb-2" for="heroEditionTag">
                            {{ translate('وسم الإصدار أو الفئة المصغرة (Edition Tag)') }}
                        </label>
                        <input type="text" name="edition_tag" id="heroEditionTag" class="form-control" value="{{ $heroData['edition_tag'] ?? 'THE NEW EDIT / 01' }}" placeholder="e.g. THE NEW EDIT / 01" style="border-radius: 8px; font-weight: 600; height: 44px; font-size: 13px; letter-spacing: 1px; color: #4B5563; direction: ltr; text-align: left;">
                    </div>

                    <!-- Heading Input -->
                    <div class="form-group mb-3">
                        <label class="font-size-13 font-weight-bold text-dark mb-2" for="heroTitle">
                            {{ translate('العنوان') }}
                        </label>
                        <input type="text" name="title" id="heroTitle" class="form-control" value="{{ $heroData['title'] ?? 'تفاصيل تصنع حضورك' }}" placeholder="{{ translate('أدخل العنوان الرئيسي') }}" required style="border-radius: 8px; font-weight: 700; height: 46px; font-size: 14px; color: #171717;">
                    </div>

                    <!-- Description Input -->
                    <div class="form-group mb-3">
                        <label class="font-size-13 font-weight-bold text-dark mb-2" for="heroDesc">
                            {{ translate('الوصف') }}
                        </label>
                        <input type="text" name="description" id="heroDesc" class="form-control" value="{{ $heroData['description'] ?? 'تشكيلة جديدة. اختيارات تشبهك.' }}" placeholder="{{ translate('أدخل الوصف المختصر') }}" style="border-radius: 8px; font-weight: 500; height: 46px; font-size: 14px; color: #4B5563;">
                    </div>

                    <!-- Button Text Input -->
                    <div class="form-group mb-3">
                        <label class="font-size-13 font-weight-bold text-dark mb-2" for="heroBtnText">
                            {{ translate('نص الزر') }}
                        </label>
                        <input type="text" name="button_text" id="heroBtnText" class="form-control" value="{{ $heroData['button_text'] ?? 'اكتشف التشكيلة' }}" placeholder="{{ translate('نص الزر (مثال: اكتشف التشكيلة)') }}" style="border-radius: 8px; font-weight: 600; height: 46px; font-size: 14px;">
                    </div>

                    <!-- Button URL Input -->
                    <div class="form-group mb-4">
                        <label class="font-size-13 font-weight-bold text-dark mb-2" for="heroBtnUrl">
                            {{ translate('رابط الزر') }}
                        </label>
                        <input type="text" name="button_url" id="heroBtnUrl" class="form-control" value="{{ $heroData['button_url'] ?? '/new' }}" placeholder="{{ translate('رابط التوجيه (مثال: new/ أو رابط فئة أو منتج)') }}" style="border-radius: 8px; font-weight: 500; height: 46px; font-size: 14px; direction: ltr; text-align: left;">
                    </div>

                    <!-- Featured Product Card Options & Visibility -->
                    <div class="p-3 bg-light rounded border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-0 font-size-13">
                                    {{ translate('عرض بطاقة المنتج العائمة (Floating Card)') }}
                                </h6>
                                <small class="text-muted font-size-11">
                                    {{ translate('بطاقة أنيقة تعرض صورة وسعر منتج مميز فوق واجهة البانر.') }}
                                </small>
                            </div>
                            
                            <label class="switch-toggle-custom">
                                <input type="checkbox" name="show_featured_product" value="1" id="toggleFeaturedCard" {{ !empty($heroData['show_featured_product']) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div id="featuredProductControls" class="{{ !empty($heroData['show_featured_product']) ? '' : 'd-none' }} mt-3 pt-2 border-top">
                            <label class="font-size-12 font-weight-bold text-muted mb-1 d-block">
                                {{ translate('اختر المنتج لعرض صورته وبياناته:') }}
                            </label>
                            <select name="featured_product_id" id="featuredProductSelect" class="form-control form-control-sm" style="border-radius: 6px;">
                                <option value="" data-image="{{ asset('public/assets/admin/img/bin-huwesh-hero.jpg') }}" data-name="بولو محبوك" data-price="12,000 ر.ي">
                                    {{ translate('--- استخدام النموذج الافتراضي (بولو محبوك) ---') }}
                                </option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}" {{ ($heroData['featured_product_id'] ?? null) == $prod->id ? 'selected' : '' }} data-name="{{ $prod->name }}" data-price="{{ \App\CentralLogics\Helpers::set_symbol($prod->price) }}" data-image="{{ $prod->first_image_url }}">
                                        #{{ $prod->id }} - {{ $prod->name }} ({{ \App\CentralLogics\Helpers::set_symbol($prod->price) }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="featured_product_title" id="hiddenProdTitle" value="{{ $heroData['featured_product_title'] ?? 'بولو محبوك' }}">
                            <input type="hidden" name="featured_product_subtitle" id="hiddenProdSubtitle" value="{{ $heroData['featured_product_subtitle'] ?? 'زيتي داكن / M' }}">
                            <input type="hidden" name="featured_product_price" id="hiddenProdPrice" value="{{ $heroData['featured_product_price'] ?? '12,000 ر.ي' }}">
                        </div>
                    </div>
                </div>

                <!-- Card 3: Sections Ordering & Visibility -->
                <div class="bh-card mb-4 overflow-hidden">
                    <div class="p-4 border-bottom bg-white d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="font-weight-bold text-dark mb-1" style="font-size: 17px;">
                                {{ translate('إدارة وترتيب أقسام الصفحة الرئيسية') }}
                            </h4>
                            <small class="text-muted font-size-12">
                                {{ translate('حدد ترتيب ظهور الأقسام في المتجر والتطبيق، ويمكنك استخدام الأسهم أو السحب لتغيير الترتيب، أو إخفاء أي قسم بالنقر على المفتاح.') }}
                            </small>
                        </div>
                    </div>

                    <div class="sections-list-container bg-white" id="sectionsContainer">
                        @foreach($sections as $sIdx => $sec)
                            <div class="section-row-item" draggable="true" data-index="{{ $sIdx }}">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="section-drag-handle" title="{{ translate('اسحب للترتيب') }}">
                                        <i class="tio-drag font-size-18"></i>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="order-badge">{{ $sIdx + 1 }}</span>
                                        <span class="font-weight-bold text-dark font-size-14 section-name-text">{{ translate($sec['name']) }}</span>
                                    </div>
                                    <input type="hidden" class="section-key-input" name="sections[{{ $sIdx }}][key]" value="{{ $sec['key'] }}">
                                    <input type="hidden" class="section-name-input" name="sections[{{ $sIdx }}][name]" value="{{ $sec['name'] }}">
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <!-- Reorder Arrow Buttons -->
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn-order-arrow btn-move-up" title="{{ translate('تحريك لأعلى') }}">
                                            <i class="tio-chevron-up"></i>
                                        </button>
                                        <button type="button" class="btn-order-arrow btn-move-down" title="{{ translate('تحريك لأسفل') }}">
                                            <i class="tio-chevron-down"></i>
                                        </button>
                                    </div>

                                    <!-- Order Number Input -->
                                    <div class="d-flex align-items-center gap-1">
                                        <input type="number" name="sections[{{ $sIdx }}][order]" value="{{ $sec['order'] ?? ($sIdx + 1) }}" min="1" max="20" class="form-control form-control-sm text-center font-weight-bold section-order-input" style="width: 50px; border-radius: 6px; height: 32px;" readonly>
                                        <span class="text-muted font-size-12 font-weight-bold">{{ translate('الترتيب') }}</span>
                                    </div>

                                    <!-- Switch Active Toggle -->
                                    <label class="switch-toggle-custom" title="{{ translate('إظهار / إخفاء') }}">
                                        <input type="hidden" class="section-active-hidden" name="sections[{{ $sIdx }}][is_active]" value="0">
                                        <input type="checkbox" class="section-active-checkbox" name="sections[{{ $sIdx }}][is_active]" value="1" {{ !empty($sec['is_active']) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Guidance Card -->
        <div class="p-3 mb-4 rounded border d-flex align-items-center gap-3" style="background-color: #FDF2F2; border-color: #FEE2E2 !important;">
            <div class="rounded-circle d-inline-flex justify-content-center align-items-center flex-shrink-0" style="width: 36px; height: 36px; background-color: #FEE2E2; color: #A71927;">
                <i class="tio-gallery-outlined" style="font-size: 18px;"></i>
            </div>
            <div>
                <h6 class="font-weight-bold mb-1" style="color: #A71927; font-size: 14px;">
                    {{ translate('الأقسام قابلة للإدارة') }}
                </h6>
                <p class="mb-0 text-muted font-size-12">
                    {{ translate('صورة أو فيديو مع غلاف، عنوان ووصف وزر ورابط، وترتيب وإظهار وإخفاء الأقسام وبطاقاتها.') }}
                </p>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. PREVIEW SWITCHER (Mobile vs Desktop) ---
        $('#btnPreviewMobile').on('click', function() {
            $('.segmented-btn').removeClass('active');
            $(this).addClass('active');
            $('#mobileMockupContainer').removeClass('d-none');
            $('#desktopMockupContainer').addClass('d-none');
        });

        $('#btnPreviewDesktop').on('click', function() {
            $('.segmented-btn').removeClass('active');
            $(this).addClass('active');
            $('#desktopMockupContainer').removeClass('d-none');
            $('#mobileMockupContainer').addClass('d-none');
        });

        // --- 2. WEB HERO STYLE SWITCHER (Split, Cover, Framed, Minimal) ---
        $('.style-option-card').on('click', function() {
            $('.style-option-card').removeClass('active');
            $(this).addClass('active');
            const style = $(this).data('style');
            $('#webHeroStyleInput').val(style);

            // Switch visible desktop layout view
            $('.desktop-style-view').addClass('d-none');
            $('#styleView_' + style).removeClass('d-none');
        });

        // --- 3. LIVE TEXT REACTIVITY ---
        $('#heroTitle').on('input', function() {
            const val = $(this).val().trim() || 'تفاصيل تصنع حضورك';
            $('#mobilePreviewTitle').text(val);
            $('.desktopPreviewTitleText').text(val);
        });

        $('#heroDesc').on('input', function() {
            const val = $(this).val().trim() || 'تشكيلة جديدة. اختيارات تشبهك.';
            $('#mobilePreviewDesc').text(val);
            $('.desktopPreviewDescText').text(val);
        });

        $('#heroBtnText').on('input', function() {
            const val = $(this).val().trim() || 'اكتشف التشكيلة';
            $('#mobilePreviewBtn').text(val);
            $('.desktopPreviewBtnText').text(val);
        });

        $('#heroEditionTag').on('input', function() {
            const val = $(this).val().trim() || 'THE NEW EDIT / 01';
            $('.previewEditionTagText').text(val);
        });

        // --- 4. MEDIA TYPE TOGGLE (صورة / فيديو) ---
        $('#typeImgBtn').on('click', function() {
            $('.segmented-toggle-group .segmented-btn').removeClass('active');
            $(this).addClass('active');
            $('#mediaTypeInput').val('image');
            $('#imageUploadWrapper').removeClass('d-none');
            $('#videoUploadWrapper').addClass('d-none');

            // Previews
            $('#mobilePreviewImg').removeClass('d-none');
            $('#mobilePreviewVid').addClass('d-none');
            $('.desktop-shared-img').removeClass('d-none');
            $('.desktop-shared-vid').addClass('d-none');
        });

        $('#typeVidBtn').on('click', function() {
            $('.segmented-toggle-group .segmented-btn').removeClass('active');
            $(this).addClass('active');
            $('#mediaTypeInput').val('video');
            $('#videoUploadWrapper').removeClass('d-none');
            $('#imageUploadWrapper').addClass('d-none');

            // Previews
            $('#mobilePreviewVid').removeClass('d-none');
            $('#mobilePreviewImg').addClass('d-none');
            $('.desktop-shared-vid').removeClass('d-none');
            $('.desktop-shared-vid').each(function() {
                if (this.src) this.play().catch(function(){});
            });
            $('.desktop-shared-img').addClass('d-none');
        });

        // --- 5. RELIABLE HTML5 DRAG & DROP + FILE SELECT ---
        function attachDropzoneHandlers(dropzoneId, fileInputId, isVideo = false) {
            const dropzone = document.getElementById(dropzoneId);
            const fileInput = document.getElementById(fileInputId);
            if (!dropzone || !fileInput) return;

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('drag-over');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('drag-over');
                }, false);
            });

            dropzone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    fileInput.files = dt.files;
                    $(fileInput).trigger('change');
                }
            }, false);
        }

        attachDropzoneHandlers('imageDropzone', 'heroImageFile', false);
        attachDropzoneHandlers('videoDropzone', 'heroVideoFile', true);

        // Image file change handler
        $('#heroImageFile').on('change', function() {
            const file = this.files[0];
            if (file) {
                $('#imageFileName').text('✓ ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)').removeClass('d-none');
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#mobilePreviewImg').attr('src', e.target.result);
                    $('.desktop-shared-img').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });

        // Video file change handler
        $('#heroVideoFile').on('change', function() {
            const file = this.files[0];
            if (file) {
                $('#videoFileName').text('✓ ' + file.name + ' (' + Math.round(file.size / (1024 * 1024)) + ' MB)').removeClass('d-none');
                const blobUrl = URL.createObjectURL(file);
                $('#mobilePreviewVid').attr('src', blobUrl);
                $('.desktop-shared-vid').attr('src', blobUrl);
            }
        });

        // --- 6. FLOATING PRODUCT CARD REACTIVITY ACROSS ALL STYLES ---
        function updateProductCards(title, price, image) {
            // Update Mobile
            $('#mobileProductTitle').text(title);
            $('#mobileProductPrice').text(price);
            $('#mobileProductImg').attr('src', image);

            // Update All Desktop Styles
            $('.desktopProductTitle').text(title);
            $('.desktopProductPrice').text(price);
            $('.desktopProductImg').attr('src', image);

            // Update Hidden Fields
            $('#hiddenProdTitle').val(title);
            $('#hiddenProdPrice').val(price);
        }

        $('#toggleFeaturedCard').on('change', function() {
            const isChecked = $(this).is(':checked');
            if (isChecked) {
                $('#featuredProductControls').removeClass('d-none');
                $('#mobileFloatingCard').removeClass('d-none');
                $('.desktop-floating-product-card').removeClass('d-none');
            } else {
                $('#featuredProductControls').addClass('d-none');
                $('#mobileFloatingCard').addClass('d-none');
                $('.desktop-floating-product-card').addClass('d-none');
            }
        });

        $('#featuredProductSelect').on('change', function() {
            const $opt = $(this).find('option:selected');
            const name = $opt.data('name') || 'بولو محبوك';
            const price = $opt.data('price') || '12,000 ر.ي';
            const image = $opt.data('image') || '{{ asset("public/assets/admin/img/bin-huwesh-hero.jpg") }}';

            updateProductCards(name, price, image);
        });

        // --- 7. SECTIONS REORDERING (DRAG & DROP + UP/DOWN ARROWS) ---
        function reindexSections() {
            const rows = $('#sectionsContainer .section-row-item');
            const total = rows.length;

            rows.each(function(index) {
                const orderNum = index + 1;
                $(this).attr('data-index', index);
                $(this).find('.order-badge').text(orderNum);
                $(this).find('.section-order-input').val(orderNum);

                // Update input name attributes
                $(this).find('.section-key-input').attr('name', `sections[${index}][key]`);
                $(this).find('.section-name-input').attr('name', `sections[${index}][name]`);
                $(this).find('.section-order-input').attr('name', `sections[${index}][order]`);
                $(this).find('.section-active-hidden').attr('name', `sections[${index}][is_active]`);
                $(this).find('.section-active-checkbox').attr('name', `sections[${index}][is_active]`);

                // Button disabled states
                $(this).find('.btn-move-up').prop('disabled', index === 0);
                $(this).find('.btn-move-down').prop('disabled', index === total - 1);
            });
        }

        // Initialize button states on load
        reindexSections();

        // Arrow button click handlers
        $(document).on('click', '.btn-move-up', function(e) {
            e.preventDefault();
            const row = $(this).closest('.section-row-item');
            const prev = row.prev('.section-row-item');
            if (prev.length) {
                row.insertBefore(prev);
                reindexSections();
            }
        });

        $(document).on('click', '.btn-move-down', function(e) {
            e.preventDefault();
            const row = $(this).closest('.section-row-item');
            const next = row.next('.section-row-item');
            if (next.length) {
                row.insertAfter(next);
                reindexSections();
            }
        });

        // Native HTML5 Drag and Drop for Section Reordering
        const container = document.getElementById('sectionsContainer');
        if (container) {
            let draggedItem = null;

            container.addEventListener('dragstart', function(e) {
                const target = e.target.closest('.section-row-item');
                if (target) {
                    draggedItem = target;
                    target.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', '');
                }
            });

            container.addEventListener('dragend', function(e) {
                const target = e.target.closest('.section-row-item');
                if (target) {
                    target.classList.remove('dragging');
                }
                draggedItem = null;
                reindexSections();
            });

            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (!draggedItem) return;

                const target = e.target.closest('.section-row-item');
                if (target && target !== draggedItem) {
                    const rect = target.getBoundingClientRect();
                    const midpoint = rect.top + rect.height / 2;
                    if (e.clientY < midpoint) {
                        container.insertBefore(draggedItem, target);
                    } else {
                        container.insertBefore(draggedItem, target.nextSibling);
                    }
                }
            });
        }
    });
</script>
@endpush
