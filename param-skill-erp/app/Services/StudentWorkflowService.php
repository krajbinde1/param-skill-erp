<?php

namespace App\Services;

use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentCentreVisitStatus;
use App\Enums\StudentDocumentStatus;
use App\Enums\StudentDocumentType;
use App\Enums\StudentStatusType;
use App\Enums\StudentVerificationStatus;
use App\Enums\StudentVisitRecordStatus;
use App\Models\Scopes\StudentAccessScope;
use App\Models\Student;
use App\Models\StudentCentreVisit;
use App\Models\StudentDocument;
use App\Models\StudentFollowUp;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\Notifications\StudentStatusChangedNotification;
use App\Support\SensitiveData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentWorkflowService
{
    /**
     * @var array<string, list<string>>
     */
    protected array $transitions = [
        'Draft' => ['Submitted', 'Rejected', 'Not Interested', 'On Hold'],
        'Submitted' => ['Document Verification Pending', 'Rejected', 'Not Interested', 'On Hold'],
        'Document Verification Pending' => ['Documents Verified', 'Rejected', 'On Hold'],
        'Documents Verified' => ['Centre Visit Pending', 'Admission Pending', 'On Hold'],
        'Centre Visit Pending' => ['Centre Visited', 'On Hold', 'Not Interested'],
        'Centre Visited' => ['Interested', 'Not Interested', 'Admission Pending', 'On Hold'],
        'Interested' => ['Admission Pending', 'Not Interested', 'On Hold'],
        'Admission Pending' => ['Admission Confirmed', 'Rejected', 'On Hold'],
        'Admission Confirmed' => ['Joined Centre', 'On Hold'],
        'Joined Centre' => [],
        'Rejected' => ['On Hold', 'Submitted'],
        'Not Interested' => ['On Hold', 'Submitted'],
        'On Hold' => ['Submitted', 'Document Verification Pending', 'Admission Pending'],
    ];

    public function __construct(
        protected CodeGeneratorService $codes,
        protected CentreContext $centreContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, bool $fromApi = false): Student
    {
        if ($fromApi) {
            if (! $actor->employee_id) {
                throw ValidationException::withMessages(['mobilizer' => 'Mobilizer profile is required.']);
            }

            $data['mobilizer_id'] = $actor->employee_id;
            $data['centre_id'] = $actor->centre_id;
            $data['admission_status'] = StudentAdmissionStatus::Draft->value;
        } else {
            $this->centreContext->assertCentreAccess((int) ($data['centre_id'] ?? $data['preferred_centre_id'] ?? 0) ?: null, $actor);
        }

        if (filled($data['aadhaar_number'] ?? null)) {
            $this->assertUniqueAadhaar((string) $data['aadhaar_number']);
        }

        if (filled($data['client_uuid'] ?? null) && $fromApi) {
            $existing = Student::withoutGlobalScopes()
                ->where('mobilizer_id', $actor->employee_id)
                ->where('client_uuid', $data['client_uuid'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($data, $actor) {
            $aadhaar = $data['aadhaar_number'] ?? null;
            unset($data['aadhaar_number']);

            $student = new Student([
                ...$data,
                'student_code' => $this->codes->nextStudentCode(),
                'mobile' => SensitiveData::normalizeIndianMobile((string) $data['mobile']),
                'parent_mobile' => filled($data['parent_mobile'] ?? null) ? SensitiveData::normalizeIndianMobile((string) $data['parent_mobile']) : null,
                'alternate_mobile' => filled($data['alternate_mobile'] ?? null) ? SensitiveData::normalizeIndianMobile((string) $data['alternate_mobile']) : null,
                'state' => $data['state'] ?? 'Maharashtra',
                'admission_status' => $data['admission_status'] ?? StudentAdmissionStatus::Draft,
                'verification_status' => $data['verification_status'] ?? StudentVerificationStatus::Pending,
                'centre_visit_status' => $data['centre_visit_status'] ?? StudentCentreVisitStatus::NotPlanned,
                'version' => 1,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $student->setAadhaar(filled($aadhaar) ? (string) $aadhaar : null);
            $student->save();

            $this->recordHistory($student, null, $student->admission_status->value, StudentStatusType::Admission, $actor, 'Student created');

            activity()->causedBy($actor)->performedOn($student)->withProperties([
                'student_code' => $student->student_code,
                'mobilizer_id' => $student->mobilizer_id,
                'centre_id' => $student->centre_id,
            ])->log('student_created');

            return $student->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data, User $actor, bool $fromApi = false): Student
    {
        if ($fromApi && ! $student->isEditableByMobilizer()) {
            throw ValidationException::withMessages(['student' => 'This student can no longer be edited.']);
        }

        if ($fromApi) {
            unset($data['centre_id'], $data['mobilizer_id'], $data['admission_status']);
        }

        if (isset($data['version']) && (int) $data['version'] !== (int) $student->version) {
            throw ValidationException::withMessages([
                'version' => 'Conflict: the student was updated elsewhere. Refresh and try again.',
            ]);
        }

        if (filled($data['aadhaar_number'] ?? null)) {
            $this->assertUniqueAadhaar((string) $data['aadhaar_number'], $student->id);
        }

        return DB::transaction(function () use ($student, $data, $actor) {
            $aadhaar = $data['aadhaar_number'] ?? null;
            unset($data['aadhaar_number'], $data['version']);

            if (isset($data['mobile'])) {
                $data['mobile'] = SensitiveData::normalizeIndianMobile((string) $data['mobile']);
            }

            $student->fill([
                ...$data,
                'updated_by' => $actor->id,
                'version' => $student->version + 1,
                'last_synced_at' => now(),
            ]);

            if ($aadhaar !== null) {
                $student->setAadhaar(filled($aadhaar) ? (string) $aadhaar : null);
            }

            $student->save();

            activity()->causedBy($actor)->performedOn($student)->log('student_updated');

            return $student->fresh();
        });
    }

    public function submit(Student $student, User $actor): Student
    {
        return $this->transitionAdmission($student, StudentAdmissionStatus::Submitted, $actor, 'Submitted by mobilizer');
    }

    public function transitionAdmission(
        Student $student,
        StudentAdmissionStatus $newStatus,
        User $actor,
        ?string $remark = null,
        ?string $rejectionReason = null,
    ): Student {
        $current = $student->admission_status;
        $allowed = $this->transitions[$current->value] ?? [];

        if (! in_array($newStatus->value, $allowed, true) && $current !== $newStatus) {
            throw ValidationException::withMessages([
                'admission_status' => "Invalid transition from {$current->value} to {$newStatus->value}.",
            ]);
        }

        if (in_array($newStatus, [StudentAdmissionStatus::Rejected, StudentAdmissionStatus::NotInterested], true) && blank($rejectionReason) && blank($remark)) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'A reason is required for this status.',
            ]);
        }

        return DB::transaction(function () use ($student, $current, $newStatus, $actor, $remark, $rejectionReason) {
            $student->admission_status = $newStatus;
            $student->updated_by = $actor->id;
            $student->version++;

            if ($newStatus === StudentAdmissionStatus::Submitted) {
                $student->submitted_at = now();
                if ($student->verification_status === StudentVerificationStatus::Pending) {
                    $student->admission_status = StudentAdmissionStatus::DocumentVerificationPending;
                }
            }

            if ($newStatus === StudentAdmissionStatus::AdmissionConfirmed) {
                $student->confirmed_at = now();
            }

            if (filled($rejectionReason)) {
                $student->rejection_reason = $rejectionReason;
            }

            if (filled($remark)) {
                $student->remark = $remark;
            }

            $student->save();

            $finalStatus = $student->admission_status;
            $this->recordHistory($student, $current->value, $finalStatus->value, StudentStatusType::Admission, $actor, $remark ?? $rejectionReason);

            activity()->causedBy($actor)->performedOn($student)->withProperties([
                'from' => $current->value,
                'to' => $finalStatus->value,
            ])->log('admission_status_changed');

            $this->notifyMobilizer($student, 'Admission status updated to '.$finalStatus->value);

            return $student->fresh();
        });
    }

    public function uploadDocument(Student $student, UploadedFile $file, StudentDocumentType $type, User $actor): StudentDocument
    {
        $path = $file->storeAs(
            'students/'.$student->id.'/documents',
            Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
            'private'
        );

        $document = StudentDocument::query()->create([
            'student_id' => $student->id,
            'document_type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => StudentDocumentStatus::Uploaded,
            'uploaded_by' => $actor->id,
        ]);

        activity()->causedBy($actor)->performedOn($student)->withProperties([
            'document_type' => $type->value,
        ])->log('document_uploaded');

        return $document;
    }

    public function verifyDocument(
        StudentDocument $document,
        StudentDocumentStatus $status,
        User $actor,
        ?string $rejectionReason = null,
    ): StudentDocument {
        if (in_array($status, [StudentDocumentStatus::Rejected, StudentDocumentStatus::ReuploadRequired], true) && blank($rejectionReason)) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Rejection reason is required.',
            ]);
        }

        $document->update([
            'status' => $status,
            'rejection_reason' => $rejectionReason,
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);

        $student = $document->student;
        $this->refreshVerificationStatus($student, $actor);

        activity()->causedBy($actor)->performedOn($student)->withProperties([
            'document_type' => $document->document_type->value,
            'status' => $status->value,
        ])->log($status === StudentDocumentStatus::Verified ? 'document_verified' : 'document_rejected');

        $this->notifyMobilizer($student, 'Document '.$document->document_type->value.' marked as '.$status->value);

        return $document->fresh();
    }

    public function refreshVerificationStatus(Student $student, User $actor): void
    {
        $mandatory = StudentDocumentType::mandatory();
        $docs = $student->documents()->get();

        $allVerified = collect($mandatory)->every(function (StudentDocumentType $type) use ($docs) {
            return $docs->contains(fn (StudentDocument $doc) => $doc->document_type === $type && $doc->status === StudentDocumentStatus::Verified);
        });

        $hasRejected = $docs->contains(fn (StudentDocument $doc) => in_array($doc->status, [
            StudentDocumentStatus::Rejected,
            StudentDocumentStatus::ReuploadRequired,
        ], true));

        $old = $student->verification_status;

        if ($allVerified) {
            $student->verification_status = StudentVerificationStatus::Verified;
            if (in_array($student->admission_status, [
                StudentAdmissionStatus::DocumentVerificationPending,
                StudentAdmissionStatus::Submitted,
            ], true)) {
                $this->transitionAdmission($student, StudentAdmissionStatus::DocumentsVerified, $actor, 'All mandatory documents verified');
            }
        } elseif ($hasRejected) {
            $student->verification_status = StudentVerificationStatus::CorrectionRequired;
        } elseif ($docs->isNotEmpty()) {
            $student->verification_status = StudentVerificationStatus::UnderVerification;
        }

        if ($student->isDirty('verification_status')) {
            $student->updated_by = $actor->id;
            $student->save();
            $this->recordHistory($student, $old->value, $student->verification_status->value, StudentStatusType::Verification, $actor);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addFollowUp(Student $student, array $data, User $actor): StudentFollowUp
    {
        $followUp = StudentFollowUp::query()->create([
            'student_id' => $student->id,
            'mobilizer_id' => $data['mobilizer_id'] ?? $actor->employee_id ?? $student->mobilizer_id,
            'follow_up_date' => $data['follow_up_date'] ?? now()->toDateString(),
            'contacted' => (bool) ($data['contacted'] ?? false),
            'response' => $data['response'] ?? null,
            'interest_status' => $data['interest_status'] ?? null,
            'centre_visit_date' => $data['centre_visit_date'] ?? null,
            'next_follow_up_date' => $data['next_follow_up_date'] ?? null,
            'remark' => $data['remark'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'created_by' => $actor->id,
        ]);

        if (filled($followUp->next_follow_up_date)) {
            $student->update([
                'next_follow_up_date' => $followUp->next_follow_up_date,
                'updated_by' => $actor->id,
            ]);
        }

        activity()->causedBy($actor)->performedOn($student)->log('follow_up_added');

        return $followUp;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addCentreVisit(Student $student, array $data, User $actor): StudentCentreVisit
    {
        $visit = StudentCentreVisit::query()->create([
            'student_id' => $student->id,
            'centre_id' => $data['centre_id'] ?? $student->preferred_centre_id ?? $student->centre_id ?? $actor->centre_id,
            'mobilizer_id' => $data['mobilizer_id'] ?? $actor->employee_id ?? $student->mobilizer_id,
            'visit_date' => $data['visit_date'] ?? now()->toDateString(),
            'visit_status' => $data['visit_status'] ?? StudentVisitRecordStatus::CentreVisited,
            'student_photo' => $data['student_photo'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address' => $data['address'] ?? null,
            'created_by' => $actor->id,
        ]);

        $old = $student->centre_visit_status;
        $student->centre_visit_status = StudentCentreVisitStatus::CentreVisited;
        $student->updated_by = $actor->id;
        $student->save();

        $this->recordHistory($student, $old->value, $student->centre_visit_status->value, StudentStatusType::CentreVisit, $actor, 'Centre visit recorded');

        if ($student->admission_status === StudentAdmissionStatus::CentreVisitPending) {
            $this->transitionAdmission($student, StudentAdmissionStatus::CentreVisited, $actor, 'Centre visit recorded');
        }

        activity()->causedBy($actor)->performedOn($student)->log('centre_visit_added');

        return $visit;
    }

    public function confirmCentreVisit(StudentCentreVisit $visit, User $actor, ?string $remark = null): StudentCentreVisit
    {
        $this->centreContext->assertCentreAccess($visit->centre_id, $actor);

        $visit->update([
            'visit_status' => StudentVisitRecordStatus::VisitConfirmed,
            'manager_remark' => $remark,
            'confirmed_by' => $actor->id,
            'confirmed_at' => now(),
        ]);

        $student = $visit->student;
        $old = $student->centre_visit_status;
        $student->centre_visit_status = StudentCentreVisitStatus::VisitConfirmed;
        $student->updated_by = $actor->id;
        $student->save();

        $this->recordHistory($student, $old->value, $student->centre_visit_status->value, StudentStatusType::CentreVisit, $actor, $remark);
        $this->notifyMobilizer($student, 'Centre visit confirmed');

        activity()->causedBy($actor)->performedOn($student)->log('centre_visit_confirmed');

        return $visit->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reassign(Student $student, array $data, User $actor): Student
    {
        return DB::transaction(function () use ($student, $data, $actor) {
            $old = [
                'centre_id' => $student->centre_id,
                'mobilizer_id' => $student->mobilizer_id,
                'preferred_centre_id' => $student->preferred_centre_id,
            ];

            $student->fill([
                'centre_id' => $data['centre_id'] ?? $student->centre_id,
                'mobilizer_id' => $data['mobilizer_id'] ?? $student->mobilizer_id,
                'preferred_centre_id' => $data['preferred_centre_id'] ?? $student->preferred_centre_id,
                'updated_by' => $actor->id,
                'version' => $student->version + 1,
            ]);

            $student->save();

            $new = [
                'centre_id' => $student->centre_id,
                'mobilizer_id' => $student->mobilizer_id,
                'preferred_centre_id' => $student->preferred_centre_id,
            ];

            $remark = $data['remark'] ?? 'Reassigned centre/mobilizer';
            $this->recordHistory($student, $student->admission_status->value, $student->admission_status->value, StudentStatusType::Admission, $actor, $remark);

            activity()->causedBy($actor)->performedOn($student)->withProperties([
                'old' => $old,
                'new' => $new,
            ])->log('student_reassigned');

            return $student->fresh();
        });
    }

    public function assertUniqueAadhaar(string $aadhaar, ?int $ignoreId = null): void
    {
        if (! SensitiveData::isValidAadhaar($aadhaar)) {
            throw ValidationException::withMessages([
                'aadhaar_number' => 'Aadhaar number must be exactly 12 digits.',
            ]);
        }

        $exists = Student::withoutGlobalScope(StudentAccessScope::class)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('aadhaar_hash', SensitiveData::aadhaarHash($aadhaar))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'aadhaar_number' => 'This Aadhaar number is already registered.',
            ]);
        }
    }

    protected function recordHistory(
        Student $student,
        ?string $old,
        string $new,
        StudentStatusType $type,
        User $actor,
        ?string $remark = null,
    ): void {
        StudentStatusHistory::query()->create([
            'student_id' => $student->id,
            'old_status' => $old,
            'new_status' => $new,
            'status_type' => $type,
            'remark' => $remark,
            'next_follow_up_date' => $student->next_follow_up_date,
            'changed_by' => $actor->id,
            'changed_at' => now(),
        ]);
    }

    protected function notifyMobilizer(Student $student, string $message): void
    {
        $user = $student->mobilizer?->user;

        if ($user) {
            $user->notify(new StudentStatusChangedNotification($student, $message));
        }
    }
}
