<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '管理画面')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('head')
    @yield('head')
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        @include('admin.partials.header')

        @include('admin.partials.menu')

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </div>

    @stack('scripts')
    @yield('scripts')
</body>
</html>


