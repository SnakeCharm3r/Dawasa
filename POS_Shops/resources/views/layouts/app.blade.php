@php
    use App\Models\Setting;
    $appName     = Setting::get('app_name', config('app.name', 'SimplePOS'));
    $faviconPath = Setting::get('favicon');
    $idleMinutes = (int) Setting::get('idle_timeout', 30);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appName }}</title>

        @if($faviconPath)
            <link rel="icon" href="{{ Storage::url($faviconPath) }}" />
        @else
            <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='18' fill='%232563eb'/><text y='.9em' font-size='75' x='12' fill='white' font-family='Arial' font-weight='bold'>S</text></svg>" />
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50"
          x-data="idleTimer({{ $idleMinutes }})"
          x-init="start()"
          @mousemove="reset()" @keydown="reset()" @click="reset()" @scroll.window="reset()">
        <div class="min-h-screen flex">
            @include('layouts.sidebar')

            <div class="flex-1 flex flex-col min-w-0">
                @include('layouts.topbar')

                <main class="flex-1 overflow-y-auto p-6">
                    @if(session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200">
                            <p class="text-sm text-green-800">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                            <p class="text-sm text-red-800">{{ session('error') }}</p>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
