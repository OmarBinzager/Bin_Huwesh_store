<div id="headerMain" class="d-none">
    <header id="header"
        class="navbar navbar-expand-lg navbar-fixed navbar-height navbar-flush navbar-container navbar-bordered modern-header">
        <div class="navbar-nav-wrap w-100">
            <!-- القسم الأيسر -->
            <div class="navbar-nav-wrap-content-left d-flex align-items-center">
                <button type="button" class="js-navbar-vertical-aside-toggle-invoker sidebar-toggle-btn mr-3">
                    <i class="tio-first-page navbar-vertical-aside-toggle-short-align"></i>
                    <i class="tio-last-page navbar-vertical-aside-toggle-full-align"></i>
                </button>

                <div class="d-xl-none">
                    @php($logo = \App\Model\BusinessSetting::where(['key' => 'logo'])->first()->value)
                    <a href="{{ route('admin.dashboard') }}" class="mobile-logo">
                        <img src="{{ App\CentralLogics\Helpers::onErrorImage($logo, asset('storage/app/public/restaurant') . '/' . $logo, asset('public/assets/admin/img/160x160/img2.jpg'), 'restaurant/') }}"
                            alt="{{ translate('logo') }}" style="height: 32px; width: auto;">
                    </a>
                </div>
            </div>

            <!-- القسم الأيمن -->
            <div class="navbar-nav-wrap-content-right ml-auto">
                <ul class="navbar-nav align-items-center flex-row">

                    <!-- تبديل اللغة -->
                    <li class="nav-item dropdown language-dropdown">
                        <div class="hs-unfold">
                            <div class="p-2">
                                @php($local = session()->has('local') ? session('local') : \App\CentralLogics\Helpers::get_default_language())
                                @php($lang = \App\CentralLogics\Helpers::get_business_settings('language') ?? null)
                                <div class="topbar-text dropdown disable-autohide text-capitalize">
                                    @if (isset($lang) && array_key_exists('code', $lang[0]))
                                        <a class="topbar-link dropdown-toggle lang-country-flag d-flex align-items-center gap-2"
                                            href="#" data-toggle="dropdown" id="languageDropdown">
                                            <div class="language-icon-wrapper">
                                                <i class="tio-globe"></i>
                                            </div>
                                            @foreach ($lang as $data)
                                                @if ($data['code'] == $local)
                                                    <span
                                                        class="language-code font-weight-medium">{{ strtoupper($data['code']) }}</span>
                                                @endif
                                            @endforeach
                                            <i class="tio-chevron-down ml-1 fs-10"></i>
                                        </a>
                                        <ul class="dropdown-menu language-dropdown-menu"
                                            aria-labelledby="languageDropdown">
                                            @foreach ($lang as $key => $data)
                                                @if ($data['status'] == 1)
                                                    <li>
                                                        <a class="dropdown-item language-item @if ($data['code'] == $local) active @endif"
                                                            href="{{ route('admin.lang', [$data['code']]) }}">
                                                            <div
                                                                class="d-flex align-items-center justify-content-between w-100">
                                                                <span>{{ \App\CentralLogics\Helpers::get_language_name($data['code']) }}</span>
                                                                @if ($data['code'] == $local)
                                                                    <i class="tio-done text-success fs-14"></i>
                                                                @endif
                                                            </div>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>

                    <div class="nav-item vr-divider d-none d-sm-block"></div>

                    <!-- أيقونة الرسائل -->
                    <li class="nav-item d-none d-sm-inline-block notification-item">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker notification-btn" href="{{ route('admin.message.list') }}"
                                data-toggle="tooltip" data-placement="bottom" title="{{ translate('messages') }}">
                                <div class="notification-icon-wrapper">
                                    <i class="tio-messages-outlined"></i>
                                    @php($message = \App\Model\Conversation::where('checked', 0)->count())
                                    @if ($message > 0)
                                        <span class="notification-badge">{{ $message }}</span>
                                    @endif
                                </div>
                            </a>
                        </div>
                    </li>

                    <!-- أيقونة الطلبات الجديدة -->
                    <li class="nav-item notification-item">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker notification-btn"
                                href="{{ route('admin.orders.list', ['status' => 'pending']) }}" data-toggle="tooltip"
                                data-placement="bottom" title="{{ translate('new_orders') }}">
                                <div class="notification-icon-wrapper">
                                    <i class="tio-shopping-cart-outlined"></i>
                                    @php($newOrders = \App\Model\Order::where(['checked' => 0])->count())
                                    @if ($newOrders > 0)
                                        <span class="notification-badge">{{ $newOrders }}</span>
                                    @endif
                                </div>
                            </a>
                        </div>
                    </li>

                    <div class="nav-item vr-divider"></div>

                    <!-- قسم المستخدم -->
                    <li class="nav-item user-profile-item">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker user-profile-trigger" href="javascript:;"
                                data-hs-unfold-options='{
                                     "target": "#accountNavbarDropdown",
                                     "type": "css-animation",
                                     "animationIn": "fadeInUp",
                                     "animationOut": "fadeOutDown"
                                   }'>
                                <div class="user-profile-wrapper d-flex align-items-center gap-2">
                                    <div class="user-info d-none d-md-block">
                                        <div class="user-name">{{ auth('admin')->user()->f_name }}
                                            {{ auth('admin')->user()->l_name }}</div>
                                        <div class="user-role">{{ auth('admin')->user()->role->name }}</div>
                                    </div>

                                    <div class="user-avatar-wrapper">
                                        <img class="user-avatar"
                                            src="{{ App\CentralLogics\Helpers::onErrorImage(auth('admin')->user()->image, asset('storage/app/public/admin') . '/' . auth('admin')->user()->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'admin/') }}"
                                            alt="{{ translate('admin') }}">
                                        <span class="user-status-badge"></span>
                                    </div>

                                    <i class="tio-chevron-down dropdown-arrow d-none d-sm-block"></i>
                                </div>
                            </a>

                            <!-- القائمة المنسدلة للمستخدم -->
                            <div id="accountNavbarDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu user-dropdown-menu">
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="user-avatar-lg">
                                            <img src="{{ App\CentralLogics\Helpers::onErrorImage(auth('admin')->user()->image, asset('storage/app/public/admin') . '/' . auth('admin')->user()->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'admin/') }}"
                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                alt="{{ translate('admin') }}">
                                        </div>
                                        <div class="user-info-text">
                                            <h6 class="mb-0">{{ auth('admin')->user()->f_name }}
                                                {{ auth('admin')->user()->l_name }}</h6>
                                            <small class="text-muted">{{ auth('admin')->user()->email }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="dropdown-divider my-2"></div>

                                <a class="dropdown-item user-menu-item" href="{{ route('admin.settings') }}">
                                    <div class="menu-icon-wrapper">
                                        <i class="tio-settings-outlined"></i>
                                    </div>
                                    <span>{{ translate('settings') }}</span>
                                </a>

                                <div class="dropdown-divider my-2"></div>

                                <!-- تم تعديل هذا الجزء - إزالة onclick واستخدام id -->
                                <a class="dropdown-item user-menu-item text-danger" href="#" id="logoutButton">
                                    <div class="menu-icon-wrapper">
                                        <i class="tio-log-out"></i>
                                    </div>
                                    <span>{{ translate('sign_out') }}</span>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </header>
</div>
<div id="headerFluid" class="d-none"></div>
<div id="headerDouble" class="d-none"></div>

<!-- Stylesheets -->
@push('css')
    <style>
        /* جميع الـ CSS الخاص بك كما هو */
        .modern-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .sidebar-toggle-btn {
            background: transparent;
            border: none;
            color: #5e6e7f;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar-toggle-btn:hover {
            background: rgba(16, 121, 128, 0.1);
            color: #107980;
        }

        .sidebar-toggle-btn i {
            font-size: 20px;
        }

        .language-dropdown .lang-country-flag {
            background: #f1f3f5;
            padding: 8px 12px;
            border-radius: 30px;
            text-decoration: none;
            gap: 6px;
        }

        .language-icon-wrapper i {
            font-size: 18px;
            color: #107980;
        }

        .language-code {
            font-size: 14px;
            font-weight: 600;
            color: #2d3e50;
        }

        .language-dropdown-menu {
            min-width: 180px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 8px 0;
        }

        .language-item {
            padding: 10px 16px;
            transition: all 0.2s;
        }

        .language-item:hover {
            background: #f8f9fa;
            padding-left: 20px;
        }

        .language-item.active {
            background: rgba(16, 121, 128, 0.1);
            color: #107980;
        }

        .vr-divider {
            width: 1px;
            height: 30px;
            background: linear-gradient(to bottom, transparent, #dee2e6, transparent);
            margin: 0 8px;
        }

        .notification-item {
            margin: 0 4px;
        }

        .notification-btn {
            position: relative;
            background: transparent;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #5e6e7f;
        }

        .notification-btn:hover {
            background: rgba(16, 121, 128, 0.08);
            color: #107980;
            transform: translateY(-2px);
        }

        .notification-icon-wrapper {
            position: relative;
            display: inline-block;
        }

        .notification-icon-wrapper i {
            font-size: 22px;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            font-size: 10px;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            box-shadow: 0 2px 5px rgba(238, 90, 36, 0.3);
        }

        .user-profile-item {
            margin-left: 8px;
        }

        .user-profile-trigger {
            text-decoration: none;
            cursor: pointer;
        }

        .user-profile-wrapper {
            background: #ffffff;
            padding: 5px 10px 5px 15px;
            border-radius: 50px;
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
        }

        .user-profile-wrapper:hover {
            background: #f8f9fa;
            border-color: #107980;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .user-info {
            text-align: right;
            margin-right: 10px;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #2d3e50;
            line-height: 1.3;
        }

        .user-role {
            font-size: 11px;
            color: #8c98a4;
            line-height: 1.2;
        }

        .user-avatar-wrapper {
            position: relative;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #107980;
            padding: 2px;
        }

        .user-status-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 10px;
            height: 10px;
            background: #28a745;
            border-radius: 50%;
            border: 2px solid white;
        }

        .dropdown-arrow {
            font-size: 14px;
            color: #8c98a4;
            margin-left: 5px;
            transition: transform 0.2s;
        }

        .user-profile-trigger[aria-expanded="true"] .dropdown-arrow {
            transform: rotate(180deg);
        }

        .user-dropdown-menu {
            width: 280px;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            padding: 0;
            overflow: hidden;
        }

        .dropdown-header {
            padding: 20px;
            background: linear-gradient(135deg, rgb(9, 68, 72), #041848);
            color: white;
        }

        .user-avatar-lg {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid white;
        }

        .user-avatar-lg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info-text h6 {
            color: white;
            font-weight: 600;
        }

        .user-info-text small {
            color: rgba(255, 255, 255, 0.8);
        }

        .user-menu-item {
            padding: 12px 20px;
            transition: all 0.2s;
            gap: 12px;
            display: flex;
            align-items: center;
        }

        .user-menu-item:hover {
            background: #f8f9fa;
            padding-left: 28px;
        }

        .menu-icon-wrapper {
            width: 24px;
            display: inline-block;
        }

        .menu-icon-wrapper i {
            font-size: 18px;
            color: #107980;
        }

        @if (app()->getLocale() == 'ar')
            .user-info {
                text-align: left;
                margin-right: 0;
                margin-left: 10px;
            }

            .dropdown-arrow {
                margin-left: 0;
                margin-right: 5px;
            }

            .notification-badge {
                right: auto;
                left: -8px;
            }

            .language-item:hover {
                padding-left: 16px;
                padding-right: 20px;
            }

            .user-menu-item:hover {
                padding-left: 20px;
                padding-right: 28px;
            }
        @endif

        @media (max-width: 768px) {
            .user-info {
                display: none;
            }

            .user-profile-wrapper {
                padding: 4px 8px;
            }

            .user-avatar {
                width: 34px;
                height: 34px;
            }

            .notification-btn {
                width: 38px;
                height: 38px;
            }

            .language-code {
                display: none;
            }

            .language-dropdown .lang-country-flag {
                padding: 8px;
            }
        }

        .gap-2 {
            gap: 8px;
        }

        .gap-3 {
            gap: 12px;
        }

        .fs-10 {
            font-size: 10px;
        }

        .fs-14 {
            font-size: 14px;
        }
    </style>
@endpush

@push('script_2')
    <script>
        $(document).ready(function() {
            // تأثير حركة عند تمرير الصفحة للهيدر
            let lastScroll = 0;
            $(window).on('scroll', function() {
                const currentScroll = $(this).scrollTop();
                const $header = $('#header');

                if (currentScroll > 100) {
                    $header.addClass('header-scrolled');
                } else {
                    $header.removeClass('header-scrolled');
                }
            });

            // معالج تسجيل الخروج - متوافق بالكامل مع SweetAlert2
            $('#logoutButton').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // إغلاق القائمة المنسدلة أولاً
                $('.hs-unfold-content.show').removeClass('show');

                // استخدام SweetAlert للتأكيد
                if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                    Swal.fire({
                        title: '{{ translate('Are you sure you want to logout?') }}',
                        text: '{{ translate('You will be redirected to the login page') }}',
                        type: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#107980',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '{{ translate('Yes, Logout') }}',
                        cancelButtonText: '{{ translate('Cancel') }}',
                        reverseButtons: true
                    }).then((result) => {
                        if (result && (result.value || result.isConfirmed)) {
                            if (typeof Swal.showLoading === 'function') {
                                Swal.showLoading();
                            }
                            window.location.href = '{{ route('admin.auth.logout') }}';
                        }
                    });
                } else if (confirm('{{ translate('Are you sure you want to logout?') }}')) {
                    // Fallback إذا لم يتم تحميل SweetAlert
                    window.location.href = '{{ route('admin.auth.logout') }}';
                }

                return false;
            });
        });

        // دالة احتياطية للتوافق مع الكود القديم
        window.confirmLogout = function() {
            $('#logoutButton').trigger('click');
            return false;
        };
    </script>
@endpush
