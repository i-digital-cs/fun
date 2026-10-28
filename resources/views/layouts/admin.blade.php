<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seo = $seo ?? [
            'title' => trim($__env->yieldContent('title')) ?: __('admin.dashboard'),
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
        :absolute-title="$seo['absoluteTitle'] ?? false"
    />
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet">
    </noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-[#f5f7fb] text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <x-admin.sidebar />

        <div class="dash-main flex min-w-0 flex-1 flex-col">
            <x-admin.topbar />

            <main class="mx-auto w-full max-w-[1500px] flex-1 px-4 py-7 pb-20 sm:px-[1.875rem] lg:pb-7">
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

            <footer class="border-t border-[#e7ebf3] bg-white/80 px-4 py-4 text-center text-xs text-slate-500 sm:px-[1.875rem]">
                {{ __('admin.footer', ['brand' => $siteName ?? site_display_name()]) }}
            </footer>
        </div>
    </div>

    <x-admin.mobile-nav />

    <x-ui.cancel-filters />
    <x-ui.confirm-dialog />
    <x-ui.page-loader />

    @stack('scripts')
</body>
</html>
