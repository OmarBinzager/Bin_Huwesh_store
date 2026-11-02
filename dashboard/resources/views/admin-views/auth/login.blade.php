<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>إدارة بن هويش | {{ translate('Login') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('public/assets/admin/img/logo/bin-huwesh-logo.png') }}">

    <!-- IBM Plex Sans Arabic Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">

    <style>
        :root {
            --bh-primary: #A71927;
            --bh-primary-hover: #8E1521;
            --bh-ink: #171717;
            --bh-muted: #707070;
            --bh-border: #DCDFE4;
            --bh-surface: #F9F9F8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
            font-family: 'IBM Plex Sans Arabic', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            background-color: var(--bh-surface);
            color: var(--bh-ink);
            overflow-x: hidden;
        }

        .bh-split-screen {
            display: flex;
            width: 100vw;
            min-height: 100vh;
            flex-direction: row-reverse; /* In RTL: puts photo on the left, form on the right */
        }

        /* ---------------- Left Side: Fashion Model Image ---------------- */
        .bh-image-pane {
            flex: 1 1 48%;
            width: 48%;
            min-height: 100vh;
            position: relative;
            background-color: #171717;
            overflow: hidden;
        }

        .bh-fashion-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 20%;
            display: block;
        }

        /* ---------------- Right Side: Clean Login Form ---------------- */
        .bh-form-pane {
            flex: 1 1 52%;
            width: 52%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background-color: var(--bh-surface);
        }

        .bh-login-box {
            width: 100%;
            max-width: 410px;
            text-align: center;
        }

        .bh-brand-logo-img {
            max-height: 68px;
            width: auto;
            object-fit: contain;
            margin-bottom: 28px;
            display: inline-block;
        }

        .bh-login-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--bh-ink);
            margin-bottom: 8px;
            letter-spacing: -0.4px;
            line-height: 1.25;
        }

        .bh-login-subtitle {
            font-size: 14.5px;
            color: var(--bh-muted);
            margin-bottom: 34px;
            font-weight: 400;
        }

        /* Form Controls */
        .bh-form-group {
            margin-bottom: 20px;
            text-align: right;
        }

        .bh-field-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--bh-ink);
            margin-bottom: 7px;
            text-align: right;
        }

        .bh-custom-input {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            font-size: 14px;
            font-family: inherit;
            color: var(--bh-ink);
            background-color: #FFFFFF;
            border: 1px solid var(--bh-border);
            border-radius: 4px;
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
            text-align: right;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .bh-custom-input:focus {
            outline: none;
            border-color: var(--bh-primary);
            box-shadow: 0 0 0 3px rgba(167, 25, 39, 0.12);
        }

        .bh-custom-input::placeholder {
            color: #9CA3AF;
        }

        /* Password Input Wrapper with Icon */
        .bh-password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .bh-password-input {
            padding-left: 44px; /* Space for lock icon on left */
            padding-right: 16px;
        }

        .bh-lock-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #707070;
            font-size: 18px;
            cursor: pointer;
            transition: color 0.16s;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            padding: 4px;
        }

        .bh-lock-icon:hover {
            color: var(--bh-primary);
        }

        /* Submit Button */
        .bh-submit-btn {
            width: 100%;
            height: 48px;
            background-color: var(--bh-primary);
            color: #FFFFFF;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.16s ease, transform 0.08s ease;
            box-shadow: 0 2px 4px rgba(167, 25, 39, 0.18);
        }

        .bh-submit-btn:hover {
            background-color: var(--bh-primary-hover);
            color: #FFFFFF;
            text-decoration: none;
        }

        .bh-submit-btn:active {
            transform: scale(0.99);
        }

        /* Footer Permission Notice */
        .bh-footer-notice {
            font-size: 12px;
            color: #888888;
            margin-top: 24px;
            text-align: center;
            font-weight: 400;
            line-height: 1.5;
        }

        /* Captcha Styling if enabled */
        .bh-captcha-wrap {
            margin-top: 14px;
            background: #FFFFFF;
            border: 1px solid var(--bh-border);
            border-radius: 4px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive Breakpoint */
        @media (max-width: 991.98px) {
            .bh-image-pane {
                display: none;
            }
            .bh-form-pane {
                width: 100%;
                flex: 1 1 100%;
            }
            .bh-login-box {
                max-width: 440px;
            }
        }
    </style>
</head>

<body>
    <div class="bh-split-screen">
        <!-- ---------------- Left: Fashion Model Image ---------------- -->
        <div class="bh-image-pane">
            <img src="{{ asset('public/assets/admin/img/bin-huwesh-login.jpg') }}" alt="Bin Huwesh Fashion" class="bh-fashion-img">
        </div>

        <!-- ---------------- Right: Login Form ---------------- -->
        <div class="bh-form-pane">
            <div class="bh-login-box">
                <!-- Brand Logo (Reference matching screenshot) -->
                <div class="text-center">
                    <img src="{{ asset('public/assets/admin/img/logo/bin-huwesh-reference.png') }}"
                         alt="شركة بن هويش التجارية - BIN HUWESH" class="bh-brand-logo-img">
                </div>

                <!-- Titles -->
                <h1 class="bh-login-title">إدارة بن هويش</h1>
                <p class="bh-login-subtitle">أهلاً بالفريق، سجّل دخولك للمتابعة.</p>

                <!-- Login Form -->
                <form action="{{ route('admin.auth.login') }}" method="POST" id="form-id">
                    @csrf

                    <!-- Email Field -->
                    <div class="bh-form-group">
                        <label class="bh-field-label" for="signinEmail">البريد الإلكتروني</label>
                        <input type="email" id="signinEmail" name="email" class="bh-custom-input"
                               value="{{ old('email', 'admin@example.com') }}"
                               placeholder="admin@example.com" required autocomplete="email">
                    </div>

                    <!-- Password Field -->
                    <div class="bh-form-group">
                        <label class="bh-field-label" for="signinSrPassword">كلمة المرور</label>
                        <div class="bh-password-wrapper">
                            <input type="password" id="signinSrPassword" name="password"
                                   class="bh-custom-input bh-password-input"
                                   value="12345678" placeholder="••••••••" required autocomplete="current-password">
                            
                            <button type="button" class="bh-lock-icon" id="togglePasswordBtn" title="{{ translate('Show/Hide password') }}" tabindex="-1">
                                <i class="tio-lock-outlined" id="lockIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Recaptcha if active -->
                    @php($recaptcha = Helpers::get_business_settings('recaptcha'))
                    @if (isset($recaptcha) && $recaptcha['status'] == 1)
                        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                        <input type="hidden" name="set_default_captcha" id="set_default_captcha_value" value="0">
                        <div class="bh-captcha-wrap" id="reload-captcha">
                            <input type="text" class="bh-custom-input" style="height: 38px;"
                                   name="default_captcha_value" placeholder="{{ translate('Enter captcha value') }}" autocomplete="off">
                            <img src="{{ URL('/admin/auth/code/captcha/1') }}" id="default_recaptcha_id" style="border-radius: 4px; height: 36px;">
                            <i class="tio-refresh refresh-recaptcha" style="cursor: pointer; color: var(--bh-muted); font-size: 20px;"></i>
                        </div>
                    @endif

                    <!-- Submit Button -->
                    <button type="submit" class="bh-submit-btn" id="signInBtn">
                        تسجيل الدخول
                    </button>

                    <!-- Footer Notice -->
                    <p class="bh-footer-notice">الصلاحيات تحدد الصفحات والإجراءات المتاحة.</p>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('public/assets/admin') }}/js/vendor.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/toastr.js"></script>
    {!! Toastr::message() !!}

    <script>
        // Toggle password visibility
        $('#togglePasswordBtn').on('click', function() {
            var input = $('#signinSrPassword');
            var icon = $('#lockIcon');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('tio-lock-outlined').addClass('tio-visible-outlined');
            } else {
                input.attr('type', 'password');
                icon.removeClass('tio-visible-outlined').addClass('tio-lock-outlined');
            }
        });

        // Refresh captcha if present
        $('.refresh-recaptcha').on('click', function() {
            var url = "{{ URL('/admin/auth/code/captcha') }}/" + Math.random();
            document.getElementById('default_recaptcha_id').src = url;
        });

        @if (isset($recaptcha) && $recaptcha['status'] == 1)
        $('#signInBtn').click(function(e) {
            if (typeof grecaptcha !== 'undefined') {
                e.preventDefault();
                grecaptcha.ready(function() {
                    grecaptcha.execute('{{ $recaptcha['site_key'] }}', { action: 'submit' }).then(function(token) {
                        $('#g-recaptcha-response').val(token);
                        $('#form-id').submit();
                    });
                });
            }
        });
        @endif
    </script>
</body>

</html>
