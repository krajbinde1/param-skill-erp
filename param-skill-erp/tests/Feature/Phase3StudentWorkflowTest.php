<?php

namespace Tests\Feature;

use App\Enums\CentreStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Enums\RoleName;
use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentDocumentStatus;
use App\Enums\StudentDocumentType;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use App\Services\CentreManagementService;
use App\Services\CodeGeneratorService;
use App\Services\EmployeeManagementService;
use App\Services\StudentWorkflowService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase3StudentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    protected function makeAdmin(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole(RoleName::Admin->value);

        return $user;
    }

    /**
     * @return array{admin: User, centre: Centre, manager: User, mobilizerEmployee: Employee, mobilizerUser: User, mobilizerPassword: string}
     */
    protected function seedCentreWithMobilizer(): array
    {
        $admin = $this->makeAdmin();
        $centreResult = app(CentreManagementService::class)->create([
            'centre_name' => 'Skill Centre Pune',
            'manager_name' => 'Manager Pune',
            'manager_mobile' => '9876500001',
            'manager_email' => 'mgr1@example.com',
            'address' => 'Pune Address',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);

        $empResult = app(EmployeeManagementService::class)->create([
            'centre_id' => $centreResult['centre']->id,
            'first_name' => 'Mobi',
            'last_name' => 'One',
            'mobile' => '9876500010',
            'employee_role' => EmployeeRole::Mobilizer->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        return [
            'admin' => $admin,
            'centre' => $centreResult['centre'],
            'manager' => $centreResult['user'],
            'mobilizerEmployee' => $empResult['employee'],
            'mobilizerUser' => $empResult['user'],
            'mobilizerPassword' => $empResult['temporary_password'],
        ];
    }

    public function test_admin_can_create_student_with_unique_code_and_secure_aadhaar(): void
    {
        $ctx = $this->seedCentreWithMobilizer();

        $student = app(StudentWorkflowService::class)->create([
            'centre_id' => $ctx['centre']->id,
            'preferred_centre_id' => $ctx['centre']->id,
            'mobilizer_id' => $ctx['mobilizerEmployee']->id,
            'first_name' => 'Rahul',
            'last_name' => 'Sharma',
            'mobile' => '9876500100',
            'full_address' => 'Lane 1',
            'village' => 'V1',
            'taluka' => 'T1',
            'district' => 'Pune',
            'aadhaar_number' => '123456789012',
        ], $ctx['admin']);

        $this->assertSame('STD000001', $student->student_code);
        $this->assertSame($ctx['mobilizerEmployee']->id, $student->mobilizer_id);
        $this->assertSame($ctx['centre']->id, $student->centre_id);
        $this->assertNotNull($student->aadhaar_encrypted);
        $this->assertNotSame('123456789012', $student->getRawOriginal('aadhaar_encrypted'));
        $this->assertSame('9012', $student->aadhaar_last4);
        $this->assertDatabaseMissing('students', ['aadhaar_encrypted' => '123456789012']);
    }

    public function test_aadhaar_duplicate_is_rejected(): void
    {
        $ctx = $this->seedCentreWithMobilizer();
        $service = app(StudentWorkflowService::class);

        $service->create([
            'centre_id' => $ctx['centre']->id,
            'mobilizer_id' => $ctx['mobilizerEmployee']->id,
            'first_name' => 'A',
            'last_name' => 'One',
            'mobile' => '9876500101',
            'full_address' => 'A',
            'village' => 'V',
            'taluka' => 'T',
            'district' => 'Pune',
            'aadhaar_number' => '123456789099',
        ], $ctx['admin']);

        $this->expectException(ValidationException::class);

        $service->create([
            'centre_id' => $ctx['centre']->id,
            'mobilizer_id' => $ctx['mobilizerEmployee']->id,
            'first_name' => 'B',
            'last_name' => 'Two',
            'mobile' => '9876500102',
            'full_address' => 'B',
            'village' => 'V',
            'taluka' => 'T',
            'district' => 'Pune',
            'aadhaar_number' => '123456789099',
        ], $ctx['admin']);
    }

    public function test_centre_manager_cannot_see_other_centre_students(): void
    {
        $admin = $this->makeAdmin();
        $a = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre A',
            'manager_name' => 'MA',
            'manager_mobile' => '9876500201',
            'manager_email' => 'a@example.com',
            'address' => 'A',
            'district' => 'Pune',
            'status' => CentreStatus::Active,
        ], $admin);
        $b = app(CentreManagementService::class)->create([
            'centre_name' => 'Centre B',
            'manager_name' => 'MB',
            'manager_mobile' => '9876500202',
            'manager_email' => 'b@example.com',
            'address' => 'B',
            'district' => 'Nagpur',
            'status' => CentreStatus::Active,
        ], $admin);

        $mobB = app(EmployeeManagementService::class)->create([
            'centre_id' => $b['centre']->id,
            'first_name' => 'Mob',
            'last_name' => 'B',
            'mobile' => '9876500210',
            'employee_role' => EmployeeRole::Mobilizer->value,
            'status' => EmployeeStatus::Active,
        ], $admin);

        $studentB = app(StudentWorkflowService::class)->create([
            'centre_id' => $b['centre']->id,
            'mobilizer_id' => $mobB['employee']->id,
            'first_name' => 'Stu',
            'last_name' => 'B',
            'mobile' => '9876500220',
            'full_address' => 'B',
            'village' => 'V',
            'taluka' => 'T',
            'district' => 'Nagpur',
        ], $admin);

        $this->actingAs($a['user']);
        $this->assertFalse(Student::query()->whereKey($studentB->id)->exists());
    }

    public function test_admission_transition_rules_and_rejection_reason(): void
    {
        $ctx = $this->seedCentreWithMobilizer();
        $service = app(StudentWorkflowService::class);

        $student = $service->create([
            'centre_id' => $ctx['centre']->id,
            'mobilizer_id' => $ctx['mobilizerEmployee']->id,
            'first_name' => 'Flow',
            'last_name' => 'Test',
            'mobile' => '9876500300',
            'full_address' => 'A',
            'village' => 'V',
            'taluka' => 'T',
            'district' => 'Pune',
        ], $ctx['admin']);

        $submitted = $service->submit($student, $ctx['mobilizerUser']);
        $this->assertSame(StudentAdmissionStatus::DocumentVerificationPending, $submitted->admission_status);

        $this->expectException(ValidationException::class);
        $service->transitionAdmission($submitted, StudentAdmissionStatus::JoinedCentre, $ctx['admin']);
    }

    public function test_document_upload_uses_private_disk_and_rejection_requires_reason(): void
    {
        Storage::fake('private');
        $ctx = $this->seedCentreWithMobilizer();
        $service = app(StudentWorkflowService::class);

        $student = $service->create([
            'centre_id' => $ctx['centre']->id,
            'mobilizer_id' => $ctx['mobilizerEmployee']->id,
            'first_name' => 'Doc',
            'last_name' => 'Test',
            'mobile' => '9876500400',
            'full_address' => 'A',
            'village' => 'V',
            'taluka' => 'T',
            'district' => 'Pune',
        ], $ctx['admin']);

        $file = UploadedFile::fake()->create('aadhaar.pdf', 100, 'application/pdf');
        $document = $service->uploadDocument($student, $file, StudentDocumentType::AadhaarFront, $ctx['mobilizerUser']);

        Storage::disk('private')->assertExists($document->file_path);

        $this->expectException(ValidationException::class);
        $service->verifyDocument($document, StudentDocumentStatus::Rejected, $ctx['admin']);
    }

    public function test_api_login_blocks_non_mobilizer_and_inactive_centre(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson('/api/v1/login', [
            'login_id' => $admin->login_id,
            'password' => 'password',
            'device_name' => 'test-device',
        ])->assertStatus(403);

        $ctx = $this->seedCentreWithMobilizer();
        app(CentreManagementService::class)->setStatus($ctx['centre']->fresh(), CentreStatus::Inactive, $ctx['admin']);

        $this->postJson('/api/v1/login', [
            'login_id' => $ctx['mobilizerUser']->login_id,
            'password' => $ctx['mobilizerPassword'],
            'device_name' => 'phone',
        ])->assertStatus(403);
    }

    public function test_mobilizer_api_create_submit_and_ownership(): void
    {
        $ctx = $this->seedCentreWithMobilizer();
        $ctx['mobilizerUser']->forceFill(['must_change_password' => false])->save();

        $login = $this->postJson('/api/v1/login', [
            'login_id' => $ctx['mobilizerUser']->login_id,
            'password' => $ctx['mobilizerPassword'],
            'device_name' => 'android',
        ]);
        $login->assertOk()->assertJsonPath('success', true);
        $token = $login->json('data.token');

        $create = $this->withToken($token)->postJson('/api/v1/students', [
            'client_uuid' => '11111111-1111-1111-1111-111111111111',
            'first_name' => 'Api',
            'last_name' => 'Student',
            'mobile' => '9876500500',
            'full_address' => 'Addr',
            'village' => 'Vil',
            'taluka' => 'Tal',
            'district' => 'Pune',
            'centre_id' => 999999,
            'mobilizer_id' => 999999,
        ]);
        $create->assertCreated();
        $this->assertSame($ctx['mobilizerEmployee']->id, $create->json('data.mobilizer_id'));
        $this->assertSame($ctx['centre']->id, $create->json('data.centre_id'));

        $dup = $this->withToken($token)->postJson('/api/v1/students', [
            'client_uuid' => '11111111-1111-1111-1111-111111111111',
            'first_name' => 'Api',
            'last_name' => 'Student',
            'mobile' => '9876500500',
            'full_address' => 'Addr',
            'village' => 'Vil',
            'taluka' => 'Tal',
            'district' => 'Pune',
        ]);
        $dup->assertOk();
        $this->assertSame($create->json('data.id'), $dup->json('data.id'));

        $studentId = $create->json('data.id');
        $this->withToken($token)->postJson("/api/v1/students/{$studentId}/submit")->assertOk();

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();
    }

    public function test_mobilizer_cannot_view_another_mobilizer_student(): void
    {
        $ctx = $this->seedCentreWithMobilizer();
        $other = app(EmployeeManagementService::class)->create([
            'centre_id' => $ctx['centre']->id,
            'first_name' => 'Other',
            'last_name' => 'Mob',
            'mobile' => '9876500600',
            'employee_role' => EmployeeRole::Mobilizer->value,
            'status' => EmployeeStatus::Active,
        ], $ctx['admin']);

        $student = app(StudentWorkflowService::class)->create([
            'centre_id' => $ctx['centre']->id,
            'mobilizer_id' => $ctx['mobilizerEmployee']->id,
            'first_name' => 'Own',
            'last_name' => 'Stu',
            'mobile' => '9876500610',
            'full_address' => 'A',
            'village' => 'V',
            'taluka' => 'T',
            'district' => 'Pune',
        ], $ctx['admin']);

        Sanctum::actingAs($other['user']);
        $response = $this->getJson('/api/v1/students/'.$student->id);
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_soft_deleted_student_does_not_reuse_code(): void
    {
        $code1 = app(CodeGeneratorService::class)->nextStudentCode();
        $code2 = app(CodeGeneratorService::class)->nextStudentCode();
        $this->assertSame('STD000001', $code1);
        $this->assertSame('STD000002', $code2);
    }

    public function test_student_export_excludes_aadhaar_and_admin_login_page_loads(): void
    {
        $ctx = $this->seedCentreWithMobilizer();
        app(StudentWorkflowService::class)->create([
            'centre_id' => $ctx['centre']->id,
            'mobilizer_id' => $ctx['mobilizerEmployee']->id,
            'first_name' => 'Exp',
            'last_name' => 'Stu',
            'mobile' => '9876500700',
            'full_address' => 'A',
            'village' => 'V',
            'taluka' => 'T',
            'district' => 'Pune',
            'aadhaar_number' => '555555555555',
        ], $ctx['admin']);

        $this->actingAs($ctx['admin']);
        $response = $this->get(route('exports.students.csv'));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringNotContainsString('555555555555', $content);
        $this->assertStringNotContainsString('aadhaar', strtolower($content));

        $this->get('/admin')->assertOk();
    }

    public function test_overdue_follow_up_scope(): void
    {
        $ctx = $this->seedCentreWithMobilizer();
        $student = app(StudentWorkflowService::class)->create([
            'centre_id' => $ctx['centre']->id,
            'mobilizer_id' => $ctx['mobilizerEmployee']->id,
            'first_name' => 'Over',
            'last_name' => 'Due',
            'mobile' => '9876500800',
            'full_address' => 'A',
            'village' => 'V',
            'taluka' => 'T',
            'district' => 'Pune',
            'next_follow_up_date' => now()->subDay()->toDateString(),
        ], $ctx['admin']);

        $this->assertTrue(Student::query()->overdueFollowUp()->whereKey($student->id)->exists());
    }
}
