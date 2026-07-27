<?php

namespace App\Models;

use App\Enums\StudentStatusType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatusHistory extends Model
{
    protected $fillable = [
        'student_id',
        'old_status',
        'new_status',
        'status_type',
        'remark',
        'next_follow_up_date',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'status_type' => StudentStatusType::class,
            'next_follow_up_date' => 'date',
            'changed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
