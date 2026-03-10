<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiDiagnosticsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('app.api_diagnostics_enabled', false), 404);

        return $next($request);
    }
}
