<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seo = $seo ?? [
            'title' => trim($__env->yieldContent('title')) ?: null,
            'description' => null,
            'image' => null,
            'url' => url()->current(),
            'type' => 'website',
            'robots' => 'noindex,nofollow',
            'jsonLd' => null,
        ];
    @endphp
    <x-seo.meta
        :title="$seo['title'] ?? null"
        :description="$seo['description'] ?? null"
        :image="$seo['image'] ?? null"
        :url="$seo['url'] ?? null"
        :type="$seo['type'] ?? 'website'"
        :robots="$seo['robots'] ?? 'noindex,nofollow'"
        :json-ld="$seo['jsonLd'] ?? null"
    />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="relative flex min-h-screen flex-col antialiased">
    <x-community.ambient />

    <x-community.navbar />

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6">
        @if (session('status'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <x-community.footer />

    <x-location-modal />

    <x-ui.cancel-filters />
    <x-ui.confirm-dialog />
    <x-ui.page-loader />
    @auth
        <x-ui.chat-toast-host />
    @endauth

    @stack('scripts')
</body>
</html>
