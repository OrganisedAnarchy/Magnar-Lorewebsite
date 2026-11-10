<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/scss/admin.scss', 'resources/js/admin.js'])
    <title>@yield('title', 'Admin') · {{ config('app.name', 'The World of Magnar') }}</title>
    @stack('head')
</head>
<body>
    <header>
        <strong>Admin</strong>
        <a href="{{ route('admin.entries.index') }}">Lore</a>
        <a href="{{ route('admin.languages.index') }}">Languages</a>
        <a href="{{ route('admin.books.index') }}">Books</a>
        <a href="{{ route('admin.locations.index') }}">Locations</a>
        <span class="spacer"></span>
        <a href="{{ route('home') }}">View site</a>
        <form class="inline" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Log out</button>
        </form>
    </header>
    <div id="toast-host" data-flash="{{ json_encode(session('status')) }}"></div>
    @include('admin.daily-tasks.tray')
    <main>
        @yield('content')
    </main>
</body>
</html>
