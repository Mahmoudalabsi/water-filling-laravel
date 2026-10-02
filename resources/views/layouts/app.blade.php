<!DOCTYPE html>
<html lang="{{ str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en' }}"
      dir="{{ str_starts_with(app()->getLocale(), 'ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'تعبئة المياه')</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <!-- Tailwind via CDN (no Node build step needed on Render) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: {} }
        }
    </script>

    <!-- Google Fonts: Tajawal (Arabic) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Tajawal', system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen text-slate-900 dark:text-slate-100 antialiased">
    @yield('body')
</body>
</html>
