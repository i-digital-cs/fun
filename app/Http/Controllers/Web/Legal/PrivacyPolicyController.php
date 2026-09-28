<?php

namespace App\Http\Controllers\Web\Legal;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\View\View;

class PrivacyPolicyController extends Controller
{
    public function __invoke(): View
    {
        $brand = site_display_name();
        $email = (string) __('privacy.contact_email');

        $sections = collect(__('privacy.sections'))
            ->map(function (array $section) use ($brand, $email): array {
                $body = str_replace(
                    [':brand', ':email'],
                    [$brand, $email],
                    (string) ($section['body'] ?? ''),
                );

                return [
                    'title' => (string) ($section['title'] ?? ''),
                    'paragraphs' => array_values(array_filter(array_map(
                        static fn (string $p): string => trim($p),
                        preg_split("/\n\s*\n/", $body) ?: [],
                    ))),
                ];
            })
            ->all();

        return view('legal.privacy', [
            'seo' => Seo::forPrivacy(),
            'brand' => $brand,
            'email' => $email,
            'sections' => $sections,
            'intro' => str_replace(
                [':brand', ':email'],
                [$brand, $email],
                (string) __('privacy.intro'),
            ),
        ]);
    }
}
