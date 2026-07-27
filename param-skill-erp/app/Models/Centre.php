<?php

namespace App\Models;

use App\Enums\CentreStatus;
use App\Enums\RoleName;
use Database\Factories\CentreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Centre extends Model
{
    /** @use HasFactory<CentreFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'centre_code',
        'centre_name',
        'scheme_name',
        'project_name',
        'manager_name',
        'manager_mobile',
        'manager_email',
        'address',
        'village_city',
        'taluka',
        'district',
        'state',
        'pincode',
        'centre_capacity',
        'boys_capacity',
        'girls_capacity',
        'hostel_available',
        'latitude',
        'longitude',
        'opening_date',
        'agreement_start_date',
        'agreement_end_date',
        'centre_photo',
        'agreement_document',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'hostel_available' => 'boolean',
            'centre_capacity' => 'integer',
            'boys_capacity' => 'integer',
            'girls_capacity' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'opening_date' => 'date',
            'agreement_start_date' => 'date',
            'agreement_end_date' => 'date',
            'status' => CentreStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'centre_code',
                'centre_name',
                'scheme_name',
                'project_name',
                'manager_name',
                'manager_mobile',
                'manager_email',
                'address',
                'village_city',
                'taluka',
                'district',
                'state',
                'pincode',
                'centre_capacity',
                'boys_capacity',
                'girls_capacity',
                'hostel_available',
                'latitude',
                'longitude',
                'opening_date',
                'agreement_start_date',
                'agreement_end_date',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function managerUser(): HasOne
    {
        return $this->hasOne(User::class)->whereHas('roles', function ($query) {
            $query->where('name', RoleName::CentreManager->value);
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === CentreStatus::Active;
    }
}
