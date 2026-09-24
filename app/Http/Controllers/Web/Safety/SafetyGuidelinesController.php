<?php

namespace App\Http\Controllers\Web\Safety;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\View\View;

class SafetyGuidelinesController extends Controller
{
    public function __invoke(): View
    {
        return view('safety.guidelines', [
            'seo' => Seo::forSafety(),
        ]);
    }
}
