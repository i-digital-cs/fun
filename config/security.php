<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP security headers
    |--------------------------------------------------------------------------
    | Applied by App\Http\Middleware\SetSecurityHeaders on web responses.
    | HSTS stays off until HTTPS + subdomain coverage is owner-verified.
    */

    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS_ENABLED', false),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', false),
        'preload' => (bool) env('SECURITY_HSTS_PRELOAD', false),
    ],

    /*
    | Content-Security-Policy-Report-Only (never enforcing by default).
    | Keep allowlists tight; widen only when report telemetry justifies it.
    */
    'csp_report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', true),

    'csp' => [
        // Comma-separated extra hosts for script-src / connect-src / etc. (optional).
        'extra_script_src' => env('SECURITY_CSP_EXTRA_SCRIPT_SRC', ''),
        'extra_connect_src' => env('SECURITY_CSP_EXTRA_CONNECT_SRC', ''),
        'extra_img_src' => env('SECURITY_CSP_EXTRA_IMG_SRC', ''),
        'report_uri' => env('SECURITY_CSP_REPORT_URI', ''),
    ],

];
