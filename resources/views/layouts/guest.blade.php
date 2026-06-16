<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'IT MIS System') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.x/dist/tabler-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gradient-to-br from-indigo-900 via-indigo-800 to-purple-900 min-h-screen">
    <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-8">
        <!-- Logo & Title -->
        <div class="mb-8 text-center">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-4">
                <i class="ti ti-activity-heartbeat text-4xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">IT MIS System</h1>
            <p class="text-indigo-300 text-sm mt-1">Management Information System</p>
        </div>

        <!-- Language switcher -->
        <div class="flex space-x-3 mb-6">
            <a href="{{ route('locale.switch', 'th') }}" class="text-sm px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'th' ? 'bg-white text-indigo-700 font-semibold' : 'bg-white/10 text-white hover:bg-white/20' }} transition">ภาษาไทย</a>
            <a href="{{ route('locale.switch', 'en') }}" class="text-sm px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-white text-indigo-700 font-semibold' : 'bg-white/10 text-white hover:bg-white/20' }} transition">English</a>
        </div>

        <!-- Card -->
        <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
            {{ $slot }}
        </div>

        <p class="mt-6 text-indigo-400 text-xs">© {{ date('Y') }} IT MIS System. All rights reserved.</p>
    </div>
</body>
</html>
