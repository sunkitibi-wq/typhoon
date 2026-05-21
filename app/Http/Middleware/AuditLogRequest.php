<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditLogRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        if ($this->shouldSkipLogging($request)) {
            return $response;
        }

        AuditLog::create([
            'user_id' => optional($request->user())->id,
            'route_name' => optional($request->route())->getName(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'request_payload' => $this->sanitizePayload($request),
            'meta' => [
                'route_action' => optional($request->route())->getActionName(),
                'query' => $request->query(),
            ],
        ]);

        return $response;
    }

    protected function shouldSkipLogging(Request $request): bool
    {
        return $request->is('vendor/*') || $request->is('assets/*');
    }

    protected function sanitizePayload(Request $request): array
    {
        return Arr::except($request->except(['_token', '_method', 'password', 'password_confirmation', 'current_password']), [
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);
    }
}
