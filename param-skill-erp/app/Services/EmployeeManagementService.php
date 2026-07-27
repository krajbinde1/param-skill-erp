<?php

namespace App\Services;

use App\Enums\CentreStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Enums\UserStatus;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\Scopes\CentreScope;
use App\Models\User;
use App\Support\SensitiveData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Throwable;

class EmployeeManagementService
{
    public function __construct(
        protected CodeGeneratorService $codes,
        protected TemporaryPasswordService $passwords,
        protected CentreContext $centreContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{employee: Employee, user: User, temporary_password: string}
     */
    public function create(array $data, User $actor): array
    {
        $centreId = (int) $data['centre_id'];
        $this->centreContext->assertCentreAccess($centreId, $actor);

        $centre = Centre::query()->findOrFail($centreId);

        if ($centre->status !== CentreStatus::Active) {
            throw ValidationException::withMessages([
                'centre_id' => 'Employees can only be created for active centres.',
            ]);
        }

        $mobile = SensitiveData::normalizeIndianMobile((string) $data['mobile']);
        $this->assertUniqueMobile($mobile);

        $role = EmployeeRole::from($data['employee_role']);
        $aadhaar = $data['aadhaar_number'] ?? null;

        if (filled($aadhaar)) {
            $this->assertUniqueAadhaar((string) $aadhaar);
        }

        unset($data['aadhaar_number']);

        try {
            return DB::transaction(function () use ($data, $actor, $centre, $mobile, $role, $aadhaar) {
                $code = $this->codes->nextEmployeeCode();
                $temporaryPassword = $this->passwords->generate();

                $employee = new Employee([
                    ...$data,
                    'centre_id' => $centre->id,
                    'employee_code' => $code,
                    'mobile' => $mobile,
                    'employee_role' => $role,
                    'state' => $data['state'] ?? 'Maharashtra',
                    'status' => $data['status'] ?? EmployeeStatus::Active,
                    'pan_number' => filled($data['pan_number'] ?? null)
                        ? strtoupper((string) $data['pan_number'])
                        : null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $employee->setAadhaar(filled($aadhaar) ? (string) $aadhaar : null);
                $employee->save();

                $user = User::query()->create([
                    'name' => $employee->full_name,
                    'login_id' => $employee->employee_code,
                    'email' => $employee->email,
                    'mobile' => $employee->mobile,
                    'password' => $temporaryPassword,
                    'status' => $employee->status === EmployeeStatus::Active
                        ? UserStatus::Active
                        : UserStatus::Inactive,
                    'must_change_password' => true,
                    'centre_id' => $centre->id,
                    'employee_id' => $employee->id,
                ]);

                $spatieRole = Role::findOrCreate($role->toSpatieRole(), 'web');
                $user->syncRoles([$spatieRole]);

                $employee->update(['user_id' => $user->id]);

                activity()
                    ->causedBy($actor)
                    ->performedOn($employee)
                    ->withProperties([
                        'employee_code' => $employee->employee_code,
                        'centre_id' => $centre->id,
                        'role' => $role->value,
                        'login_id' => $user->login_id,
                    ])
                    ->log('employee_created');

                return [
                    'employee' => $employee->fresh(),
                    'user' => $user->fresh(),
                    'temporary_password' => $temporaryPassword,
                ];
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'first_name' => 'Employee creation failed and was rolled back. '.$e->getMessage(),
            ]);
        }
    }

    public function syncLinkedUser(Employee $employee, ?EmployeeRole $previousRole = null): void
    {
        $user = $employee->user;

        if ($user === null) {
            return;
        }

        $user->fill([
            'name' => $employee->full_name,
            'email' => $employee->email,
            'mobile' => $employee->mobile,
            'centre_id' => $employee->centre_id,
            'status' => $employee->status === EmployeeStatus::Active
                ? UserStatus::Active
                : UserStatus::Inactive,
        ])->save();

        if ($previousRole !== null && $previousRole !== $employee->employee_role) {
            $operationalRoles = collect(EmployeeRole::cases())->map->value->all();
            $keep = $user->roles
                ->pluck('name')
                ->reject(fn (string $name) => in_array($name, $operationalRoles, true))
                ->values()
                ->all();

            $keep[] = $employee->employee_role->toSpatieRole();
            $user->syncRoles(array_unique($keep));

            activity()
                ->performedOn($employee)
                ->withProperties([
                    'from' => $previousRole->value,
                    'to' => $employee->employee_role->value,
                ])
                ->log('employee_role_changed');
        }
    }

    /**
     * @return array{user: User, temporary_password: string}
     */
    public function resetLogin(Employee $employee, User $actor): array
    {
        $this->centreContext->assertCentreAccess($employee->centre_id, $actor);

        $user = $employee->user;

        if ($user === null) {
            throw ValidationException::withMessages([
                'employee' => 'This employee does not have a linked login account.',
            ]);
        }

        $temporaryPassword = $this->passwords->generate();

        $user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
            'status' => UserStatus::Active,
        ])->save();

        activity()
            ->causedBy($actor)
            ->performedOn($employee)
            ->withProperties([
                'employee_code' => $employee->employee_code,
                'login_id' => $user->login_id,
            ])
            ->log('employee_login_reset');

        return [
            'user' => $user->fresh(),
            'temporary_password' => $temporaryPassword,
        ];
    }

    public function setStatus(Employee $employee, EmployeeStatus $status, User $actor): void
    {
        $this->centreContext->assertCentreAccess($employee->centre_id, $actor);

        $employee->update([
            'status' => $status,
            'updated_by' => $actor->id,
        ]);

        $this->syncLinkedUser($employee);

        activity()
            ->causedBy($actor)
            ->performedOn($employee)
            ->withProperties(['status' => $status->value])
            ->log($status === EmployeeStatus::Active ? 'employee_activated' : 'employee_deactivated');
    }

    public function assertUniqueMobile(string $mobile, ?int $ignoreEmployeeId = null): void
    {
        $mobile = SensitiveData::normalizeIndianMobile($mobile);

        $exists = Employee::withoutGlobalScope(CentreScope::class)
            ->when($ignoreEmployeeId, fn ($q) => $q->whereKeyNot($ignoreEmployeeId))
            ->where('mobile', $mobile)
            ->where('status', EmployeeStatus::Active)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'mobile' => 'An active employee with this mobile number already exists.',
            ]);
        }
    }

    public function assertUniqueAadhaar(string $aadhaar, ?int $ignoreEmployeeId = null): void
    {
        if (! SensitiveData::isValidAadhaar($aadhaar)) {
            throw ValidationException::withMessages([
                'aadhaar_number' => 'Aadhaar number must be exactly 12 digits.',
            ]);
        }

        $hash = SensitiveData::aadhaarHash($aadhaar);

        $exists = Employee::withoutGlobalScope(CentreScope::class)
            ->when($ignoreEmployeeId, fn ($q) => $q->whereKeyNot($ignoreEmployeeId))
            ->where('aadhaar_hash', $hash)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'aadhaar_number' => 'This Aadhaar number is already registered.',
            ]);
        }
    }
}
