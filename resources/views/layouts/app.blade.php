<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', config('app.name'))</title>

    <!-- CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-white shadow-sm fixed top-0 left-0 right-0 z-40">
        <nav class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16" x-data="{ isOpen: false }">
                <!-- ロゴ -->
                <div class="flex-shrink-0">
                    <a href="{{ url('/') }}" class="text-xl font-bold">
                        {{ config('app.name') }}
                    </a>
                </div>

                <!-- ハンバーガーメニューボタン -->
                <button @click="isOpen = true">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <!-- モバイルメニュー -->
                <div x-show="isOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-50 bg-white"
                    @keydown.escape.window="isOpen = false">
                    <div class="p-4">
                        <div class="flex justify-end">
                            <button @click="isOpen = false" class="text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        @include('layouts.menu')
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-6 flex-grow mt-16">
        @yield('content')
    </main>

    @yield('script')
</body>

</html>