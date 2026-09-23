{{-- Self-contained error shell (works even if Vite/DB is down). --}}
@php
    $code = (int) ($code ?? 500);
    $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    try {
        $brand = function_exists('site_display_name') ? site_display_name() : (string) config('app.name', 'Funity');
    } catch (\Throwable) {
        $brand = (string) config('app.name', 'Funity');
    }
    $homeUrl = '/';
    $dashboardUrl = null;
    try {
        $homeUrl = route('home');
        if (auth()->check()) {
            $dashboardUrl = route('dashboard');
        }
    } catch (\Throwable) {
        // keep defaults
    }
    $titleKey = "errors.{$code}_title";
    $messageKey = "errors.{$code}_message";
    $pageTitle = __($titleKey);
    if ($pageTitle === $titleKey) {
        $pageTitle = __('errors.500_title');
    }
    $pageMessage = __($messageKey);
    if ($pageMessage === $messageKey) {
        $pageMessage = __('errors.500_message');
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ $pageTitle }} · {{ $brand }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700" rel="stylesheet">
    <style>
        :root {
            --err-bg: #f5f7fb;
            --err-ink: #0f172a;
            --err-muted: #64748b;
            --err-indigo: #4f46e5;
            --err-orange: #f97316;
            --err-cube: #c7d2fe;
            --err-cube-side: #e0e7ff;
            --err-cube-top: #eef2ff;
            --err-cube-hot: #f97316;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "IBM Plex Sans", "IBM Plex Sans Arabic", system-ui, sans-serif;
            color: var(--err-ink);
            background:
                radial-gradient(ellipse 80% 50% at 20% 0%, rgba(79, 70, 229, 0.14), transparent 55%),
                radial-gradient(ellipse 70% 45% at 90% 10%, rgba(249, 115, 22, 0.12), transparent 50%),
                var(--err-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }
        .error-shell {
            width: 100%;
            max-width: 28rem;
            text-align: center;
        }
        .error-brand {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--err-indigo);
            margin-bottom: 1rem;
        }
        .error-code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: rgba(79, 70, 229, 0.1);
            color: var(--err-indigo);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.35rem 0.75rem;
            margin-bottom: 0.75rem;
        }
        .error-title {
            margin: 0 0 0.5rem;
            font-size: clamp(1.5rem, 4vw, 1.85rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .error-message {
            margin: 0 auto 1.5rem;
            max-width: 24rem;
            font-size: 0.95rem;
            line-height: 1.55;
            color: var(--err-muted);
        }
        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            justify-content: center;
            margin-top: 0.5rem;
        }
        .error-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            padding: 0.7rem 1.15rem;
            font-size: 0.85rem;
            font-weight: 800;
            text-decoration: none;
            transition: filter 0.15s ease, transform 0.15s ease;
        }
        .error-btn:hover { filter: brightness(1.05); transform: translateY(-1px); }
        .error-btn--primary {
            background: linear-gradient(90deg, #f97316, #f59e0b);
            color: #fff;
            box-shadow: 0 10px 24px -10px rgba(249, 115, 22, 0.55);
        }
        .error-btn--ghost {
            background: #fff;
            color: #334155;
            border: 1px solid #e2e8f0;
        }
        .error-visual-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            margin: 0.25rem 0 1.5rem;
            min-height: 220px;
        }

        /* From Uiverse.io by csemszepp — scoped */
        @keyframes error-cubes-hue {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }
        .error-cubes {
            position: relative;
            top: -24px;
            width: 180px;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: skewY(-20deg);
            animation: error-cubes-hue 5s linear infinite;
        }
        .error-cubes__cube {
            position: relative;
            z-index: 2;
        }
        .error-cubes__cube:nth-child(2) {
            z-index: 1;
            translate: -48px -48px;
        }
        .error-cubes__cube:nth-child(3) {
            z-index: 3;
            translate: 48px 48px;
        }
        .error-cubes__cube > div {
            position: absolute;
            display: flex;
            flex-direction: column;
            gap: 24px;
            translate: calc(-56px * var(--x)) calc(-48px * var(--y));
        }
        .error-cubes__cube span {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 40px;
            background: var(--err-cube);
            z-index: calc(1 * var(--i));
            transition: 1.5s;
        }
        .error-cubes__cube span:hover {
            transition: 0s;
            background: var(--err-cube-hot);
            filter: drop-shadow(0 0 24px var(--err-cube-hot));
        }
        .error-cubes__cube span:hover::before,
        .error-cubes__cube span:hover::after {
            transition: 0s;
            background: var(--err-cube-hot);
        }
        .error-cubes__cube span::before {
            content: "";
            position: absolute;
            left: -32px;
            width: 32px;
            height: 100%;
            background: var(--err-cube-side);
            transform-origin: right;
            transform: skewY(45deg);
            transition: 1.5s;
        }
        .error-cubes__cube span::after {
            content: "";
            position: absolute;
            top: -32px;
            left: 0;
            width: 100%;
            height: 32px;
            background: var(--err-cube-top);
            transform-origin: bottom;
            transform: skewX(45deg);
            transition: 1.5s;
        }

        /* From Uiverse.io by AatreyuShau — runner (secondary) */
        .error-runner {
            scale: 0.45;
            position: relative;
            width: 200px;
            height: 120px;
            translate: 6px -10px;
            opacity: 0.85;
        }
        .error-runner svg { position: absolute; top: 0; left: 0; }
        .error-runner .head {
            translate: 27px -30px;
            z-index: 3;
            animation: error-runner-bob 1s infinite ease-in;
        }
        .error-runner .bod {
            translate: 0 30px;
            z-index: 3;
            animation: error-runner-bob 1s infinite ease-in-out;
        }
        .error-runner .legr {
            translate: 75px 135px;
            z-index: 0;
            animation: error-runner-rstep 1s infinite ease-in;
            animation-delay: 0.45s;
        }
        .error-runner .legl {
            translate: 30px 155px;
            z-index: 3;
            animation: error-runner-lstep 1s infinite ease-in;
        }
        .error-runner #gnd {
            translate: -140px 0;
            rotate: 10deg;
            z-index: -1;
            filter: blur(0.5px) drop-shadow(1px 3px 5px #000000);
            opacity: 0.25;
            animation: error-runner-scroll 5s infinite linear;
        }
        @keyframes error-runner-bob {
            0% { transform: translateY(0) rotate(3deg); }
            5% { transform: translateY(0) rotate(3deg); }
            25% { transform: translateY(5px) rotate(0deg); }
            50% { transform: translateY(0) rotate(-3deg); }
            70% { transform: translateY(5px) rotate(0deg); }
            100% { transform: translateY(0) rotate(3deg); }
        }
        @keyframes error-runner-lstep {
            0% { transform: translateY(0) rotate(-5deg); }
            33% { transform: translateY(-15px) translate(32px) rotate(35deg); }
            66% { transform: translateY(0) translate(25px) rotate(-25deg); }
            100% { transform: translateY(0) rotate(-5deg); }
        }
        @keyframes error-runner-rstep {
            0% { transform: translateY(0) translate(0) rotate(-5deg); }
            33% { transform: translateY(-10px) translate(30px) rotate(35deg); }
            66% { transform: translateY(0) translate(20px) rotate(-25deg); }
            100% { transform: translateY(0) translate(0) rotate(-5deg); }
        }
        @keyframes error-runner-scroll {
            0% { transform: translateY(25px) translate(50px); opacity: 0; }
            33% { opacity: 0.25; }
            66% { opacity: 0.25; }
            to { transform: translateY(-50px) translate(-100px); opacity: 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            .error-cubes { animation: none; }
            .error-runner .head,
            .error-runner .bod,
            .error-runner .legr,
            .error-runner .legl,
            .error-runner #gnd { animation: none; }
        }
        @media (max-width: 420px) {
            .error-cubes {
                scale: 0.85;
                top: -12px;
            }
            .error-visual-wrap { min-height: 190px; }
        }
    </style>
</head>
<body>
    <div class="error-shell">
        <p class="error-brand">{{ $brand }}</p>
        <div class="error-visual-wrap" aria-hidden="true">
            @include('errors.partials.cubes')
            @include('errors.partials.runner')
        </div>
        <span class="error-code">{{ __('errors.code_label', ['code' => $code]) }}</span>
        <h1 class="error-title">{{ $pageTitle }}</h1>
        <p class="error-message">{{ $pageMessage }}</p>
        <div class="error-actions">
            <a class="error-btn error-btn--primary" href="{{ $homeUrl }}">{{ __('errors.go_home') }}</a>
            @if ($dashboardUrl)
                <a class="error-btn error-btn--ghost" href="{{ $dashboardUrl }}">{{ __('errors.go_dashboard') }}</a>
            @endif
        </div>
    </div>
</body>
</html>
