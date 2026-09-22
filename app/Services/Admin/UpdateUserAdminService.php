<?php

namespace App\Services\Admin;

use App\Enums\AccountStatus;
use App\Enums\PlatformRole;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAdminService
{
    public function __construct(private readonly AdminAuditLogger $audit) {}

    /**
     * @param  array{name?: string, email?: string, password?: string|null, status?: string}  $data
     */
    public function update(User $actor, User $target, array $data): User
    {
        $this->assertCanEdit($actor, $target);

        return DB::transaction(function () use ($actor, $target, $data) {
            $payload = [];

            if (array_key_exists('name', $data) && filled($data['name'])) {
                $payload['name'] = $data['name'];
            }

            if (array_key_exists('email', $data) && filled($data['email'])) {
                $payload['email'] = $data['email'];
            }

            if (array_key_exists('status', $data) && filled($data['status'])) {
                $status = AccountStatus::from((string) $data['status']);

                if ($status === AccountStatus::Deactivated && ! $target->isDeactivated()) {
                    $payload['status'] = AccountStatus::Deactivated;
                    $payload['deactivated_at'] = now();
                    $payload['deactivated_by'] = $actor->id;
                } elseif ($status !== AccountStatus::Deactivated) {
                    $payload['status'] = $status;
                    $payload['deactivated_at'] = null;
                    $payload['deactivated_by'] = null;
                    $payload['deactivation_reason'] = null;
                }
            }

            if ($payload !== []) {
                $target->forceFill($payload)->save();
            }

            if (! empty($data['password'])) {
                $target->forceFill([
                    'password' => (string) $data['password'],
                ])->save();
                $target->tokens()->delete();
            }

            $this->audit->log($actor, 'user.updated', User::class, $target->id, [
                'fields' => array_values(array_filter([
                    array_key_exists('name', $payload) ? 'name' : null,
                    array_key_exists('email', $payload) ? 'email' : null,
                    array_key_exists('status', $payload) ? 'status' : null,
                    ! empty($data['password']) ? 'password' : null,
                ])),
            ]);

            return $target->refresh();
        });
    }

    private function assertCanEdit(User $actor, User $target): void
    {
        if (! $actor->platform_role instanceof PlatformRole) {
            throw new DomainException(__('admin.unauthorized'));
        }

        if ($target->platform_role === PlatformRole::SuperAdmin && $actor->platform_role !== PlatformRole::SuperAdmin) {
            throw new DomainException(__('admin.cannot_moderate_super_admin'));
        }
    }
}
