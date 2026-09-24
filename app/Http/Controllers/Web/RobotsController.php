<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = rtrim((string) config('app.url'), '/').'/sitemap.xml';

        $body = <<<TXT
User-agent: *
Disallow: /admin
Disallow: /dashboard
Disallow: /settings
Disallow: /chats
Disallow: /identity
Disallow: /email
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /my-events
Disallow: /favorites
Disallow: /report
Disallow: /account
Disallow: /api/

Allow: /
Allow: /events
Allow: /safety
Allow: /u/

Sitemap: {$sitemap}

TXT;

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
