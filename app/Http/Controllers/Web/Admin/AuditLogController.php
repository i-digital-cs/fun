<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403, __('admin.super_admin_only'));

        $logs = AdminAuditLog::query()
            ->with('actor:id,name,email')
            ->latest()
            ->paginate(30);

        return view('admin.audit.index', compact('logs'));
    }
}
