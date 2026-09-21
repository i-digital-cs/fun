<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-text">
    <header class="border-b border-border/60 bg-surface/80 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ url('/') }}" class="text-lg font-semibold text-primary">{{ config('app.name') }}</a>
            <div class="flex items-center gap-3 text-sm">
                <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-2">
                    @csrf
                    <label for="locale" class="sr-only">{{ __('ui.language') }}</label>
                    <select id="locale" name="locale" onchange="this.form.submit()" class="rounded-lg border border-border bg-surface px-2 py-1 text-sm">
                        <option value="en" @selected(app()->getLocale() === 'en')>{{ __('ui.english') }}</option>
                        <option value="ar" @selected(app()->getLocale() === 'ar')>{{ __('ui.arabic') }}</option>
                    </select>
                </form>
                @auth
                    <a href="{{ route('events.index') }}" class="text-muted hover:text-text">{{ __('events.discover') }}</a>
                    <a href="{{ route('settings.location.edit') }}" class="text-muted hover:text-text">{{ __('location.location_settings') }}</a>
                    <a href="{{ route('events.mine') }}" class="text-muted hover:text-text">{{ __('events.my_events') }}</a>
                    @if (auth()->user()->isPlatformAdmin())
                        <a href="{{ route('admin.cities.index') }}" class="text-muted hover:text-text">{{ __('location.cities') }}</a>
                        <a href="{{ route('admin.categories.index') }}" class="text-muted hover:text-text">{{ __('catalog.categories') }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-muted hover:text-text">{{ __('ui.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('events.index') }}" class="text-muted hover:text-text">{{ __('events.discover') }}</a>
                    <a href="{{ route('login') }}" class="text-muted hover:text-text">{{ __('ui.login') }}</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-accent px-3 py-1.5 font-semibold text-white">{{ __('ui.register') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger" role="alert">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
