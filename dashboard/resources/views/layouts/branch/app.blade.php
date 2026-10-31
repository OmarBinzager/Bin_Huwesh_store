<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title')</title>
    @php($icon = \App\Model\BusinessSetting::where(['key' => 'fav_icon'])->first()->value)
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/app/public/restaurant/' . $icon ?? '') }}">
    <link rel="shortcut icon" href="">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/style.css">
    @stack('css_or_js')

    <script
        src="{{asset('public/assets/admin')}}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js"></script>
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">
</head>

<body class="footer-offset">

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="loading" class="d-none">
                <div class="loader-image">
                    <img width="200" src="{{asset('public/assets/admin/img/loader.gif')}}">
                </div>
            </div>
        </div>
    </div>
</div>

@include('layouts.branch.partials._header')
@include('layouts.branch.partials._sidebar')

<main id="content" role="main" class="main pointer-event">
@yield('content')

@include('layouts.branch.partials._footer')

    <div class="modal fade" id="popup-modal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="text-center">
                                <h2 class="order-check-colour">
                                    <i class="tio-shopping-cart-outlined"></i> {{ translate('You have new order, Check Please.') }}
                                </h2>
                                <hr>
                                <button id="check-order" class="btn btn-primary">{{ translate('Ok, let me check') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delivery-popup-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <span class="btn btn-icon btn-soft-info rounded-circle" style="width: 70px; height: 70px; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; background: rgba(0, 150, 255, 0.1); color: #0080ff;">
                            <i class="tio-bike"></i>
                        </span>
                    </div>
                    <h3 id="delivery-modal-title" class="text-dark font-weight-bold mb-2">
                        {{ translate('Delivery Movement Update') }}
                    </h3>
                    <p id="delivery-modal-desc" class="text-body font-size-md mb-4 px-2" style="line-height: 1.6; font-size: 15px;"></p>
                    <hr class="my-3">
                    <div class="d-flex justify-content-center" style="gap: 12px;">
                        <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">
                            {{ translate('Close') }}
                        </button>
                        <a id="delivery-modal-link" href="#" class="btn btn-primary px-4">
                            {{ translate('View Order') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/sweet_alert.js"></script>
<script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
<script src="{{asset('public/assets/admin/js/firebase.min.js')}}"></script>
<script src="{{asset('public/assets/admin')}}/js/custom.js"></script>

@stack('script')

{!! Toastr::message() !!}

@if ($errors->any())
    <script>
        @foreach($errors->all() as $error)
        toastr.error('{{$error}}', Error, {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
    </script>
@endif
<script>
    $(document).on('ready', function () {
        if (window.localStorage.getItem('hs-builder-popover') === null) {
            $('#builderPopover').popover('show')
                .on('shown.bs.popover', function () {
                    $('.popover').last().addClass('popover-dark')
                });

            $(document).on('click', '#closeBuilderPopover', function () {
                window.localStorage.setItem('hs-builder-popover', true);
                $('#builderPopover').popover('dispose');
            });
        } else {
            $('#builderPopover').on('show.bs.popover', function () {
                return false
            });
        }

        $('.js-navbar-vertical-aside-toggle-invoker').click(function () {
            $('.js-navbar-vertical-aside-toggle-invoker i').tooltip('hide');
        });

        var megaMenu = new HSMegaMenu($('.js-mega-menu'), {
            desktop: {
                position: 'left'
            }
        }).init();

        var sidebar = $('.js-navbar-vertical-aside').hsSideNav();

        $('.js-nav-tooltip-link').tooltip({boundary: 'window'})

        $(".js-nav-tooltip-link").on("show.bs.tooltip", function (e) {
            if (!$("body").hasClass("navbar-vertical-aside-mini-mode")) {
                return false;
            }
        });

        $('.js-hs-unfold-invoker').each(function () {
            var unfold = new HSUnfold($(this)).init();
        });

        $('.js-form-search').each(function () {
            new HSFormSearch($(this)).init()
        });

        $('.js-select2-custom').each(function () {
            var select2 = $.HSCore.components.HSSelect2.init($(this));
        });

        $('.js-daterangepicker').daterangepicker();

        $('.js-daterangepicker-times').daterangepicker({
            timePicker: true,
            startDate: moment().startOf('hour'),
            endDate: moment().startOf('hour').add(32, 'hour'),
            locale: {
                format: 'M/DD hh:mm A'
            }
        });

        var start = moment();
        var end = moment();

        function cb(start, end) {
            $('#js-daterangepicker-predefined .js-daterangepicker-predefined-preview').html(start.format('MMM D') + ' - ' + end.format('MMM D, YYYY'));
        }

        $('#js-daterangepicker-predefined').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                '{{translate("Today")}}': [moment(), moment()],
                '{{translate("Yesterday") }}': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                '{{translate("Last 7 Days")}}': [moment().subtract(6, 'days'), moment()],
                '{{translate("Last 30 Days")}}': [moment().subtract(29, 'days'), moment()],
                '{{translate("This Month")}}': [moment().startOf('month'), moment().endOf('month')],
                '{{translate("Last Month")}}': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

        cb(start, end);

        $('.js-clipboard').each(function () {
            var clipboard = $.HSCore.components.HSClipboard.init(this);
        });
    });
</script>

@stack('script_2')
<audio id="myAudio">
    <source src="{{asset('public/assets/admin/sound/notification.mp3')}}" type="audio/mpeg">
</audio>

<script>
    var audio = document.getElementById("myAudio");

    function playAudio() {
        audio.play();
    }

    function pauseAudio() {
        audio.pause();
    }
</script>
<script>

    $('#check-order').on('click', function(){
        location.href = '{{route('branch.order.list',['status'=>'pending'])}}';
    })

    $('.route-alert').on('click', function (){
        let route = $(this).data('route');
        let message = $(this).data('message');
        route_alert(route, message)
    });

    function route_alert(route, message) {
        Swal.fire({
            title: '{{translate("Are you sure?")}}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#01684b',
            cancelButtonText: '{{translate("No")}}',
            confirmButtonText: '{{translate("Yes")}}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = route;
            }
        })
    }

    function form_alert(id, message) {
        Swal.fire({
            title: '{{translate("Are you sure?")}}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#01684b',
            cancelButtonText: '{{translate("No")}}',
            confirmButtonText: '{{translate("Yes")}}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $('#'+id).submit()
            }
        })
    }

    function call_demo(){
        toastr.info('Disabled for demo version!')
    }
</script>

<script>
    @php($admin_order_notification = \App\CentralLogics\Helpers::get_business_settings('admin_order_notification'))
    @php($admin_order_notification_type = \App\CentralLogics\Helpers::get_business_settings('admin_order_notification_type'))

    function showDeliveryNotification(title, message, url) {
        playAudio();

        if (typeof toastr !== 'undefined' && message) {
            toastr.info(message, title, {
                timeOut: 20000,
                closeButton: true,
                progressBar: true,
                onclick: function() {
                    if (url) window.location.href = url;
                }
            });
        }

        $('#delivery-modal-title').text(title || '{{ translate('Delivery Movement Update') }}');
        $('#delivery-modal-desc').text(message || '');
        if (url) {
            $('#delivery-modal-link').attr('href', url);
        }
        $('#delivery-popup-modal').appendTo("body").modal('show');
    }

    @if($admin_order_notification)
        var lastDeliveryEventId = null;
        @if($admin_order_notification_type == 'manual')
            setInterval(function () {
                $.get({
                    url: '{{route('branch.get-restaurant-data')}}',
                    dataType: 'json',
                    success: function (response) {
                        let data = response.data;
                        new_order_type = data.type;
                        if (data.new_order > 0) {
                            playAudio();
                            $('#popup-modal').appendTo("body").modal('show');
                        }
                        if (data.delivery_event && data.delivery_event.id && data.delivery_event.id !== lastDeliveryEventId) {
                            lastDeliveryEventId = data.delivery_event.id;
                            let title = data.delivery_event.title || '{{ translate('Delivery Movement Update') }}';
                            let desc = data.delivery_event.description || data.delivery_event.body || '';
                            let url = data.delivery_event.url || ('{{ url('/branch/orders/details') }}/' + data.delivery_event.order_id);
                            showDeliveryNotification(title, desc, url);
                        }
                    },
                });
            }, 10000);
        @endif

        @if($admin_order_notification_type == 'firebase')
            @php($fcm_credentials = \App\CentralLogics\Helpers::get_business_settings('firebase_message_config'))
            var firebaseConfig = {
                apiKey: "{{isset($fcm_credentials['apiKey']) ? $fcm_credentials['apiKey'] : ''}}",
                authDomain: "{{isset($fcm_credentials['authDomain']) ? $fcm_credentials['authDomain'] : ''}}",
                projectId: "{{isset($fcm_credentials['projectId']) ? $fcm_credentials['projectId'] : ''}}",
                storageBucket: "{{isset($fcm_credentials['storageBucket']) ? $fcm_credentials['storageBucket'] : ''}}",
                messagingSenderId: "{{isset($fcm_credentials['messagingSenderId']) ? $fcm_credentials['messagingSenderId'] : ''}}",
                appId: "{{isset($fcm_credentials['appId']) ? $fcm_credentials['appId'] : ''}}",
                measurementId: "{{isset($fcm_credentials['measurementId']) ? $fcm_credentials['measurementId'] : ''}}"
            };


            firebase.initializeApp(firebaseConfig);
            const messaging = firebase.messaging();

            function startFCM() {
                messaging
                    .requestPermission()
                    .then(function() {
                        return messaging.getToken();
                    })
                    .then(function(token) {
                        subscribeTokenToBackend(token, 'grofresh_branch_{{ auth('branch')->id() }}_message');
                    }).catch(function(error) {
                    console.error('Error getting permission or token:', error);
                });
            }

            function subscribeTokenToBackend(token, topic) {
                fetch('{{url('/')}}/subscribeToTopic', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ token: token, topic: topic })
                }).then(response => {
                    if (response.status < 200 || response.status >= 400) {
                        return response.text().then(text => {
                            throw new Error(`Error subscribing to topic: ${response.status} - ${text}`);
                        });
                    }
                }).catch(error => {
                    console.error('Subscription error:', error);
                });
            }

            messaging.onMessage(function(payload) {
                let pData = payload.data || {};
                let pNotif = payload.notification || {};

                if(pData.order_id && pData.type == "order_request"){
                    playAudio();
                    $('#popup-modal').appendTo("body").modal('show');
                } else if (pData.type == "delivery_man_confirmed" || pData.type == "delivery_man_started") {
                    let title = pData.title || pNotif.title || '{{ translate('Delivery Movement Update') }}';
                    let desc = pData.description || pData.body || pNotif.body || '';
                    let orderId = pData.order_id || '';
                    let url = orderId ? ('{{ url('/branch/orders/details') }}/' + orderId) : (pData.click_action || '');
                    showDeliveryNotification(title, desc, url);
                }
            });

            startFCM();
        @endif
    @endif

</script>

<!-- IE Support -->
<script>
    if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write('<script src="{{asset('public/assets/admin')}}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
</script>
</body>
</html>
