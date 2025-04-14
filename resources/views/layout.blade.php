<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} - Conversa</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 antialiased">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white border-r border-gray-200 flex flex-col">
            <!-- Logo -->
            <div class="h-16 flex items-center px-4 border-b border-gray-200">
                <h1 class="text-xl font-semibold text-gray-800">Conversa</h1>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <div class="space-y-1">
                    <!-- Spaces Section -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Spaces</h2>
                            <button class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        @yield('spaces_list')
                    </div>

                    <!-- Direct Messages Section -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Direct Messages</h2>
                            <button class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        @yield('threads_list')
                    </div>
                </div>
            </nav>

            <!-- User Profile -->
            <div class="h-16 border-t border-gray-200 flex items-center px-4">
                <div class="flex items-center space-x-3">
                    <img src="{{ auth()->user()->avatar_url ?? 'https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg' }}" 
                         alt="Profile" 
                         class="h-8 w-8 rounded-full">
                    <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6">
                @yield('header')
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Base URL for API endpoints
        window.conversaConfig = {
            baseUrl: '{{ url('conversa') }}',
            csrfToken: '{{ csrf_token() }}',
            user: @json(auth()->user()),
            workspace: @json(auth()->user()->workspace),
        };
    </script>
    @stack('scripts')
</body>
</html>
