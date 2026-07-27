<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\User;
use App\Policies\CentrePolicy;
use App\Policies\EmployeePolicy;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Centre::class => CentrePolicy::class,
        Employee::class => EmployeePolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Gate::before(function (?User $user, string $ability): ?bool {
            if ($user === null) {
                return null;
            }

            if (! $user->isActive()) {
                return false;
            }

            if ($user->hasRole(RoleName::SuperAdmin->value)) {
                return true;
            }

            return null;
        });

        Event::listen(LoginEvent::class, function (LoginEvent $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            $user->forceFill(['last_login_at' => now()])->saveQuietly();

            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->log('login');
        });
    }
}
