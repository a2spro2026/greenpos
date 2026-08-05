<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAuditActivity
{
    public function __construct(private AuditService $audit)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $response = $next($request);

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('audit_events')) {
                $this->audit->logFromRequest($request, $response->getStatusCode(), $started);
            }
        } catch (\Throwable) {
            // Never break the app because of audit logging
        }

        return $response;
    }
}
