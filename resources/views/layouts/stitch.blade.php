// resources/views/layouts/stitch.blade.php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="bg-background text-foreground font-sans antialiased">
    @include('partials.nav')
    <main class="container mx-auto p-4">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
