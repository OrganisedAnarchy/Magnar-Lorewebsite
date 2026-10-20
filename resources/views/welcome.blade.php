<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'The World of Magnar') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div
        id="app"
        data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
        data-user-name="{{ auth()->user()?->name }}"
        data-celestial-bodies="{{ json_encode(config('celestial-bodies')) }}"
    ></div>
</body>
</html>
