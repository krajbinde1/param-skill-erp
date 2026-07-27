<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CentreStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('login_id', $credentials['login_id'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login_id' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->hasRole(RoleName::Mobilizer->value)) {
            return $this->error('Only Mobilizer accounts may use the mobile API.', 403);
        }

        if ($user->status !== UserStatus::Active) {
            return $this->error('Your account is inactive or blocked.', 403);
        }

        $employee = $user->employee_id ? Employee::withoutGlobalScopes()->find($user->employee_id) : null;
        if ($employee === null || $employee->status !== EmployeeStatus::Active) {
            return $this->error('Your employee profile is inactive.', 403);
        }

        if ($user->centre_id) {
            $centre = Centre::query()->find($user->centre_id);
            if ($centre === null || $centre->status !== CentreStatus::Active) {
                return $this->error('Your centre is inactive. Please contact the administrator.', 403);
            }
        }

        $token = $user->createToken($credentials['device_name'])->plainTextToken;
        $user->load(['centre', 'employee', 'roles']);

        return $this->success('Login successful.', [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login_id' => $user->login_id,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'must_change_password' => $user->must_change_password,
                'roles' => $user->getRoleNames(),
            ],
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'role' => $employee->employee_role?->value,
            ],
            'centre' => $user->centre ? [
                'id' => $user->centre->id,
                'centre_code' => $user->centre->centre_code,
                'centre_name' => $user->centre->centre_name,
            ] : null,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'must_change_password' => $user->must_change_password,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success('Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['centre', 'employee', 'roles']);

        return $this->success('Profile loaded.', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login_id' => $user->login_id,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'must_change_password' => $user->must_change_password,
                'roles' => $user->getRoleNames(),
            ],
            'employee' => $user->employee ? [
                'id' => $user->employee->id,
                'employee_code' => $user->employee->employee_code,
                'full_name' => $user->employee->full_name,
            ] : null,
            'centre' => $user->centre ? [
                'id' => $user->centre->id,
                'centre_code' => $user->centre->centre_code,
                'centre_name' => $user->centre->centre_name,
            ] : null,
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        activity()->causedBy($user)->performedOn($user)->log('password_changed');

        return $this->success('Password updated successfully.');
    }

    protected function success(string $message, mixed $data = [], array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
        ]);
    }

    protected function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
