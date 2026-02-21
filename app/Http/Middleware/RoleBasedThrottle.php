<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedThrottle
{
    public function __construct(protected RateLimiter $limiter) {}

    /**
     * Apply role-aware rate limiting.
     *
     * Staff (editor/admin/superadmin) → 50 attempts per hour.
     * Everyone else (customers, guests) → 10 attempts per hour.
     */
    public function handle(Request $request, Closure $next, string $key = 'new-order'): Response
    {
        $user = $request->user();

        $isStaff = $user && $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        $maxAttempts = $isStaff ? 50 : 10;
        $decaySeconds = 3600; // 1 hour

        $limiterKey = $key . ':' . ($user ? $user->id : $request->ip());

        if ($this->limiter->tooManyAttempts($limiterKey, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($limiterKey);

            return response()->json([
                'message' => __('Too many requests. Please try again later.'),
            ], 429)->withHeaders([
                'Retry-After'           => $retryAfter,
                'X-RateLimit-Limit'     => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($limiterKey, $decaySeconds);

        $response = $next($request);

        $remaining = max(0, $maxAttempts - $this->limiter->attempts($limiterKey));

        return $response->withHeaders([
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);
    }
}
