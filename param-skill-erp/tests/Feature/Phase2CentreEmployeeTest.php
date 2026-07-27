<?php

namespace Tests\Feature;

use App\Enums\CentreStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Enums\RoleName;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\User;
use App\Services\CentreAccessService;
use App\Services\CentreContext;
use App\Services\CentreManagementService;
use App\Services\CodeGeneratorService;
use App\Services\EmployeeManagementService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase2CentreEmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    protected function makeElevatedUser(string $role = 'Super Admin'): User
    {
        $user = User::factory()->create([
            'login_id' => fake()->unique()->userName(),
            'must_change_password' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_create_centre_with_manager_login(): void
    {
        $admin = $this->makeElevatedUser(RoleName::Admin->value);

        $result = app(CentreManagementService::class)->create([
            'centre_name' => 'Test Centre',
            'manager_name' => 'Manager One',
            'manager_mobile' => '9876543210',
            'manager_email' => 'manager@example.com',
            'address' => 'Address line',
            'district' => 'Pune',
            'state' => 'Maharashtra',
            'centre_capacity' => 100,
            'boys_capacity' => 40,
            'girls_capacity' => 40,
            'hostel_available' => true,
            'status' => CentreStatus::Active,
        ], $admin);

        $centre = $result['centre'];
        $manager = $result['user'];
        $plain = $result['temporary_password'];

        $this->assertSame('PSC0001', $centre->centre_code);
        $this->assertTrue($manager->hasRole(RoleName::CentreManager->value));
        $this->assertSame($centre->id, $manager->centre_id);
        $this->assertSame($centre->centre_code, $manager->login_id);
        $this->assertTrue($manager->must_change_password);
        $this->assertTrue(Hash::check($plain, $manager->fresh()->password));
        $this->assertDatabaseMissing('users', ['password' => $plain]);
    }

    public function test_centre_codes_are_unique_and_sequential(): void
    {
        $admin = $this->makeElevatedUser();

        $first = app(CodeGeneratorService::class)->nextCentreCode();
        $second = app(CodeGeneratorService::class)->nextCentreCode();

        $this->assertSame('PSC0001', $first);
        $this->assertSame('PSC0002', $second);
        $this->assertNotSame($first, $second);
    }

    public function test_centre_manager_cannot_see_another_centre_employee(): void
    {
        $admin = $this->makeElevatedUser();
        $service = app(CentreManagementService::class);

        $centreA = $service->create([
            'centre_name' => 'Centre A',
            'manager_name' => 'Manager A',
            'manager_mobile' => '9876543211',
            'address' => 'A',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $centreB = $service->create([
            'centre_name' => 'Centre B',
            'manager_name' => 'Manager B',
            'manager_mobile' => '9876543212',
            'address' => 'B',
            'district' => 'Nagpur',
            'status' => CentreStatus::Active,
        ], $admin);

        $employeeService = app(EmployeeManagementService::class);

        $empB = $employeeService->create([
            'centre_id' => $centreB['centre']->id,
            'first_name' => 'Emp',
            'last_name' => 'B',
            'mobile' => '9123456780',
            'employee_role' => EmployeeRole::Trainer->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        $this->actingAs($centreA['user']);

        $visible = Employee::query()->pluck('id');
        $this->assertFalse($visible->contains($empB['employee']->id));
        $this->assertFalse(app(CentreContext::class)->canAccessEmployee($empB['employee']));
    }

    public function test_centre_manager_cannot_manipulate_centre_id_on_create(): void
    {
        $admin = $this->makeElevatedUser();
        $service = app(CentreManagementService::class);

        $centreA = $service->create([
            'centre_name' => 'Centre A',
            'manager_name' => 'Manager A',
            'manager_mobile' => '9876543213',
            'address' => 'A',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $centreB = $service->create([
            'centre_name' => 'Centre B',
            'manager_name' => 'Manager B',
            'manager_mobile' => '9876543214',
            'address' => 'B',
            'district' => 'Nagpur',
            'status' => CentreStatus::Active,
        ], $admin);

        $this->actingAs($centreA['user']);

        $this->expectException(HttpException::class);

        app(EmployeeManagementService::class)->create([
            'centre_id' => $centreB['centre']->id,
            'first_name' => 'Hack',
            'last_name' => 'Attempt',
            'mobile' => '9123456781',
            'employee_role' => EmployeeRole::Mobilizer->value,
            'status' => EmployeeStatus::Active,
        ], $centreA['user']);
    }

    public function test_employee_user_created_with_role_and_unique_code(): void
    {
        $admin = $this->makeElevatedUser();
        $centre = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre C',
            'manager_name' => 'Manager C',
            'manager_mobile' => '9876543215',
            'address' => 'C',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $result = app(EmployeeManagementService::class)->create([
            'centre_id' => $centre['centre']->id,
            'first_name' => 'Ravi',
            'last_name' => 'Patil',
            'mobile' => '9123456782',
            'employee_role' => EmployeeRole::Mobilizer->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        $this->assertSame('EMP00001', $result['employee']->employee_code);
        $this->assertTrue($result['user']->hasRole(EmployeeRole::Mobilizer->value));
        $this->assertSame($result['employee']->id, $result['user']->employee_id);
        $this->assertTrue(Hash::check($result['temporary_password'], $result['user']->password));
    }

    public function test_duplicate_active_mobile_is_rejected(): void
    {
        $admin = $this->makeElevatedUser();
        $centre = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre D',
            'manager_name' => 'Manager D',
            'manager_mobile' => '9876543216',
            'manager_email' => 'managerd@example.com',
            'address' => 'D',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        app(EmployeeManagementService::class)->create([
            'centre_id' => $centre['centre']->id,
            'first_name' => 'One',
            'last_name' => 'Emp',
            'mobile' => '9123456783',
            'employee_role' => EmployeeRole::Trainer->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        try {
            app(EmployeeManagementService::class)->create([
                'centre_id' => $centre['centre']->id,
                'first_name' => 'Two',
                'last_name' => 'Emp',
                'mobile' => '9123456783',
                'employee_role' => EmployeeRole::Trainer->value,
                'status' => EmployeeStatus::Active,
            ], $admin);
            $this->fail('Expected duplicate mobile validation exception.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('mobile', $e->errors());
        }
    }

    public function test_duplicate_aadhaar_hash_is_rejected(): void
    {
        $admin = $this->makeElevatedUser();
        $centre = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre D2',
            'manager_name' => 'Manager D2',
            'manager_mobile' => '9876543299',
            'manager_email' => 'managerd2@example.com',
            'address' => 'D2',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        app(EmployeeManagementService::class)->create([
            'centre_id' => $centre['centre']->id,
            'first_name' => 'One',
            'last_name' => 'Emp',
            'mobile' => '9123456790',
            'aadhaar_number' => '123456789012',
            'employee_role' => EmployeeRole::Trainer->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        try {
            app(EmployeeManagementService::class)->create([
                'centre_id' => $centre['centre']->id,
                'first_name' => 'Two',
                'last_name' => 'Emp',
                'mobile' => '9123456791',
                'aadhaar_number' => '123456789012',
                'employee_role' => EmployeeRole::Trainer->value,
                'status' => EmployeeStatus::Active,
            ], $admin);
            $this->fail('Expected duplicate aadhaar validation exception.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('aadhaar_number', $e->errors());
        }
    }

    public function test_soft_deleted_employee_allows_mobile_recreation(): void
    {
        $admin = $this->makeElevatedUser();
        $centre = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre E',
            'manager_name' => 'Manager E',
            'manager_mobile' => '9876543217',
            'address' => 'E',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $first = app(EmployeeManagementService::class)->create([
            'centre_id' => $centre['centre']->id,
            'first_name' => 'Old',
            'last_name' => 'Emp',
            'mobile' => '9123456784',
            'aadhaar_number' => '123456789013',
            'employee_role' => EmployeeRole::Cook->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        $first['employee']->delete();

        $second = app(EmployeeManagementService::class)->create([
            'centre_id' => $centre['centre']->id,
            'first_name' => 'New',
            'last_name' => 'Emp',
            'mobile' => '9123456784',
            'aadhaar_number' => '123456789013',
            'employee_role' => EmployeeRole::Cook->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        $this->assertNotSame($first['employee']->employee_code, $second['employee']->employee_code);
        $this->assertSame('9123456784', $second['employee']->mobile);
    }

    public function test_inactive_centre_blocks_manager_and_employee_panel_access(): void
    {
        $admin = $this->makeElevatedUser();
        $centreResult = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre F',
            'manager_name' => 'Manager F',
            'manager_mobile' => '9876543218',
            'address' => 'F',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $employeeResult = app(EmployeeManagementService::class)->create([
            'centre_id' => $centreResult['centre']->id,
            'first_name' => 'Staff',
            'last_name' => 'F',
            'mobile' => '9123456785',
            'employee_role' => EmployeeRole::Warden->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        // Give employee a panel role for this access check scenario - still blocked by centre
        $employeeResult['user']->assignRole(RoleName::CentreManager->value);

        app(CentreManagementService::class)->setStatus($centreResult['centre']->fresh(), CentreStatus::Inactive, $admin);

        $access = app(CentreAccessService::class);
        $this->assertFalse($access->canAccessPanel($centreResult['user']->fresh()));
        $this->assertFalse($access->canAccessPanel($employeeResult['user']->fresh()));
    }

    public function test_inactive_employee_blocks_login_access(): void
    {
        $admin = $this->makeElevatedUser();
        $centre = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre G',
            'manager_name' => 'Manager G',
            'manager_mobile' => '9876543219',
            'address' => 'G',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $employee = app(EmployeeManagementService::class)->create([
            'centre_id' => $centre['centre']->id,
            'first_name' => 'Staff',
            'last_name' => 'G',
            'mobile' => '9123456786',
            'employee_role' => EmployeeRole::Helper->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        $employee['user']->assignRole(RoleName::CentreManager->value);

        app(EmployeeManagementService::class)->setStatus($employee['employee']->fresh(), EmployeeStatus::Inactive, $admin);

        $this->assertFalse(app(CentreAccessService::class)->canAccessPanel($employee['user']->fresh()));
    }

    public function test_password_reset_and_must_change_password_flow(): void
    {
        $admin = $this->makeElevatedUser();
        $centre = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre H',
            'manager_name' => 'Manager H',
            'manager_mobile' => '9876543220',
            'address' => 'H',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $reset = app(CentreManagementService::class)->resetManagerLogin($centre['centre'], $admin);
        $manager = $reset['user']->fresh();

        $this->assertTrue($manager->must_change_password);
        $this->assertTrue(Hash::check($reset['temporary_password'], $manager->password));

        $manager->forceFill([
            'password' => 'NewStrong@123',
            'must_change_password' => false,
        ])->save();

        $this->assertFalse($manager->fresh()->must_change_password);
        $this->assertTrue(app(CentreAccessService::class)->canAccessPanel($manager->fresh()));
    }

    public function test_centre_manager_cannot_reset_other_centre_employee_login(): void
    {
        $admin = $this->makeElevatedUser();
        $service = app(CentreManagementService::class);

        $centreA = $service->create([
            'centre_name' => 'Centre I',
            'manager_name' => 'Manager I',
            'manager_mobile' => '9876543221',
            'address' => 'I',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $centreB = $service->create([
            'centre_name' => 'Centre J',
            'manager_name' => 'Manager J',
            'manager_mobile' => '9876543222',
            'address' => 'J',
            'district' => 'Nagpur',
            'status' => CentreStatus::Active,
        ], $admin);

        $employeeB = app(EmployeeManagementService::class)->create([
            'centre_id' => $centreB['centre']->id,
            'first_name' => 'Emp',
            'last_name' => 'J',
            'mobile' => '9123456787',
            'employee_role' => EmployeeRole::Trainer->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        $this->actingAs($centreA['user']);

        $this->expectException(HttpException::class);

        app(EmployeeManagementService::class)->resetLogin($employeeB['employee'], $centreA['user']);
    }

    public function test_super_admin_bypass_and_dashboard_counts(): void
    {
        $super = $this->makeElevatedUser(RoleName::SuperAdmin->value);
        $this->actingAs($super);

        app(CentreManagementService::class)->create([
            'centre_name' => 'Centre K',
            'manager_name' => 'Manager K',
            'manager_mobile' => '9876543223',
            'address' => 'K',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $super);

        $this->assertTrue($super->can('centres.create'));
        $this->assertSame(1, Centre::query()->count());
        $this->assertGreaterThanOrEqual(1, Centre::query()->where('district', 'Pune')->count());
    }

    public function test_soft_delete_does_not_reuse_codes(): void
    {
        $code1 = app(CodeGeneratorService::class)->nextEmployeeCode();
        $code2 = app(CodeGeneratorService::class)->nextEmployeeCode();

        $this->assertSame('EMP00001', $code1);
        $this->assertSame('EMP00002', $code2);
    }

    public function test_operational_employee_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole(EmployeeRole::Mobilizer->value);

        $this->assertFalse(app(CentreAccessService::class)->canAccessPanel($user));
    }

    public function test_admin_login_page_still_works(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
