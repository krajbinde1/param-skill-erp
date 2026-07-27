<?php

namespace App\Models;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Concerns\BelongsToCentre;
use App\Support\SensitiveData;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToCentre, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'centre_id',
        'user_id',
        'employee_code',
        'first_name',
        'middle_name',
        'last_name',
        'father_husband_name',
        'mobile',
        'alternate_mobile',
        'email',
        'gender',
        'date_of_birth',
        'aadhaar_number_encrypted',
        'aadhaar_hash',
        'aadhaar_last4',
        'pan_number',
        'address',
        'village',
        'taluka',
        'district',
        'state',
        'pincode',
        'employee_role',
        'joining_date',
        'salary',
        'bank_name',
        'account_number',
        'ifsc_code',
        'emergency_contact',
        'profile_photo',
        'aadhaar_document',
        'pan_document',
        'education_certificate',
        'appointment_letter',
        'other_document',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'salary' => 'decimal:2',
            'aadhaar_number_encrypted' => 'encrypted',
            'account_number' => 'encrypted',
            'employee_role' => EmployeeRole::class,
            'status' => EmployeeStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Employee $employee): void {
            if ($employee->isForceDeleting()) {
                return;
            }

            $employee->aadhaar_hash = null;
            $employee->saveQuietly();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'centre_id',
                'employee_code',
                'first_name',
                'middle_name',
                'last_name',
                'mobile',
                'alternate_mobile',
                'email',
                'gender',
                'date_of_birth',
                'aadhaar_last4',
                'pan_number',
                'address',
                'village',
                'taluka',
                'district',
                'state',
                'pincode',
                'employee_role',
                'joining_date',
                'salary',
                'bank_name',
                'ifsc_code',
                'emergency_contact',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return Attribute::get(function (): string {
            return collect([$this->first_name, $this->middle_name, $this->last_name])
                ->filter()
                ->implode(' ');
        });
    }

    protected function maskedAadhaar(): Attribute
    {
        return Attribute::get(fn (): string => SensitiveData::maskAadhaar($this->aadhaar_last4));
    }

    protected function maskedPan(): Attribute
    {
        return Attribute::get(fn (): string => SensitiveData::maskPan($this->pan_number));
    }

    protected function maskedAccountNumber(): Attribute
    {
        return Attribute::get(fn (): string => SensitiveData::maskAccountNumber($this->account_number));
    }

    public function setAadhaar(?string $aadhaar): void
    {
        if (blank($aadhaar)) {
            $this->aadhaar_number_encrypted = null;
            $this->aadhaar_hash = null;
            $this->aadhaar_last4 = null;

            return;
        }

        $digits = preg_replace('/\D+/', '', $aadhaar) ?? '';
        $this->aadhaar_number_encrypted = $digits;
        $this->aadhaar_hash = SensitiveData::aadhaarHash($digits);
        $this->aadhaar_last4 = SensitiveData::aadhaarLast4($digits);
    }

    public function isActive(): bool
    {
        return $this->status === EmployeeStatus::Active;
    }
}
