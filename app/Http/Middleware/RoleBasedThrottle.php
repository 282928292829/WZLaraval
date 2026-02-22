<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedThrottle
{
    public function __construct(protected RateLimiter $limiter) {}

    /**
     * Apply role-aware rate limiting, reading limits from the settings table.
     *
     * Staff (editor/admin/superadmin) → orders_per_hour_admin (default 50).
     * Everyone else (customers, guests)  → orders_per_hour_customer (default 10).
     */
    public function handle(Request $request, Closure $next, string $key = 'new-order'): Response
    {
        $user = $request->user();

        $isStaff = $user && $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        $maxAttempts = $isStaff
            ? (int) Setting::get('orders_per_hour_admin', 50)
            : (int) Setting::get('orders_per_hour_customer', 10);

        $decaySeconds = 3600; // 1 hour

        $limiterKey = $key.':'.($user ? $user->id : $request->ip());

        if ($this->limiter->tooManyAttempts($limiterKey, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($limiterKey);

            return response()->json([
                'message' => __('Too many requests. Please try again later.'),
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($limiterKey, $decaySeconds);

        $response = $next($request);

        $remaining = max(0, $maxAttempts - $this->limiter->attempts($limiterKey));

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);
    }
}
