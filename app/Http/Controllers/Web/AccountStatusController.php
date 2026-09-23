<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class AccountStatusController extends Controller
{
    public function deactivated(): View
    {
        return view('account.deactivated', [
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }
}
