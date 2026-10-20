<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $mode === 'register' ? 'Create an account' : 'Sign in' }} · {{ config('app.name', 'Magnar Lore') }}</title>
    @vite(['resources/scss/app.scss'])
</head>
<body>
    <main class="auth-page">
        <section class="auth-card" aria-labelledby="auth-title">
            <a class="auth-brand wordmark" href="/" aria-label="Magnar Lore home">
                <span class="wordmark-mark" aria-hidden="true">M</span>
                <span>MAGNAR <span class="wordmark-light">LORE</span></span>
            </a>

            <header class="auth-heading">
                <p class="eyebrow"><span class="eyebrow-line"></span> Your story awaits <span class="eyebrow-line"></span></p>
                <h1 id="auth-title">{{ $mode === 'register' ? 'Create your account' : 'Welcome back' }}</h1>
                <p>{{ $mode === 'register' ? 'Start gathering the pieces of your world.' : 'Step back into the worlds you imagine.' }}</p>
            </header>

            @if ($errors->any())
                <div class="form-error" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form
                class="auth-form"
                action="{{ $mode === 'register' ? route('register.store') : route('login.store') }}"
                method="post"
            >
                @csrf

                @if ($mode === 'register')
                    <div class="form-field">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="form-field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required @if ($mode === 'login') autofocus @endif>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="{{ $mode === 'register' ? 'new-password' : 'current-password' }}" required>
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                @if ($mode === 'register')
                    <div class="form-field">
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                @else
                    <label class="remember-row" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1">
                        Remember me
                    </label>
                @endif

                <button class="button button-primary auth-submit" type="submit">
                    {{ $mode === 'register' ? 'Create account' : 'Sign in' }}
                    <span aria-hidden="true">↗</span>
                </button>
            </form>

            <p class="auth-switch">
                @if ($mode === 'register')
                    Already have an account? <a href="{{ route('login') }}">Sign in</a>
                @else
                    New to Magnar Lore? <a href="{{ route('register') }}">Create an account</a>
                @endif
            </p>
            <a class="auth-back" href="/">← Back to the welcome page</a>
        </section>
    </main>
</body>
</html>
