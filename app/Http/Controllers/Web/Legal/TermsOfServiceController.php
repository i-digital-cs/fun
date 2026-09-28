<?php

namespace App\Http\Controllers\Web\Legal;

use App\Http\Controllers\Controller;
use App\Support\LegalDocument;
use App\Support\Seo;
use Illuminate\View\View;

class TermsOfServiceController extends Controller
{
    public function __invoke(): View
    {
        $doc = LegalDocument::sections('terms');

        return view('legal.document', [
            'seo' => Seo::forTerms(),
            'title' => __('terms.title'),
            'updated' => __('terms.updated', ['date' => __('terms.updated_date')]),
            'brand' => $doc['brand'],
            'email' => $doc['email'],
            'intro' => $doc['intro'],
            'sections' => $doc['sections'],
            'anchorPrefix' => 'terms',
            'extraLinks' => [
                ['url' => route('legal.privacy'), 'label' => __('privacy.footer_link')],
                ['url' => route('legal.support'), 'label' => __('support.footer_link')],
            ],
        ]);
    }
}
