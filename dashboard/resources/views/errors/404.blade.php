<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>الصفحة غير موجودة 404</title>

    <link rel="shortcut icon" href="favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased flex items-center justify-center min-h-screen p-6">

    <div class="max-w-4xl w-full bg-white rounded-2xl shadow-xl shadow-slate-100 p-8 md:p-12 border border-slate-100 transition-all duration-300 hover:shadow-2xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">

            <div class="flex justify-center order-first md:order-last">
                <div class="relative w-64 md:w-80 max-w-full animate-bounce-slow">
                    <img class="w-full h-auto drop-shadow-md"
                        src="{{ asset('public/assets/admin/svg/illustrations/think.svg') }}"
                        alt="صفحة غير موجودة">
                </div>
            </div>

            <div class="text-center md:text-right space-y-6">
                <div>
                    <h1 class="text-7xl md:text-8xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-purple-600 select-none">
                        404
                    </h1>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">عذراً، الصفحة غير متوفرة!</h2>
                </div>

                <p class="text-slate-500 text-base leading-relaxed max-w-md mx-auto md:mx-0">
                    يبدو أن الرابط الذي حاولت الوصول إليه غير صحيح، أو تم نقل الصفحة، أو أنها لم تعد موجودة نهائياً.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center md:justify-start pt-2">
                    @if (auth('branch')->check())
                        <a href="{{ route('branch.dashboard') }}"
                           class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow-md shadow-indigo-100 hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all active:scale-95 duration-200">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            العودة إلى لوحة تحكم الفرع
                        </a>
                    @else
                        <a href="{{ route('admin.dashboard') }}"
                           class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow-md shadow-indigo-100 hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all active:scale-95 duration-200">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            العودة إلى لوحة تحكم الإدارة
                        </a>
                    @endif

                    <button onclick="history.back()"
                       class="inline-flex items-center justify-center px-6 py-3 bg-slate-100 text-slate-700 font-semibold rounded-xl hover:bg-slate-200 transition-all active:scale-95 duration-200 cursor-pointer">
                        الرجوع للخلف
                    </button>
                </div>
            </div>

        </div>

        <div class="mt-12 pt-6 border-t border-slate-100 text-center text-xs text-slate-400">
            إذا كنت تعتقد أن هذا الخطأ من جانبنا، يرجى إبلاغ مطور النظام.
        </div>
    </div>

    <style>
        @keyframes bounceSlow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .animate-bounce-slow {
            animation: bounceSlow 3.5s ease-in-out infinite;
        }
    </style>

    <script src="{{ asset('public/assets/admin/js/theme.min.js') }}"></script>
</body>

</html>
