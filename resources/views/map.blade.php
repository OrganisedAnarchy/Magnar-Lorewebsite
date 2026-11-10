<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>World map · {{ config('app.name', 'The World of Magnar') }}</title>
    @vite(['resources/scss/map.scss', 'resources/js/map.js'])
</head>
<body>
    <div
        id="map-app"
        data-index-url="{{ route('map.locations.index') }}"
        data-show-url="{{ route('map.locations.show', ['location' => '__ID__']) }}"
        data-home-url="{{ route('home') }}"
    ></div>
</body>
</html>
