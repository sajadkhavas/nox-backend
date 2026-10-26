<?php
namespace App\Http\Middleware;

use App\Models\IpBlacklist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckIpBlacklist
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin*')) {
            return $next($request);
        }

        $ip = $request->ip();
        $cacheKey = "ip_blocked:{$ip}";

        $isBlocked = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($ip) {
            return IpBlacklist::isBlocked($ip);
        });

        if ($isBlocked) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی شما مسدود شده است.',
                ], 403);
            }
            abort(403, 'دسترسی شما مسدود شده است.');
        }

        return $next($request);
    }
}
