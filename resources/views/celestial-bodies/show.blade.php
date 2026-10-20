<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $body['name'] }} · {{ config('app.name', 'The World of Magnar') }}</title>
    @vite(['resources/scss/app.scss'])
</head>
<body>
    <main class="celestial-page">
        <header class="site-header">
            <a class="wordmark" href="/" aria-label="The World of Magnar home">
                <span class="wordmark-mark" aria-hidden="true">M</span>
                <span>THE WORLD OF <span class="wordmark-light">MAGNAR</span></span>
            </a>
            <a class="button button-quiet" href="/">Return to the system</a>
        </header>

        <article class="celestial-entry" aria-labelledby="body-title">
            <p class="eyebrow"><span class="eyebrow-line"></span> {{ $body['kind'] }} of Magnar</p>
            <h1 id="body-title">{{ $body['name'] }}</h1>
            <p class="celestial-summary">{{ $body['summary'] }}</p>

            <div class="celestial-details">
                @foreach ($body['details'] as $detail)
                    <p>{{ $detail }}</p>
                @endforeach
            </div>

            <a class="button button-primary" href="/">Return to the celestial system <span aria-hidden="true">↗</span></a>
        </article>

        <footer class="site-footer">
            <span>THE WORLD OF MAGNAR</span>
            <span>Make room for wonder.</span>
        </footer>
    </main>
</body>
</html>
