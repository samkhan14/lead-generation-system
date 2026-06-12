<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIngestToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('ingest.token');

        if (! $token) {
            abort(503, 'Lead ingestion is not configured.');
        }

        $provided = $request->bearerToken();

        if (! $provided || ! hash_equals($token, $provided)) {
            abort(401, 'Invalid ingestion token.');
        }

        return $next($request);
    }
}
