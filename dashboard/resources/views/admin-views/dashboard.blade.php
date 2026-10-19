@extends('layouts.admin.app')

@section('title', translate('Dashboard'))

@section('content')
    @if (Helpers::module_permission_check(MANAGEMENT_SECTION['dashboard_management']))
        <div class="content container-fluid dashboard-container">
            <!-- رأس الصفحة المحسن مع تأثير ترحيبي -->
            <div class="welcome-section mb-4">
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="welcome-content">
                                <div class="welcome-badge">
                                    <span class="badge bg-success-soft">{{ date('l, F j, Y') }}</span>
                                </div>
                                <h1 class="welcome-title">
                                    {{ translate('Welcome back') }},
                                    <span class="text-gradient">{{ auth('admin')->user()->f_name }}!</span>
                                </h1>
                                <p class="welcome-message">{{ translate('welcome_message') }}</p>
                            </div>
                        </div>
                        <div class="col-md-4 text-center d-none d-md-block">
                            <div class="welcome-illustration">
                                <i class="tio-dashboard-vs fs-70 text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- بطاقات الإحصائيات الرئيسية -->
            <div class="stats-grid mb-4" id="order_stats">
                @include('admin-views.partials._dashboard-order-stats', ['data' => $data])
            </div>

            <!-- التحليلات والرسوم البيانية -->
            <div class="analytics-grid">
                <div class="row g-4">
                    <!-- إحصائيات الطلبات - رسم بياني -->
                    <div class="col-xl-8">
                        <div class="analytics-card h-100">
                            <div class="card-header-custom">
                                <div class="header-left">
                                    <div class="icon-wrapper bg-primary-light">
                                        <i class="tio-chart-line-up fs-20 text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0">{{ translate('order_statistics') }}</h5>
                                        <p class="card-subtitle">{{ translate('orders_overview') }}</p>
                                    </div>
                                </div>
                                <div class="header-right">
                                    <div class="statistics-btn-group">
                                        <button class="stat-btn active"
                                            data-type="yearOrder">{{ translate('This_Year') }}</button>
                                        <button class="stat-btn"
                                            data-type="MonthOrder">{{ translate('This_Month') }}</button>
                                        <button class="stat-btn" data-type="WeekOrder">{{ translate('This Week') }}</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-custom">
                                <div id="updatingOrderData">
                                    <div id="line-chart-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- إحصائيات حالة الطلبات - Pie Chart -->
                    <div class="col-xl-4">
                        <div class="analytics-card h-100">
                            <div class="card-header-custom">
                                <div class="header-left">
                                    <div class="icon-wrapper bg-info-light">
                                        <i class="tio-status fs-20 text-info"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0">{{ translate('order_status_statistics') }}</h5>
                                        <p class="card-subtitle">{{ translate('current_orders_status') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-custom text-center">
                                <div class="position-relative pie-chart-container">
                                    <div id="dognut-pie"></div>
                                    <div class="total-orders-badge">
                                        <div class="total-count">
                                            {{ $data['pending_count'] + $data['ongoing_count'] + $data['delivered_count'] + $data['canceled_count'] + $data['returned_count'] + $data['failed_count'] }}
                                        </div>
                                        <div class="total-label">{{ translate('total_orders') }}</div>
                                    </div>
                                </div>
                                <div class="status-legends mt-4">
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: #6a5acd;"></span>
                                        <span>{{ translate('pending') }} ({{ $data['pending_count'] }})</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: #036BB7;"></span>
                                        <span>{{ translate('ongoing') }} ({{ $data['ongoing_count'] }})</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: #107980;"></span>
                                        <span>{{ translate('delivered') }} ({{ $data['delivered_count'] }})</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: #0e0def;"></span>
                                        <span>{{ translate('canceled') }} ({{ $data['canceled_count'] }})</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: #ff00ff;"></span>
                                        <span>{{ translate('returned') }} ({{ $data['returned_count'] }})</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: #f51414;"></span>
                                        <span>{{ translate('failed') }} ({{ $data['failed_count'] }})</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- إحصائيات الأرباح - رسم بياني -->
                    <div class="col-xl-8">
                        <div class="analytics-card h-100">
                            <div class="card-header-custom">
                                <div class="header-left">
                                    <div class="icon-wrapper bg-success-light">
                                        <i class="tio-dollar fs-20 text-success"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0">{{ translate('earning_statistics') }}</h5>
                                        <p class="card-subtitle">{{ translate('revenue_overview') }}</p>
                                    </div>
                                </div>
                                <div class="header-right">
                                    <div class="statistics-btn-group">
                                        <button class="stat-btn active"
                                            data-earn-type="yearEarn">{{ translate('This_Year') }}</button>
                                        <button class="stat-btn"
                                            data-earn-type="MonthEarn">{{ translate('This_Month') }}</button>
                                        <button class="stat-btn"
                                            data-earn-type="WeekEarn">{{ translate('This Week') }}</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-custom">
                                <div id="updatingData">
                                    <div id="line-adwords"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الطلبات الحديثة -->
                    <div class="col-xl-4">
                        <div class="analytics-card h-100">
                            <div class="card-header-custom">
                                <div class="header-left">
                                    <div class="icon-wrapper bg-warning-light">
                                        <i class="tio-receipt fs-20 text-warning"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0">{{ translate('recent_orders') }}</h5>
                                        <p class="card-subtitle">{{ translate('latest_transactions') }}</p>
                                    </div>
                                </div>
                                <div class="header-right">
                                    <a href="{{ route('admin.orders.list', ['all']) }}" class="view-all-link">
                                        {{ translate('view_all') }} <i class="tio-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body-custom p-0">
                                <div class="recent-orders-list">
                                    @foreach ($data['recent_orders'] as $order)
                                        <a href="{{ route('admin.orders.details', ['id' => $order['id']]) }}"
                                            class="order-item">
                                            <div class="order-info">
                                                <span class="order-number">#{{ $order['id'] }}</span>
                                                <span
                                                    class="order-date">{{ date('M d, h:i A', strtotime($order['created_at'])) }}</span>
                                            </div>
                                            <div class="order-status">
                                                @php
                                                    $statusClass = match ($order['order_status']) {
                                                        'pending' => 'status-pending',
                                                        'delivered' => 'status-delivered',
                                                        'confirmed',
                                                        'processing',
                                                        'out_for_delivery'
                                                            => 'status-progress',
                                                        'canceled', 'failed' => 'status-canceled',
                                                        default => 'status-default',
                                                    };
                                                    $statusText =
                                                        $order['order_status'] == 'processing'
                                                            ? translate('packaging')
                                                            : translate($order['order_status']);
                                                @endphp
                                                <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- المنتجات الأكثر مبيعاً -->
                    <div class="col-xl-4">
                        <div class="analytics-card h-100">
                            <div class="card-header-custom">
                                <div class="header-left">
                                    <div class="icon-wrapper bg-danger-light">
                                        <i class="tio-shopping-cart fs-20 text-danger"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0">{{ translate('top_selling_products') }}</h5>
                                        <p class="card-subtitle">{{ translate('best_performing_items') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-custom p-0">
                                @include('admin-views.partials._top-selling-products', [
                                    'top_sell' => $data['top_sell'],
                                ])
                            </div>
                        </div>
                    </div>

                    <!-- المنتجات الأعلى تقييماً -->
                    <div class="col-xl-4">
                        <div class="analytics-card h-100">
                            <div class="card-header-custom">
                                <div class="header-left">
                                    <div class="icon-wrapper bg-purple-light">
                                        <i class="tio-star fs-20 text-purple"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0">{{ translate('top_rated_products') }}</h5>
                                        <p class="card-subtitle">{{ translate('highest_rated_items') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-custom p-0">
                                @include('admin-views.partials._most-rated-products', [
                                    'most_rated_products' => $data['most_rated_products'],
                                ])
                            </div>
                        </div>
                    </div>

                    <!-- أفضل العملاء -->
                    <div class="col-xl-4">
                        <div class="analytics-card h-100">
                            <div class="card-header-custom">
                                <div class="header-left">
                                    <div class="icon-wrapper bg-teal-light">
                                        <i class="tio-users fs-20 text-teal"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0">{{ translate('top_customers') }}</h5>
                                        <p class="card-subtitle">{{ translate('most_loyal_customers') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-custom p-0">
                                @include('admin-views.partials._top-customer', [
                                    'top_customer' => $data['top_customer'],
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script
        src="{{ asset('public/assets/admin') }}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js">
    </script>
    <script src="{{ asset('/public/assets/admin/js/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('/public/assets/admin/js/apex-charts/dashboard.js') }}"></script>
@endpush

@push('css')
    <style>
        /* ========================================
                   Dashboard Modern Design Styles
                ========================================= */

        .dashboard-container {
            padding: 1.5rem;
            background: #f8fafc;
            min-height: calc(100vh - 70px);
        }

        /* قسم الترحيب المحسن */
        .welcome-section {
            margin-bottom: 1.5rem;
        }

        .welcome-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(16, 121, 128, 0.1);
        }

        .welcome-badge {
            margin-bottom: 1rem;
        }

        .badge.bg-success-soft {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .welcome-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2d3e50;
        }

        .text-gradient {
            background: linear-gradient(135deg, #107980 0%, #20a8a0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-message {
            color: #6c7a8a;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .welcome-illustration i {
            opacity: 0.7;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.05));
        }

        /* بطاقات التحليلات */
        .analytics-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            border: 1px solid #eef2f6;
            overflow: hidden;
        }

        .analytics-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .card-header-custom {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bg-primary-light {
            background: rgba(16, 121, 128, 0.1);
        }

        .bg-info-light {
            background: rgba(3, 107, 183, 0.1);
        }

        .bg-success-light {
            background: rgba(40, 167, 69, 0.1);
        }

        .bg-warning-light {
            background: rgba(255, 162, 0, 0.1);
        }

        .bg-danger-light {
            background: rgba(220, 53, 69, 0.1);
        }

        .bg-purple-light {
            background: rgba(111, 66, 193, 0.1);
        }

        .bg-teal-light {
            background: rgba(32, 168, 160, 0.1);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3e50;
            margin-bottom: 0.25rem;
        }

        .card-subtitle {
            font-size: 0.75rem;
            color: #8c98a4;
            margin-bottom: 0;
        }

        .card-body-custom {
            padding: 1.5rem;
        }

        /* مجموعة أزرار الإحصائيات */
        .statistics-btn-group {
            display: flex;
            gap: 0.5rem;
            background: #f8fafc;
            padding: 0.25rem;
            border-radius: 12px;
        }

        .stat-btn {
            padding: 0.4rem 1rem;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #6c7a8a;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .stat-btn:hover {
            background: rgba(16, 121, 128, 0.1);
            color: #107980;
        }

        .stat-btn.active {
            background: #107980;
            color: white;
            box-shadow: 0 2px 8px rgba(16, 121, 128, 0.3);
        }

        /* الرسم البياني الدائري */
        .pie-chart-container {
            position: relative;
            display: inline-block;
        }

        .total-orders-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .total-count {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3e50;
            line-height: 1;
        }

        .total-label {
            font-size: 0.7rem;
            color: #8c98a4;
            text-transform: uppercase;
        }

        /* أسطورة الحالات */
        .status-legends {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #5e6e7f;
        }

        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 3px;
        }

        /* قائمة الطلبات الحديثة */
        .recent-orders-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eef2f6;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .order-item:hover {
            background: #f8fafc;
            padding-left: 1.75rem;
        }

        .order-number {
            font-weight: 600;
            color: #2d3e50;
            font-size: 0.9rem;
            display: block;
        }

        .order-date {
            font-size: 0.7rem;
            color: #8c98a4;
            display: block;
            margin-top: 0.25rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .status-delivered {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .status-progress {
            background: rgba(3, 107, 183, 0.15);
            color: #036BB7;
        }

        .status-canceled {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .status-default {
            background: rgba(108, 117, 125, 0.15);
            color: #6c757d;
        }

        .view-all-link {
            color: #107980;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            transition: gap 0.2s;
        }

        .view-all-link:hover {
            gap: 0.5rem;
            color: #0d6b6f;
        }

        /* أيقونات بحجم مخصص */
        .fs-20 {
            font-size: 20px;
        }

        .fs-70 {
            font-size: 70px;
        }

        .text-primary-light {
            color: #107980;
        }

        .text-purple {
            color: #6f42c1;
        }

        .text-teal {
            color: #20a8a0;
        }

        /* RTL Support for Dashboard */
        @if (app()->getLocale() == 'ar')
            .header-left {
                flex-direction: row-reverse;
            }

            .legend-item {
                flex-direction: row-reverse;
            }

            .order-item:hover {
                padding-left: 1.5rem;
                padding-right: 1.75rem;
            }

            .view-all-link i {
                transform: rotate(180deg);
            }

            .stat-btn.active {
                box-shadow: 0 2px 8px rgba(16, 121, 128, 0.3);
            }

            .statistics-btn-group {
                flex-direction: row-reverse;
            }

        @endif

        /* استجابة للشاشات الصغيرة */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .welcome-card {
                padding: 1.25rem;
            }

            .welcome-title {
                font-size: 1.25rem;
            }

            .card-header-custom {
                flex-direction: column;
                align-items: flex-start;
            }

            .statistics-btn-group {
                width: 100%;
                justify-content: center;
            }

            .status-legends {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('script_2')
    <script>
        "use strict";

        // تحويل أزرار الإحصائيات إلى التنسيق الجديد
        $('.stat-btn[data-type]').on('click', function() {
            $('.stat-btn[data-type]').removeClass('active');
            $(this).addClass('active');
            var orderType = $(this).data('type');
            orderStatisticsUpdate(orderType);
        });

        $('.stat-btn[data-earn-type]').on('click', function() {
            $('.stat-btn[data-earn-type]').removeClass('active');
            $(this).addClass('active');
            var earnType = $(this).data('earn-type');
            earningStatisticsUpdate(earnType);
        });

        // دوال تحديث الرسوم البيانية (محفوظة كما هي)
        function order_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: "{{ route('admin.order-stats') }}",
                type: "post",
                data: {
                    statistics_type: type,
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    $('#order_stats').html(data.view)
                },
                error: function(jqXHR, textStatus, errorThrown) {},
                complete: function() {
                    $('#loading').hide()
                }
            });
        }

        Chart.plugins.unregister(ChartDataLabels);

        function orderStatisticsUpdate(value) {
            $.ajax({
                url: '{{ route('admin.dashboard.order-statistics') }}',
                type: 'GET',
                data: {
                    type: value
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(response_data) {
                    document.getElementById("line-chart-1").remove();
                    let graph = document.createElement('div');
                    graph.setAttribute("id", "line-chart-1");
                    document.getElementById("updatingOrderData").appendChild(graph);

                    var options = {
                        series: [{
                            name: "{{ translate('Orders') }}",
                            data: response_data.orders,
                        }],
                        chart: {
                            height: 316,
                            type: 'line',
                            zoom: {
                                enabled: false
                            },
                            toolbar: {
                                show: false
                            },
                            markers: {
                                size: 5
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        colors: ['#87bcbf', '#107980'],
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        xaxis: {
                            categories: response_data.orders_label
                        },
                        grid: {
                            show: true,
                            padding: {
                                bottom: 0
                            },
                            borderColor: "#d9e7ef",
                            strokeDashArray: 7,
                            xaxis: {
                                lines: {
                                    show: true
                                }
                            }
                        },
                        yaxis: {
                            tickAmount: 4
                        }
                    };

                    var chart = new ApexCharts(document.querySelector("#line-chart-1"), options);
                    chart.render();
                },
                complete: function() {
                    $('#loading').hide()
                }
            });
        }

        function earningStatisticsUpdate(value) {
            $.ajax({
                url: '{{ route('admin.dashboard.earning-statistics') }}',
                type: 'GET',
                data: {
                    type: value
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(response_data) {
                    document.getElementById("line-adwords").remove();
                    let graph = document.createElement('div');
                    graph.setAttribute("id", "line-adwords");
                    document.getElementById("updatingData").appendChild(graph);

                    var optionsLine = {
                        chart: {
                            height: 328,
                            type: 'line',
                            zoom: {
                                enabled: false
                            },
                            toolbar: {
                                show: false
                            }
                        },
                        stroke: {
                            curve: 'straight',
                            width: 2
                        },
                        colors: ['#87bcbf', '#107980'],
                        series: [{
                            name: "{{ translate('Earning') }}",
                            data: response_data.earning
                        }],
                        markers: {
                            size: 6,
                            strokeWidth: 0,
                            hover: {
                                size: 9
                            }
                        },
                        grid: {
                            show: true,
                            padding: {
                                bottom: 0
                            },
                            borderColor: "#d9e7ef",
                            strokeDashArray: 7,
                            xaxis: {
                                lines: {
                                    show: true
                                }
                            }
                        },
                        labels: response_data.earning_label,
                        xaxis: {
                            tooltip: {
                                enabled: false
                            }
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'right',
                            offsetY: -20
                        }
                    }
                    var chartLine = new ApexCharts(document.querySelector('#line-adwords'), optionsLine);
                    chartLine.render();
                },
                complete: function() {
                    $('#loading').hide()
                }
            });
        }

        // الرسوم البيانية الأولية
        var options = {
            series: [{
                name: "{{ translate('Orders') }}",
                data: [{{ $orderStatisticsChart[1] }}, {{ $orderStatisticsChart[2] }},
                    {{ $orderStatisticsChart[3] }}, {{ $orderStatisticsChart[4] }},
                    {{ $orderStatisticsChart[5] }}, {{ $orderStatisticsChart[6] }},
                    {{ $orderStatisticsChart[7] }}, {{ $orderStatisticsChart[8] }},
                    {{ $orderStatisticsChart[9] }}, {{ $orderStatisticsChart[10] }},
                    {{ $orderStatisticsChart[11] }}, {{ $orderStatisticsChart[12] }}
                ],
            }],
            chart: {
                height: 316,
                type: 'line',
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: false
                },
                markers: {
                    size: 5
                }
            },
            dataLabels: {
                enabled: false
            },
            colors: ['#87bcbf', '#107980'],
            stroke: {
                curve: 'smooth',
                width: 3
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            grid: {
                show: true,
                padding: {
                    bottom: 0
                },
                borderColor: "#d9e7ef",
                strokeDashArray: 7,
                xaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            yaxis: {
                tickAmount: 4
            }
        };
        var chart = new ApexCharts(document.querySelector("#line-chart-1"), options);
        chart.render();

        var optionsPie = {
            series: [{{ $data['ongoing_count'] }}, {{ $data['delivered_count'] }}, {{ $data['pending_count'] }},
                {{ $data['canceled'] }}, {{ $data['returned'] }}, {{ $data['failed'] }}
            ],
            chart: {
                width: 320,
                type: 'donut'
            },
            labels: ['{{ translate('ongoing') }}', '{{ translate('delivered') }}', '{{ translate('pending') }}',
                '{{ translate('canceled') }}', '{{ translate('returned') }}', '{{ translate('failed') }}'
            ],
            dataLabels: {
                enabled: false
            },
            responsive: [{
                breakpoint: 1650,
                options: {
                    chart: {
                        width: 250
                    }
                }
            }],
            colors: ['#036BB7', '#107980', '#6a5acd', '#0e0def', '#ff00ff', '#f51414'],
            fill: {
                colors: ['#036BB7', '#107980', '#6a5acd', '#0e0def', '#ff00ff', '#f51414']
            },
            legend: {
                show: false
            }
        };
        var chartPie = new ApexCharts(document.querySelector("#dognut-pie"), optionsPie);
        chartPie.render();

        var optionsLine = {
            chart: {
                height: 328,
                type: 'line',
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: false
                }
            },
            stroke: {
                curve: 'straight',
                width: 2
            },
            colors: ['#87bcbf', '#107980'],
            series: [{
                name: "{{ translate('Earning') }}",
                data: [{{ $earning[1] }}, {{ $earning[2] }}, {{ $earning[3] }}, {{ $earning[4] }},
                    {{ $earning[5] }}, {{ $earning[6] }}, {{ $earning[7] }}, {{ $earning[8] }},
                    {{ $earning[9] }}, {{ $earning[10] }}, {{ $earning[11] }}, {{ $earning[12] }}
                ]
            }],
            markers: {
                size: 6,
                strokeWidth: 0,
                hover: {
                    size: 9
                }
            },
            grid: {
                show: true,
                padding: {
                    bottom: 0
                },
                borderColor: "#d9e7ef",
                strokeDashArray: 7,
                xaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            xaxis: {
                tooltip: {
                    enabled: false
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                offsetY: -20
            }
        };
        var chartLine = new ApexCharts(document.querySelector('#line-adwords'), optionsLine);
        chartLine.render();
    </script>
@endpush
