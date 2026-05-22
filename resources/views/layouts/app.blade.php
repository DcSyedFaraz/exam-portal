<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app_settings.name')) — {{ config('app_settings.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    @include('components.sidebar')

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Top Header --}}
        <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between sticky top-0 z-40">
            <div class="flex items-center gap-4">
                {{-- Mobile hamburger --}}
                <button id="sidebar-toggle" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition" aria-label="Toggle sidebar">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div>
                    <h1 class="text-lg font-semibold font-heading text-gray-900">@yield('page-title', 'Dashboard')</h1>
                    @hasSection('breadcrumb')
                    <p class="text-xs text-gray-400 mt-0.5">@yield('breadcrumb')</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 hidden sm:block">{{ auth()->user()->name }}</span>
                <div class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold text-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto p-6 main-content opacity-0 translate-y-2 transition-all duration-300">
            @include('components.toast')
            @include('components.parent-banner')
            @yield('content')
        </main>

    </div>
</div>

{{-- Sidebar overlay for mobile --}}
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

{{-- Modals (rendered outside overflow containers so fixed positioning works correctly) --}}
@stack('modals')

</body>
</html>
