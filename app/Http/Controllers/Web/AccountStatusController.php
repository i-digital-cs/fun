<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AccountStatusController extends Controller
{
    public function deactivated(): View
    {
        return view('account.deactivated');
    }
}
