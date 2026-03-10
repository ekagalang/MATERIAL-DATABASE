<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebDiagnosticsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.web_diagnostics_enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
