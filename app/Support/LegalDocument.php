<?php

namespace App\Support;

final class LegalDocument
{
    /**
     * @return array{brand: string, email: string, intro: string, sections: list<array{title: string, paragraphs: list<string>}>}
     */
    public static function sections(string $langKey, string $emailKey = 'contact_email'): array
    {
        $brand = site_display_name();
        $email = (string) __("{$langKey}.{$emailKey}");

        $sections = collect(__("{$langKey}.sections"))
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

        return [
            'brand' => $brand,
            'email' => $email,
            'intro' => str_replace(
                [':brand', ':email'],
                [$brand, $email],
                (string) __("{$langKey}.intro"),
            ),
            'sections' => $sections,
        ];
    }
}
