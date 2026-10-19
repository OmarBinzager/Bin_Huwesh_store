<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ translate('Admin') }} | {{ translate('Login') }}</title>

    @php($icon = Helpers::get_business_settings('fav_icon'))
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/app/public/restaurant/' . $icon ?? '') }}">

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/theme.minc619.css?v=1.0') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">

    <style>
        /* --- Reset & Modern Override --- */
        body {
            font-family: 'Inter', 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #f5f7fc 0%, #eef2f7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 2rem 1rem;
        }

        /* remove old layout completely */
        .auth-wrapper {
            display: block !important;
            background: transparent !important;
            box-shadow: none !important;
            max-width: 100% !important;
            margin: 0 auto;
        }

        /* hide left area */
        .auth-wrapper-left {
            display: none !important;
        }

        /* right panel becomes the main card */
        .auth-wrapper-right {
            width: 100% !important;
            max-width: 520px !important;
            margin: 0 auto !important;
            background: transparent !important;
            padding: 0 !important;
        }

        .auth-wrapper-form {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(0px);
            border-radius: 42px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0, 0, 0, 0.02);
            padding: 2.5rem 2rem 2.8rem;
            transition: all 0.3s ease;
        }

        /* Logo on top - bigger, sleek */
        .modern-logo-area {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .brand-logo {
            max-width: 140px;
            height: auto;
            display: inline-block;
            transition: transform 0.2s ease;
        }

        .brand-logo:hover {
            transform: scale(1.02);
        }

        /* sign in header */
        .auth-header-clean {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header-clean .greeting-title {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            background: linear-gradient(135deg, #1e2b3c, #0f2b2f);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .welcome-caption {
            color: #5b6e8c;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .branch-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0f4fa;
            padding: 6px 14px;
            border-radius: 60px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #1f6392;
            margin-top: 12px;
            transition: all 0.2s;
        }

        .branch-link:hover {
            background: #e6edf6;
            color: #0a4b70;
            text-decoration: none;
        }

        .badge-modern {
            background: #eef2ff;
            color: #2c5f8a;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            display: inline-block;
            margin-top: 12px;
        }

        /* form fields with modern style */
        .input-group-modern {
            margin-bottom: 1.5rem;
        }

        .input-label-modern {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1f2f47;
            margin-bottom: 0.5rem;
            display: block;
            letter-spacing: -0.2px;
        }

        .form-control-fresh {
            width: 100%;
            padding: 0.9rem 1.2rem;
            font-size: 0.95rem;
            border-radius: 24px;
            border: 1.5px solid #e2e8f0;
            background-color: #ffffff;
            transition: all 0.2s;
            font-weight: 500;
            color: #1a2c3e;
        }

        .form-control-fresh:focus {
            border-color: #2d9c7c;
            box-shadow: 0 0 0 4px rgba(45, 156, 124, 0.15);
            outline: none;
        }

        /* password toggle group */
        .input-group-merge-fresh {
            display: flex;
            align-items: stretch;
            border-radius: 24px;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s;
        }

        .input-group-merge-fresh:focus-within {
            border-color: #2d9c7c;
            box-shadow: 0 0 0 4px rgba(45, 156, 124, 0.15);
        }

        .input-group-merge-fresh .form-control-fresh {
            border: none;
            background: transparent;
            box-shadow: none;
            padding: 0.9rem 1rem;
        }

        .input-group-merge-fresh .input-group-text-fresh {
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            padding-right: 1rem;
            color: #8d9db0;
            cursor: pointer;
        }

        .checkbox-modern {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 1rem 0 1.5rem;
        }

        .checkbox-modern input {
            width: 18px;
            height: 18px;
            accent-color: #2d9c7c;
            margin: 0;
            cursor: pointer;
        }

        .checkbox-modern label {
            margin: 0;
            color: #4a5b79;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
        }

        /* captcha row */
        .captcha-row-modern {
            display: flex;
            gap: 12px;
            align-items: center;
            background: #f8fafd;
            padding: 0.6rem 0.9rem;
            border-radius: 60px;
            margin-bottom: 1.8rem;
        }

        .captcha-img-wrapper {
            background: white;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 10px;
        }

        .captcha-img {
            border-radius: 20px;
            height: 48px;
            width: auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .refresh-icon {
            font-size: 1.4rem;
            cursor: pointer;
            color: #5f7f9e;
            transition: 0.2s;
        }

        .refresh-icon:hover {
            color: #2d9c7c;
            transform: rotate(15deg);
        }

        /* login button */
        .btn-login-modern {
            background: linear-gradient(98deg, #1b6b53, #2d9c7c);
            border: none;
            border-radius: 40px;
            padding: 0.9rem;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.3px;
            color: white;
            width: 100%;
            transition: all 0.25s;
            box-shadow: 0 8px 20px -8px rgba(45, 156, 124, 0.4);
        }

        .btn-login-modern:hover {
            background: linear-gradient(98deg, #145a46, #238b6e);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -10px rgba(45, 156, 124, 0.5);
        }

        /* demo card / auto-fill */
        .auto-demo-card {
            background: #f1f6fe;
            border-radius: 28px;
            padding: 1rem 1.5rem;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border: 1px solid #e2edf7;
        }

        .demo-cred span {
            font-size: 0.8rem;
            font-weight: 500;
            color: #2c5f8a;
        }

        .demo-cred strong {
            color: #1c4e6e;
        }

        .copy-btn-demo {
            background: white;
            border: none;
            border-radius: 60px;
            padding: 8px 18px;
            font-weight: 600;
            font-size: 0.8rem;
            color: #2d9c7c;
            transition: 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .copy-btn-demo:hover {
            background: #2d9c7c;
            color: white;
            transform: scale(0.98);
        }

        /* responsive */
        @media (max-width: 550px) {
            .auth-wrapper-form {
                padding: 1.8rem 1.2rem;
            }

            .greeting-title {
                font-size: 1.7rem;
            }
        }

        /* special animation */
        .auth-wrapper-form {
            animation: fadeSlideUp 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <main>
        <div class="auth-wrapper">
            {{-- LEFT SIDE IS TOTALLY REMOVED --}}
            <div class="auth-wrapper-right">
                <div class="auth-wrapper-form">
                    {{-- LOGO ONLY AT THE TOP (no left column at all) --}}
                    <div class="modern-logo-area">
                        <img class="brand-logo" src="{{ $logo }}" alt="{{ translate('logo') }}">
                    </div>

                    <div class="auth-header-clean">
                        <h1 class="greeting-title">{{ translate('sign in') }}</h1>
                        <div class="welcome-caption">{{ translate('welcome_back') }}</div>
                        <div class="branch-link">
                            <i class="tio-store"></i>
                            <a href="{{ route('branch.auth.login') }}"
                                style="text-decoration: none; color: inherit;">{{ translate('Branch login') }} →</a>
                        </div>
                        <span class="badge-modern">{{ translate('admin_or_employee_signin') }}</span>
                    </div>

                    <form id="form-id" action="{{ route('admin.auth.login') }}" method="post">
                        @csrf
                        <!-- Email field -->
                        <div class="input-group-modern">
                            <label class="input-label-modern" for="signinSrEmail">{{ translate('your email') }}</label>
                            <input type="email" class="form-control-fresh" name="email" id="signinSrEmail"
                                tabindex="1" placeholder="{{ translate('email@address.com') }}" required
                                data-msg="Please enter a valid email address.">
                        </div>

                        <!-- Password field with toggle -->
                        <div class="input-group-modern">
                            <label class="input-label-modern"
                                for="signupSrPassword">{{ translate('password') }}</label>
                            <div class="input-group-merge-fresh">
                                <input type="password" class="js-toggle-password form-control-fresh" name="password"
                                    id="signupSrPassword" placeholder="{{ translate('8+ characters required') }}"
                                    required data-msg="Your password is invalid. Please try again."
                                    data-hs-toggle-password-options='{
                                        "target": "#changePassTargetFresh",
                                        "defaultClass": "tio-hidden-outlined",
                                        "showClass": "tio-visible-outlined",
                                        "classChangeTarget": "#changePassIconFresh"
                                    }'>
                                <div id="changePassTargetFresh" class="input-group-text-fresh">
                                    <a class="input-group-text-fresh" href="javascript:" style="text-decoration: none;">
                                        <i id="changePassIconFresh" class="tio-visible-outlined"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Remember me checkbox clean -->
                        <div class="checkbox-modern">
                            <input type="checkbox" id="termsCheckbox" name="remember">
                            <label for="termsCheckbox">{{ translate('remember') }} {{ translate('me') }}</label>
                        </div>

                        @php($recaptcha = Helpers::get_business_settings('recaptcha'))
                        @if (isset($recaptcha) && $recaptcha['status'] == 1)
                            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                            <input type="hidden" name="set_default_captcha" id="set_default_captcha_value"
                                value="0">
                            <div class="row pt-2 pb-2 align-items-center d-none" id="reload-captcha">
                                <div class="col-6 pr-0">
                                    <input type="text" class="form-control-fresh" name="default_captcha_value"
                                        placeholder="{{ translate('Enter captcha value') }}" autocomplete="off">
                                </div>
                                <div class="col-6">
                                    <div class="captcha-row-modern">
                                        <img src="{{ URL('/admin/auth/code/captcha/1') }}" class="captcha-img"
                                            id="default_recaptcha_id">
                                        <i class="tio-refresh refresh-icon refresh-recaptcha"></i>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="captcha-row-modern">
                                <img src="{{ URL('/admin/auth/code/captcha/1') }}" class="captcha-img"
                                    id="default_recaptcha_id" style="height: 48px;">
                                <i class="tio-refresh refresh-icon refresh-recaptcha" style="font-size: 1.5rem;"></i>
                                <input type="text" class="form-control-fresh" style="flex:1;"
                                    name="default_captcha_value" placeholder="{{ translate('Enter captcha value') }}"
                                    autocomplete="off">
                            </div>
                        @endif

                        <button type="submit" class="btn btn-login-modern"
                            id="signInBtn">{{ translate('login') }}</button>
                    </form>

                    @if (env('APP_MODE') == 'demo')
                        <div class="auto-demo-card">
                            <div class="demo-cred">
                                <span><strong>Email</strong> : admin@admin.com</span><br>
                                <span><strong>Password</strong> : 12345678</span>
                            </div>
                            <div>
                                <button class="copy-btn-demo" id="copyButton"><i class="tio-copy"></i> Copy
                                    demo</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <script src="{{ asset('public/assets/admin') }}/js/vendor.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/theme.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/toastr.js"></script>
    {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                toastr.error('{{ $error }}', 'Error', {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif

    <script>
        $(document).on('ready', function() {
            // reinitialize toggle password with new container
            $('.js-toggle-password').each(function() {
                new HSTogglePassword(this).init()
            });

            $('.js-validate').each(function() {
                $.HSCore.components.HSValidation.init($(this));
            });
        });

        $.getJSON('https://api.ipify.org?format=json', function(data) {
            var ipAddress = data.ip;
        });

        // recaptcha logic if active
        @if (isset($recaptcha) && $recaptcha['status'] == 1)
            <
            script src = "https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js" >
    </script>
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha['site_key'] }}"></script>
    <script>
        $(document).ready(function() {
            $('#signInBtn').click(function(e) {
                if ($('#set_default_captcha_value').val() == 1) {
                    $('#form-id').submit();
                    return true;
                }

                e.preventDefault();

                if (typeof grecaptcha === 'undefined') {
                    toastr.error(
                        'Invalid recaptcha key provided. Please check the recaptcha configuration.');
                    return;
                }

                grecaptcha.ready(function() {
                    grecaptcha.execute('{{ $recaptcha['site_key'] }}', {
                        action: 'submit'
                    }).then(function(token) {
                        $('#g-recaptcha-response').value = token;
                        $('#form-id').submit();
                    });
                });

                window.onerror = function(message) {
                    var errorMessage =
                        'An unexpected error occurred. Please check the recaptcha configuration';
                    if (message.includes('Invalid site key')) {
                        errorMessage =
                            'Invalid site key provided. Please check the recaptcha configuration.';
                    } else if (message.includes('not loaded in api.js')) {
                        errorMessage =
                            'reCAPTCHA API could not be loaded. Please check the recaptcha API configuration.';
                    }
                    $('#reload-captcha').removeClass('d-none');
                    $('#set_default_captcha_value').val('1');
                    toastr.error(errorMessage);
                    return true;
                };
            });
        });
    </script>
    @endif

    <script type="text/javascript">
        $('.refresh-recaptcha').on('click', function() {
            reCaptcha();
        });

        function reCaptcha() {
            var $url = "{{ URL('/admin/auth/code/captcha') }}";
            var $url = $url + "/" + Math.random();
            document.getElementById('default_recaptcha_id').src = $url;
        }
    </script>

    @if (env('APP_MODE') == 'demo')
        <script>
            $('#copyButton').on('click', function() {
                copyCredentials();
            });

            function copyCredentials() {
                $('#signinSrEmail').val('admin@admin.com');
                $('#signupSrPassword').val('12345678');
                toastr.success('Copied successfully!', 'Success!', {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
        </script>
    @endif

    <script>
        if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write(
            '<script src="{{ asset('public/assets/admin') }}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
    </script>
</body>

</html>
