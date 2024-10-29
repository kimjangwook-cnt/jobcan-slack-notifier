<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IpRestrictionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $blockedIps = config('env.ip_restriction');
        $ip = $request->ip();

        if (!in_array($ip, $blockedIps) || $request->input('admin_key') !== config('env.admin_key')) {
            Log::info('IP 制限: ' . $ip);
            return response()->json(['message' => 'You are not allowed to access this service.'], 403);
        }

        return $next($request);
    }
}
