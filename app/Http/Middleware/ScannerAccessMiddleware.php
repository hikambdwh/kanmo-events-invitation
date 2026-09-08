<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScannerAccessMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (
            !$request->user()
            || !$request->user()->canScan()
        ) {
            abort(403);
        }

        return $next($request);
    }
}
