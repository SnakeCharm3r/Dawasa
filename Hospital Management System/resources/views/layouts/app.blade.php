<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hospital Management System')</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon_io/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('assets/images/favicon_io/site.webmanifest') }}">

    <!-- Vite Assets -->
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    <!-- Page Specific Styles -->
    @stack('styles')
</head>
<body class="bg-light">
    <!-- Overlay for mobile sidebar -->
    <div id="overlay" class="overlay"></div>

    @auth
        <!-- Header / Topbar -->
        @include('includes.header')

        <!-- Sidebar Navigation -->
        @include('includes.sidebar')
    @endauth

    <!-- Main Content Area -->
    <main id="content" class="{{ auth()->check() ? 'content' : '' }}">
        <div class="container-fluid py-4">
            @yield('content')
        </div>

        @auth
            @include('includes.footer')
        @endauth
    </main>

    <!-- Scripts -->
    @auth
        <script src="{{ asset('assets/js/sidebar.js') }}"></script>
    @endauth

    <!-- Page Specific Scripts -->
    @stack('scripts')
</body>
</html>
