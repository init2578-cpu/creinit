<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'CRE iNiT Kolda') }}</title>
        
        <!-- Social Media Meta Tags -->
        <meta property="og:title" content="CRE iNiT Kolda">
        <meta property="og:description" content="Plateforme de gestion de l'établissement CRE iNiT Kolda.">
        <meta property="og:image" content="{{ asset('images/logo-cre.png') }}">
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="summary_large_image">
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo-cre.png') }}">

        <!-- Fonts (Inter & Sora) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @vite(['src/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        @inertia
    </body>
</html>
