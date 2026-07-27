<?php

namespace App\Models;

use App\Enums\StudentDocumentStatus;
use App\Enums\StudentDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => StudentDocumentType::class,
            'status' => StudentDocumentStatus::class,
            'verified_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
