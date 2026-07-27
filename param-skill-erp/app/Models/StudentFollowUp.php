<?php

namespace App\Models;

use App\Enums\StudentInterestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFollowUp extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'mobilizer_id',
        'follow_up_date',
        'contacted',
        'response',
        'interest_status',
        'centre_visit_date',
        'next_follow_up_date',
        'remark',
        'latitude',
        'longitude',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
            'contacted' => 'boolean',
            'interest_status' => StudentInterestStatus::class,
            'centre_visit_date' => 'date',
            'next_follow_up_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mobilizer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'mobilizer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
