<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Notifications\StudentSubmittedNotification;
use App\Services\StudentWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function __construct(protected StudentWorkflowService $workflow) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $students = Student::query()
            ->where('mobilizer_id', $user->employee_id)
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('student_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('admission_status'), fn ($q) => $q->where('admission_status', $request->string('admission_status')))
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Students retrieved.',
            'data' => StudentResource::collection($students)->resolve(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedStudent($request);
        /** @var User $user */
        $user = $request->user();

        $wasExisting = filled($data['client_uuid'] ?? null)
            && Student::withoutGlobalScopes()
                ->where('mobilizer_id', $user->employee_id)
                ->where('client_uuid', $data['client_uuid'])
                ->exists();

        $student = $this->workflow->create($data, $user, fromApi: true);

        return response()->json([
            'success' => true,
            'message' => $wasExisting ? 'Existing student returned for client_uuid.' : 'Student created.',
            'data' => new StudentResource($student->load(['centre', 'preferredCentre', 'mobilizer'])),
            'meta' => (object) [],
        ], $wasExisting ? 200 : 201);
    }

    public function show(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);

        return response()->json([
            'success' => true,
            'message' => 'Student retrieved.',
            'data' => new StudentResource($student->load(['centre', 'preferredCentre', 'mobilizer', 'documents', 'followUps', 'centreVisits'])),
            'meta' => (object) [],
        ]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);
        $data = $this->validatedStudent($request, partial: true);
        $student = $this->workflow->update($student, $data, $request->user(), fromApi: true);

        return response()->json([
            'success' => true,
            'message' => 'Student updated.',
            'data' => new StudentResource($student->fresh()->load(['centre', 'preferredCentre', 'mobilizer'])),
            'meta' => (object) [],
        ]);
    }

    public function submit(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);
        $student = $this->workflow->submit($student, $request->user());

        $manager = $student->centre?->managerUser
            ?? $student->preferredCentre?->managerUser
            ?? null;

        if ($manager) {
            $manager->notify(new StudentSubmittedNotification($student));
        }

        return response()->json([
            'success' => true,
            'message' => 'Student submitted.',
            'data' => new StudentResource($student),
            'meta' => (object) [],
        ]);
    }

    public function documents(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);

        return response()->json([
            'success' => true,
            'message' => 'Documents retrieved.',
            'data' => $student->documents,
            'meta' => (object) [],
        ]);
    }

    public function uploadDocument(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);

        $data = $request->validate([
            'document_type' => ['required', Rule::enum(StudentDocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $document = $this->workflow->uploadDocument(
            $student,
            $request->file('file'),
            StudentDocumentType::from($data['document_type']),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded.',
            'data' => $document,
            'meta' => (object) [],
        ], 201);
    }

    public function deleteDocument(Request $request, Student $student, StudentDocument $document): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);

        if ($document->student_id !== $student->id) {
            abort(404);
        }

        if ($document->status->value === 'Verified') {
            return response()->json(['success' => false, 'message' => 'Verified documents cannot be deleted.'], 422);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted.',
            'data' => [],
            'meta' => (object) [],
        ]);
    }

    public function followUps(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);

        return response()->json([
            'success' => true,
            'message' => 'Follow-ups retrieved.',
            'data' => $student->followUps()->latest()->get(),
            'meta' => (object) [],
        ]);
    }

    public function storeFollowUp(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);

        $data = $request->validate([
            'follow_up_date' => ['nullable', 'date'],
            'contacted' => ['nullable', 'boolean'],
            'response' => ['nullable', 'string'],
            'interest_status' => ['nullable', 'string'],
            'centre_visit_date' => ['nullable', 'date'],
            'next_follow_up_date' => ['nullable', 'date'],
            'remark' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $followUp = $this->workflow->addFollowUp($student, $data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Follow-up added.',
            'data' => $followUp,
            'meta' => (object) [],
        ], 201);
    }

    public function centreVisits(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);

        return response()->json([
            'success' => true,
            'message' => 'Centre visits retrieved.',
            'data' => $student->centreVisits()->latest()->get(),
            'meta' => (object) [],
        ]);
    }

    public function storeCentreVisit(Request $request, Student $student): JsonResponse
    {
        $this->authorizeMobilizer($request->user(), $student);

        $data = $request->validate([
            'centre_id' => ['nullable', 'exists:centres,id'],
            'visit_date' => ['nullable', 'date'],
            'student_photo' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string'],
            'remark' => ['nullable', 'string'],
        ]);

        $visit = $this->workflow->addCentreVisit($student, $data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Centre visit recorded.',
            'data' => $visit,
            'meta' => (object) [],
        ], 201);
    }

    public function dashboard(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $query = Student::query()->where('mobilizer_id', $user->employee_id);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard loaded.',
            'data' => [
                'total_students' => (clone $query)->count(),
                'draft' => (clone $query)->where('admission_status', StudentAdmissionStatus::Draft)->count(),
                'submitted' => (clone $query)->whereIn('admission_status', [
                    StudentAdmissionStatus::Submitted,
                    StudentAdmissionStatus::DocumentVerificationPending,
                ])->count(),
                'admission_confirmed' => (clone $query)->where('admission_status', StudentAdmissionStatus::AdmissionConfirmed)->count(),
                'joined' => (clone $query)->where('admission_status', StudentAdmissionStatus::JoinedCentre)->count(),
                'overdue_follow_ups' => (clone $query)->overdueFollowUp()->count(),
            ],
            'meta' => (object) [],
        ]);
    }

    protected function authorizeMobilizer(User $user, Student $student): void
    {
        if ($student->mobilizer_id !== $user->employee_id) {
            abort(403, 'You are not authorized to perform this action.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedStudent(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        // Ownership fields are always server-assigned for Mobilizer API.
        $request->request->remove('centre_id');
        $request->request->remove('mobilizer_id');

        return $request->validate([
            'client_uuid' => ['nullable', 'uuid'],
            'version' => ['nullable', 'integer'],
            'first_name' => [$required, 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => [$required, 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'mobile' => [$required, 'regex:/^[6-9]\d{9}$/'],
            'parent_mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'alternate_mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'email' => ['nullable', 'email'],
            'aadhaar_number' => ['nullable', 'regex:/^\d{12}$/'],
            'full_address' => [$required, 'string'],
            'village' => [$required, 'string'],
            'taluka' => [$required, 'string'],
            'district' => [$required, 'string'],
            'state' => ['nullable', 'string'],
            'pincode' => ['nullable', 'regex:/^\d{6}$/'],
            'religion' => ['nullable', 'string'],
            'caste' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string'],
            'education_qualification' => ['nullable', 'string'],
            'school_college_name' => ['nullable', 'string'],
            'passing_year' => ['nullable', 'string', 'max:10'],
            'percentage_grade' => ['nullable', 'string'],
            'employment_status' => ['nullable', 'string'],
            'annual_family_income' => ['nullable', 'numeric', 'min:0'],
            'preferred_course' => ['nullable', 'string'],
            'preferred_centre_id' => ['nullable', 'exists:centres,id'],
            'hostel_required' => ['nullable', 'boolean'],
            'guardian_name' => ['nullable', 'string'],
            'guardian_relation' => ['nullable', 'string'],
            'guardian_mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'remark' => ['nullable', 'string'],
            'next_follow_up_date' => ['nullable', 'date'],
        ]);
    }
}
