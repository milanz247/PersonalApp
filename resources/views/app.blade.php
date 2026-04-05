<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#09090b">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Personal HQ">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.svg">
        @if (file_exists(public_path('build/manifest.webmanifest')))
            <link rel="manifest" href="/build/manifest.webmanifest">
        @else
            <link rel="manifest" href="/manifest.webmanifest">
        @endif

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&family=playfair-display:400,400i,600,700&family=dancing-script:400,600,700&display=swap" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
