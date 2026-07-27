<?php

namespace App\Http\Middleware;

use App\Services\CentreAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessAdmin
{
    public function __construct(
        protected CentreAccessService $centreAccessService
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $this->centreAccessService->canAccessPanel($user)) {
            $reason = $this->centreAccessService->denialReason($user) ?? 'Access denied.';
            auth()->logout();

            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['data.login_id' => $reason]);
        }

        return $next($request);
    }
}
