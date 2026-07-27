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
            auth()->logout();

            abort(403, 'Your account is inactive or not allowed to access this panel.');
        }

        return $next($request);
    }
}
