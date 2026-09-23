<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function edit(SiteSettingsService $service): View
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403, __('admin.super_admin_only'));

        return view('admin.settings.edit', [
            'settings' => $service->current(),
            'logoUrl' => $service->logoUrl(),
        ]);
    }

    public function update(
        UpdateSiteSettingsRequest $request,
        SiteSettingsService $service,
    ): RedirectResponse {
        abort_unless($request->user()?->isSuperAdmin(), 403, __('admin.super_admin_only'));

        try {
            $service->update($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['display_name' => $exception->getMessage()]);
        }

        return back()->with('status', __('admin.settings_saved'));
    }
}
