<?php

namespace App\Models;

use App\Enums\StudentVisitRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentCentreVisit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'centre_id',
        'mobilizer_id',
        'visit_date',
        'visit_status',
        'student_photo',
        'latitude',
        'longitude',
        'address',
        'manager_remark',
        'confirmed_by',
        'confirmed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'visit_status' => StudentVisitRecordStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'confirmed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }

    public function mobilizer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'mobilizer_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
