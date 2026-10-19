{{-- _dashboard-order-stats.blade.php --}}
<div class="stats-container">
    <div class="row g-3">
        <!-- بطاقة المعلقة (Pending) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a class="dashboard--card h-100" href="{{ route('admin.orders.list', ['pending']) }}">
                <div class="card-content">
                    <div class="card-info">
                        <h6 class="subtitle">{{ translate('pending') }}</h6>
                        <h2 class="title">{{ $data['pending'] }}</h2>
                    </div>
                    <img src="{{ asset('/public/assets/admin/img/dashboard/pending.png') }}" alt=""
                        class="dashboard-icon">
                </div>
                <div class="card-overlay pending-overlay"></div>
            </a>
        </div>

        <!-- بطاقة المؤكد (Confirmed) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a class="dashboard--card h-100" href="{{ route('admin.orders.list', ['confirmed']) }}">
                <div class="card-content">
                    <div class="card-info">
                        <h6 class="subtitle">{{ translate('confirmed') }}</h6>
                        <h2 class="title">{{ $data['confirmed'] }}</h2>
                    </div>
                    <img src="{{ asset('/public/assets/admin/img/dashboard/confirmed.png') }}" alt=""
                        class="dashboard-icon">
                </div>
                <div class="card-overlay confirmed-overlay"></div>
            </a>
        </div>

        <!-- بطاقة التغليف (Packaging) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a class="dashboard--card h-100" href="{{ route('admin.orders.list', ['processing']) }}">
                <div class="card-content">
                    <div class="card-info">
                        <h6 class="subtitle">{{ translate('packaging') }}</h6>
                        <h2 class="title">{{ $data['processing'] }}</h2>
                    </div>
                    <img src="{{ asset('/public/assets/admin/img/dashboard/packaging.png') }}" alt=""
                        class="dashboard-icon">
                </div>
                <div class="card-overlay packaging-overlay"></div>
            </a>
        </div>

        <!-- بطاقة خارج التوصيل (Out for Delivery) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a class="dashboard--card h-100" href="{{ route('admin.orders.list', ['out_for_delivery']) }}">
                <div class="card-content">
                    <div class="card-info">
                        <h6 class="subtitle">{{ translate('out_for_delivery') }}</h6>
                        <h2 class="title">{{ $data['out_for_delivery'] }}</h2>
                    </div>
                    <img src="{{ asset('/public/assets/admin/img/dashboard/out-for-delivery.png') }}" alt=""
                        class="dashboard-icon">
                </div>
                <div class="card-overlay delivery-overlay"></div>
            </a>
        </div>

        <!-- بطاقة المكتملة (Delivered) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a class="order--card h-100" href="{{ route('admin.orders.list', ['delivered']) }}">
                <div class="order-card-content">
                    <div class="order-info">
                        <img src="{{ asset('public/assets/admin/img/delivery/1.png') }}" alt="dashboard"
                            class="order-icon">
                        <h6 class="order-subtitle">{{ translate('delivered') }}</h6>
                    </div>
                    <span class="order-value text-success">{{ $data['delivered'] }}</span>
                </div>
                <div class="card-overlay delivered-overlay"></div>
            </a>
        </div>

        <!-- بطاقة الملغية (Canceled) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a class="order--card h-100" href="{{ route('admin.orders.list', ['canceled']) }}">
                <div class="order-card-content">
                    <div class="order-info">
                        <img src="{{ asset('public/assets/admin/img/delivery/2.png') }}"
                            alt="{{ translate('dashboard') }}" class="order-icon">
                        <h6 class="order-subtitle">{{ translate('Canceled') }}</h6>
                    </div>
                    <span class="order-value text-danger">{{ $data['canceled'] }}</span>
                </div>
                <div class="card-overlay canceled-overlay"></div>
            </a>
        </div>

        <!-- بطاقة المرتجعة (Returned) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a class="order--card h-100" href="{{ route('admin.orders.list', ['returned']) }}">
                <div class="order-card-content">
                    <div class="order-info">
                        <img src="{{ asset('public/assets/admin/img/delivery/3.png') }}"
                            alt="{{ translate('dashboard') }}" class="order-icon">
                        <h6 class="order-subtitle">{{ translate('returned') }}</h6>
                    </div>
                    <span class="order-value text-warning">{{ $data['returned'] }}</span>
                </div>
                <div class="card-overlay returned-overlay"></div>
            </a>
        </div>

        <!-- بطاقة الفاشلة (Failed) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a class="order--card h-100" href="{{ route('admin.orders.list', ['failed']) }}">
                <div class="order-card-content">
                    <div class="order-info">
                        <img src="{{ asset('public/assets/admin/img/delivery/4.png') }}"
                            alt="{{ translate('dashboard') }}" class="order-icon">
                        <h6 class="order-subtitle">{{ translate('Failed to Delivered') }}</h6>
                    </div>
                    <span class="order-value text-danger">{{ $data['failed'] }}</span>
                </div>
                <div class="card-overlay failed-overlay"></div>
            </a>
        </div>
    </div>
</div>

<style>
    /* ========================================
       تحسين بطاقات الإحصائيات مع ضمان الظهور الأفقي
    ========================================= */

    .stats-container {
        width: 100%;
    }

    .stats-container .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-right: -12px !important;
        margin-left: -12px !important;
    }

    .stats-container [class*="col-"] {
        flex: 0 0 auto !important;
        padding-right: 12px !important;
        padding-left: 12px !important;
        margin-bottom: 24px !important;
    }

    /* توزيع 4 أعمدة في كل صف */
    @media (min-width: 992px) {
        .stats-container .col-lg-3 {
            width: 25% !important;
            flex: 0 0 auto !important;
        }
    }

    /* توزيع عمودين في الشاشات المتوسطة */
    @media (min-width: 576px) and (max-width: 991px) {
        .stats-container .col-sm-6 {
            width: 50% !important;
            flex: 0 0 auto !important;
        }
    }

    /* عمود واحد في الشاشات الصغيرة */
    @media (max-width: 575px) {
        .stats-container .col-12 {
            width: 100% !important;
            flex: 0 0 auto !important;
        }
    }

    /* تنسيق البطاقات من النوع الأول (dashboard--card) */
    .dashboard--card {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        text-decoration: none;
        transition: all 0.3s ease;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.05);
        min-height: 130px;
    }

    .dashboard--card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .dashboard--card .card-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        z-index: 2;
    }

    .dashboard--card .subtitle {
        font-size: 14px;
        font-weight: 500;
        color: #6c7a8a;
        margin-bottom: 8px;
        text-transform: capitalize;
    }

    .dashboard--card .title {
        font-size: 32px;
        font-weight: 700;
        color: #2d3e50;
        margin: 0;
    }

    .dashboard--card .dashboard-icon {
        width: 60px;
        height: 60px;
        object-fit: contain;
    }

    /* تنسيق البطاقات من النوع الثاني (order--card) */
    .order--card {
        position: relative;
        display: block;
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        text-decoration: none;
        transition: all 0.3s ease;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .order--card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .order-card-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        z-index: 2;
        position: relative;
    }

    .order-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .order-icon {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }

    .order-subtitle {
        font-size: 14px;
        font-weight: 500;
        color: #6c7a8a;
        margin: 0;
    }

    .order-value {
        font-size: 28px;
        font-weight: 700;
    }

    /* تأثيرات الخلفية عند التمرير */
    .card-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        transition: height 0.3s ease;
    }

    .dashboard--card:hover .card-overlay,
    .order--card:hover .card-overlay {
        height: 100%;
        opacity: 0.05;
    }

    .pending-overlay {
        background: linear-gradient(90deg, #ffc107, #ff9800);
    }

    .confirmed-overlay {
        background: linear-gradient(90deg, #2196f3, #03a9f4);
    }

    .packaging-overlay {
        background: linear-gradient(90deg, #9c27b0, #e91e63);
    }

    .delivery-overlay {
        background: linear-gradient(90deg, #00bcd4, #009688);
    }

    .delivered-overlay {
        background: linear-gradient(90deg, #4caf50, #8bc34a);
    }

    .canceled-overlay {
        background: linear-gradient(90deg, #f44336, #e91e63);
    }

    .returned-overlay {
        background: linear-gradient(90deg, #ff9800, #ffc107);
    }

    .failed-overlay {
        background: linear-gradient(90deg, #f44336, #d32f2f);
    }

    /* دعم اللغة العربية RTL */
    @if (app()->getLocale() == 'ar')
        .dashboard--card .card-content {
            flex-direction: row-reverse;
        }

        .order-card-content {
            flex-direction: row-reverse;
        }

        .order-info {
            flex-direction: row-reverse;
        }

        .dashboard--card .title,
        .dashboard--card .subtitle {
            text-align: right;
        }

        .order-value {
            text-align: left;
        }
    @endif
</style>
