<?php

namespace App\Services;

use App\Enums\CentreStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Centre;
use App\Models\User;
use App\Support\SensitiveData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Throwable;

class CentreManagementService
{
    public function __construct(
        protected CodeGeneratorService $codes,
        protected TemporaryPasswordService $passwords,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{centre: Centre, user: User, temporary_password: string}
     */
    public function create(array $data, User $actor): array
    {
        $mobile = SensitiveData::normalizeIndianMobile((string) $data['manager_mobile']);

        $this->assertUniqueManagerMobile($mobile);

        try {
            return DB::transaction(function () use ($data, $actor, $mobile) {
                $code = $this->codes->nextCentreCode();
                $temporaryPassword = $this->passwords->generate();

                $centre = Centre::query()->create([
                    ...$data,
                    'centre_code' => $code,
                    'manager_mobile' => $mobile,
                    'state' => $data['state'] ?? 'Maharashtra',
                    'status' => $data['status'] ?? CentreStatus::Active,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $user = User::query()->create([
                    'name' => $centre->manager_name,
                    'login_id' => $centre->centre_code,
                    'email' => $centre->manager_email,
                    'mobile' => $centre->manager_mobile,
                    'password' => $temporaryPassword,
                    'status' => $centre->status === CentreStatus::Active
                        ? UserStatus::Active
                        : UserStatus::Inactive,
                    'must_change_password' => true,
                    'centre_id' => $centre->id,
                    'employee_id' => null,
                ]);

                $role = Role::findOrCreate(RoleName::CentreManager->value, 'web');
                $user->syncRoles([$role]);

                activity()
                    ->causedBy($actor)
                    ->performedOn($centre)
                    ->withProperties([
                        'centre_code' => $centre->centre_code,
                        'manager_login_id' => $user->login_id,
                    ])
                    ->log('centre_created');

                return [
                    'centre' => $centre->fresh(),
                    'user' => $user->fresh(),
                    'temporary_password' => $temporaryPassword,
                ];
            });
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'centre_name' => 'Centre creation failed and was rolled back. '.$e->getMessage(),
            ]);
        }
    }

    public function syncManagerUser(Centre $centre): void
    {
        $user = User::query()
            ->where('centre_id', $centre->id)
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::CentreManager->value))
            ->first();

        if ($user === null) {
            return;
        }

        $user->fill([
            'name' => $centre->manager_name,
            'email' => $centre->manager_email,
            'mobile' => $centre->manager_mobile,
            'status' => $centre->status === CentreStatus::Active
                ? UserStatus::Active
                : UserStatus::Inactive,
        ])->save();
    }

    /**
     * @return array{user: User, temporary_password: string}
     */
    public function resetManagerLogin(Centre $centre, User $actor): array
    {
        $user = User::query()
            ->where('centre_id', $centre->id)
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::CentreManager->value))
            ->firstOrFail();

        $temporaryPassword = $this->passwords->generate();

        $user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
            'status' => UserStatus::Active,
        ])->save();

        activity()
            ->causedBy($actor)
            ->performedOn($centre)
            ->withProperties([
                'centre_code' => $centre->centre_code,
                'manager_login_id' => $user->login_id,
            ])
            ->log('centre_manager_login_reset');

        return [
            'user' => $user->fresh(),
            'temporary_password' => $temporaryPassword,
        ];
    }

    public function setStatus(Centre $centre, CentreStatus $status, User $actor): void
    {
        $centre->update([
            'status' => $status,
            'updated_by' => $actor->id,
        ]);

        $this->syncManagerUser($centre);

        activity()
            ->causedBy($actor)
            ->performedOn($centre)
            ->withProperties(['status' => $status->value])
            ->log($status === CentreStatus::Active ? 'centre_activated' : 'centre_deactivated');
    }

    protected function assertUniqueManagerMobile(string $mobile, ?int $ignoreCentreId = null): void
    {
        $existsOnCentre = Centre::query()
            ->when($ignoreCentreId, fn ($q) => $q->whereKeyNot($ignoreCentreId))
            ->where('manager_mobile', $mobile)
            ->where('status', CentreStatus::Active)
            ->exists();

        $existsOnUser = User::query()
            ->where('mobile', $mobile)
            ->where('status', UserStatus::Active)
            ->exists();

        if ($existsOnCentre || $existsOnUser) {
            throw ValidationException::withMessages([
                'manager_mobile' => 'This mobile number is already used by an active centre manager or user.',
            ]);
        }
    }
}
