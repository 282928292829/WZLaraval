<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppIsLocal
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->isLocal(), 403);

        return $next($request);
    }
}
