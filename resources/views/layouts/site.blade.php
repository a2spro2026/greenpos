<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GreenPOS') — GreenPOS</title>
    <meta name="description" content="@yield('meta_description', 'GreenPOS — plateforme SaaS tout-en-un pour piloter commerce, stock, ventes et croissance.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>
<body class="site-body">
@include('site.partials.header')
<main>
    @yield('content')
</main>
@include('site.partials.footer')
</body>
</html>
