<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased h-full bg-black text-gray-200">
        <div class="min-h-screen bg-black flex flex-col lg:flex-row">
            <!-- Sidebar / Mobile Header Navigation -->
            <livewire:layout.navigation />

            <!-- Page Content Main Wrapper -->
            <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="border-b border-gray-900 bg-gray-950/50 backdrop-blur sticky top-0 z-10">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8 bg-black">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
