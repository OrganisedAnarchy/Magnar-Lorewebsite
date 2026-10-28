<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/js/admin-editor.js'])
    <title>@yield('title', 'Admin') · {{ config('app.name', 'The World of Magnar') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #14121a; color: #e8e4f0; }
        header { display: flex; gap: 1.5rem; align-items: center; padding: .75rem 1.5rem; background: #1f1b2b; }
        header a, header button { color: #cdb8ff; background: none; border: 0; font: inherit; cursor: pointer; text-decoration: none; }
        header .spacer { flex: 1; }
        main { max-width: 60rem; margin: 1.5rem auto; padding: 0 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .5rem; border-bottom: 1px solid #3a3450; }
        label { display: block; margin-top: 1rem; font-weight: 600; }
        input, select, textarea { width: 100%; box-sizing: border-box; padding: .5rem; background: #1f1b2b; color: inherit; border: 1px solid #3a3450; border-radius: 4px; font: inherit; }
        textarea { min-height: 18rem; }
        .btn { display: inline-block; margin-top: 1rem; padding: .5rem 1rem; background: #6b4fd8; color: #fff; border: 0; border-radius: 4px; cursor: pointer; text-decoration: none; font: inherit; }
        .btn-danger { background: #b03a48; margin: 0; }
        .error { color: #ff8a98; font-size: .9rem; }
        .status { padding: .5rem 1rem; background: #23402e; border-radius: 4px; }
        .inline { display: inline; }
        .filters { display: flex; flex-wrap: wrap; gap: .5rem; margin: 1rem 0; }
        .filters a { padding: .25rem .75rem; border: 1px solid #3a3450; border-radius: 999px; color: #cdb8ff; text-decoration: none; font-size: .9rem; }
        .filters a.active { background: #6b4fd8; color: #fff; border-color: #6b4fd8; }
        .hint { color: #9a93ad; font-size: .85rem; }
    </style>
    @stack('head')
</head>
<body>
    <header>
        <strong>Admin</strong>
        <a href="{{ route('admin.entries.index') }}">Lore</a>
        <span class="spacer"></span>
        <a href="{{ route('home') }}">View site</a>
        <form class="inline" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Log out</button>
        </form>
    </header>
    <main>
        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
