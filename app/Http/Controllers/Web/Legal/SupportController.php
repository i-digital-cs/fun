<?php

namespace App\Http\Controllers\Web\Legal;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function __invoke(): View
    {
        $brand = site_display_name();
        $email = (string) __('support.email');
        $privacyEmail = (string) __('support.privacy_email');

        $sections = collect(__('support.sections'))
            ->map(function (array $section) use ($privacyEmail): array {
                return [
                    'title' => (string) ($section['title'] ?? ''),
                    'body' => str_replace(':privacy_email', $privacyEmail, (string) ($section['body'] ?? '')),
                ];
            })
            ->all();

        return view('legal.support', [
            'seo' => Seo::forSupport(),
            'brand' => $brand,
            'email' => $email,
            'intro' => str_replace(':brand', $brand, (string) __('support.intro')),
            'sections' => $sections,
        ]);
    }
}
