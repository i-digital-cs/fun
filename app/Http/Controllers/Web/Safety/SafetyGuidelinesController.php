<?php

namespace App\Http\Controllers\Web\Safety;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SafetyGuidelinesController extends Controller
{
    public function __invoke(): View
    {
        return view('safety.guidelines');
    }
}
