<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/admin.js'])
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
        .info-tip { position: relative; display: inline-block; margin-left: .35rem; }
        .info-tip button { width: 1.2rem; height: 1.2rem; padding: 0; border: 1px solid #9a93ad; border-radius: 50%; background: none; color: #cdb8ff; font: italic 700 .75rem/1 serif; cursor: help; }
        .info-tip .info-tip-text { display: none; position: absolute; left: 0; top: 1.6rem; z-index: 10; width: 22rem; max-width: 80vw; padding: .6rem .75rem; background: #1f1b2b; border: 1px solid #3a3450; border-radius: 4px; color: #e8e4f0; font: 400 .85rem/1.4 system-ui, sans-serif; }
        .info-tip:hover .info-tip-text, .info-tip:focus-within .info-tip-text { display: block; }
        .date-group { margin-top: 1rem; border: 1px solid #3a3450; border-radius: 4px; }
        .date-group legend { color: #cdb8ff; font-weight: 600; }
        .date-flags { display: flex; gap: 1.5rem; }
        label.checkbox { display: flex; align-items: center; gap: .4rem; font-weight: 400; }
        label.checkbox input { width: auto; }
        .date-fields { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .date-control { position: relative; }
        .date-control input { padding: 1.15rem .5rem .35rem; }
        .date-control label { position: absolute; top: 50%; left: .6rem; margin: 0; color: #9a93ad; font-size: .9rem; font-weight: 400; line-height: 1; pointer-events: none; transform: translateY(-50%); transition: top .15s ease, transform .15s ease, font-size .15s ease, color .15s ease; }
        .date-control input:focus + label, .date-control input:not(:placeholder-shown) + label { top: .25rem; color: #cdb8ff; font-size: .7rem; transform: none; }
        .draft-banner { display: flex; gap: .75rem; align-items: center; padding: .5rem 1rem; background: #3a3014; border-radius: 4px; }
        .link-button { background: none; border: 0; color: #cdb8ff; font: inherit; text-decoration: underline; cursor: pointer; padding: 0; }
        .form-actions { display: flex; align-items: center; gap: 1rem; }
        .table-scroll { overflow-x: auto; }
        .panel { margin-top: 1.5rem; padding: 0 1rem 1rem; border: 1px solid #3a3450; border-radius: 4px; }
        .panel h2 { font-size: 1.1rem; color: #cdb8ff; }
        textarea.short { min-height: 6rem; }
        .btn.small { margin-top: 0; padding: .3rem .7rem; font-size: .9rem; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .tabs { display: flex; flex-wrap: wrap; gap: .4rem; margin: .5rem 0 1rem; }
        .tab { padding: .3rem .75rem; background: none; border: 1px solid #3a3450; border-radius: 999px; color: #cdb8ff; font: inherit; font-size: .9rem; cursor: pointer; }
        .tab.active { background: #6b4fd8; border-color: #6b4fd8; color: #fff; }
        .badge { margin-left: .25rem; padding: 0 .4rem; background: #14121a; border-radius: 999px; font-size: .75rem; }
        .ipa-group { margin-top: .75rem; }
        .ipa-group-label { color: #9a93ad; font-size: .8rem; margin-bottom: .25rem; }
        .ipa-keys { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }
        .ipa-key { min-width: 2.4rem; height: 2.4rem; padding: 0 .5rem; background: #1f1b2b; border: 1px solid #3a3450; border-radius: 4px; color: inherit; font-size: 1.15rem; cursor: pointer; }
        .ipa-key:hover { border-color: #cdb8ff; }
        .ipa-key.selected { background: #6b4fd8; border-color: #6b4fd8; color: #fff; }
        .ipa-custom { width: 8rem; }
        .phoneme-summary { margin-top: 1rem; font-size: 1.1rem; word-break: break-word; }
        .generator-controls { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
        .inline-field { margin-top: 0; }
        .inline-field input { width: 8rem; }
        .word-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .75rem; }
        .word-chip { padding: .25rem .6rem; background: #1f1b2b; border: 1px solid #3a3450; border-radius: 999px; color: inherit; font: inherit; cursor: pointer; }
        .word-chip:hover:not(:disabled) { border-color: #cdb8ff; }
        .word-chip.added { opacity: .45; cursor: default; }
        .coverage-bar { height: .6rem; margin: .4rem 0; background: #1f1b2b; border-radius: 999px; overflow: hidden; }
        .coverage-fill { height: 100%; background: #6b4fd8; }
        .lexicon-rows { display: flex; flex-direction: column; gap: .5rem; margin: 1rem 0; }
        .lexicon-row { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: .5rem; }
        .combo { position: relative; }
        .combo-list { position: absolute; left: 0; right: 0; z-index: 10; margin: 0; padding: 0; list-style: none; background: #1f1b2b; border: 1px solid #3a3450; border-radius: 4px; max-height: 14rem; overflow-y: auto; }
        .combo-list li { padding: .4rem .6rem; cursor: pointer; }
        .combo-list li:hover { background: #3a3450; }
        .tag-input { display: flex; flex-wrap: wrap; gap: .35rem; padding: .35rem; background: #1f1b2b; border: 1px solid #3a3450; border-radius: 4px; }
        .tag-input input { flex: 1; min-width: 8rem; width: auto; border: 0; padding: .2rem; }
        .tag { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .5rem; background: #6b4fd8; border-radius: 999px; font-size: .9rem; }
        .tag button { padding: 0; background: none; border: 0; color: inherit; cursor: pointer; }
        .date-fields { padding: 0 .5rem .5rem; }
        @media (max-width: 40rem) {
            header { flex-wrap: wrap; gap: .75rem; }
            .lexicon-row { grid-template-columns: 1fr 1fr; }
            .date-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
    @stack('head')
</head>
<body>
    <header>
        <strong>Admin</strong>
        <a href="{{ route('admin.entries.index') }}">Lore</a>
                <a href="{{ route('admin.languages.index') }}">Languages</a>
                <a href="{{ route('admin.books.index') }}">Books</a>
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
