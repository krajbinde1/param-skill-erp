<?php

namespace App\Models;

use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentCentreVisitStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Scopes\StudentAccessScope;
use App\Support\SensitiveData;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_code',
        'client_uuid',
        'centre_id',
        'preferred_centre_id',
        'mobilizer_id',
        'first_name',
        'middle_name',
        'last_name',
        'father_name',
        'mother_name',
        'gender',
        'date_of_birth',
        'mobile',
        'parent_mobile',
        'alternate_mobile',
        'email',
        'aadhaar_encrypted',
        'aadhaar_hash',
        'aadhaar_last4',
        'full_address',
        'village',
        'taluka',
        'district',
        'state',
        'pincode',
        'religion',
        'caste',
        'category',
        'marital_status',
        'education_qualification',
        'school_college_name',
        'passing_year',
        'percentage_grade',
        'employment_status',
        'annual_family_income',
        'preferred_course',
        'hostel_required',
        'student_photo',
        'guardian_name',
        'guardian_relation',
        'guardian_mobile',
        'admission_status',
        'verification_status',
        'centre_visit_status',
        'next_follow_up_date',
        'rejection_reason',
        'remark',
        'submitted_at',
        'confirmed_at',
        'last_synced_at',
        'version',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hostel_required' => 'boolean',
            'annual_family_income' => 'decimal:2',
            'aadhaar_encrypted' => 'encrypted',
            'admission_status' => StudentAdmissionStatus::class,
            'verification_status' => StudentVerificationStatus::class,
            'centre_visit_status' => StudentCentreVisitStatus::class,
            'next_follow_up_date' => 'date',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new StudentAccessScope);

        static::deleting(function (Student $student): void {
            if ($student->isForceDeleting()) {
                return;
            }

            $student->aadhaar_hash = null;
            $student->saveQuietly();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'student_code',
                'centre_id',
                'preferred_centre_id',
                'mobilizer_id',
                'first_name',
                'middle_name',
                'last_name',
                'mobile',
                'parent_mobile',
                'district',
                'taluka',
                'village',
                'admission_status',
                'verification_status',
                'centre_visit_status',
                'next_follow_up_date',
                'hostel_required',
                'aadhaar_last4',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }

    public function preferredCentre(): BelongsTo
    {
        return $this->belongsTo(Centre::class, 'preferred_centre_id');
    }

    public function mobilizer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'mobilizer_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(StudentFollowUp::class);
    }

    public function centreVisits(): HasMany
    {
        return $this->hasMany(StudentCentreVisit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' '));
    }

    protected function maskedAadhaar(): Attribute
    {
        return Attribute::get(fn (): string => SensitiveData::maskAadhaar($this->aadhaar_last4));
    }

    public function setAadhaar(?string $aadhaar): void
    {
        if (blank($aadhaar)) {
            $this->aadhaar_encrypted = null;
            $this->aadhaar_hash = null;
            $this->aadhaar_last4 = null;

            return;
        }

        $digits = preg_replace('/\D+/', '', $aadhaar) ?? '';
        $this->aadhaar_encrypted = $digits;
        $this->aadhaar_hash = SensitiveData::aadhaarHash($digits);
        $this->aadhaar_last4 = SensitiveData::aadhaarLast4($digits);
    }

    public function scopeOverdueFollowUp(Builder $query): Builder
    {
        return $query
            ->whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<', now()->toDateString())
            ->whereNotIn('admission_status', [
                StudentAdmissionStatus::AdmissionConfirmed->value,
                StudentAdmissionStatus::JoinedCentre->value,
                StudentAdmissionStatus::Rejected->value,
                StudentAdmissionStatus::NotInterested->value,
            ]);
    }

    public function isEditableByMobilizer(): bool
    {
        if ($this->admission_status === StudentAdmissionStatus::Draft) {
            return true;
        }

        return $this->verification_status === StudentVerificationStatus::CorrectionRequired;
    }
}
