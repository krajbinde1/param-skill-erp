<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Services\CentreAccessService;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'login_id',
        'email',
        'mobile',
        'password',
        'status',
        'must_change_password',
        'last_login_at',
        'centre_id',
        'employee_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'centre_id' => 'integer',
            'employee_id' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'login_id',
                'email',
                'mobile',
                'status',
                'must_change_password',
                'centre_id',
                'employee_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdCentres(): HasMany
    {
        return $this->hasMany(Centre::class, 'created_by');
    }

    public function createdEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'created_by');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return app(CentreAccessService::class)->canAccessPanel($this);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin->value);
    }

    public function isCentreManager(): bool
    {
        return $this->hasRole(RoleName::CentreManager->value);
    }

    public function isElevated(): bool
    {
        return $this->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::Admin->value,
        ]);
    }

    public function canAccessAdminPanelByRole(): bool
    {
        return $this->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::Admin->value,
            RoleName::CentreManager->value,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function primaryRoleName(): ?string
    {
        return $this->roles->first()?->name;
    }
}
