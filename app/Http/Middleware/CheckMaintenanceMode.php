<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        // پنل ادمین رو چک نمیکنیم
        if ($request->is('admin*')) {
            return $next($request);
        }

        try {
            $setting = MaintenanceSetting::current();

            if ($setting->is_enabled) {
                $ip = $request->ip();

                // اگه IP مجاز بود، رد کن
                if ($setting->isIpAllowed($ip)) {
                    return $next($request);
                }

                // برای API درخواست‌ها JSON برگردون
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $setting->title,
                        'detail'  => $setting->message,
                    ], 503);
                }

                // صفحه maintenance
                return response()->view('maintenance', [
                    'title'        => $setting->title,
                    'message'      => $setting->message,
                    'scheduledEnd' => $setting->scheduled_end,
                ], 503);
            }
        } catch (\Exception $e) {
            // اگه DB در دسترس نبود، ادامه بده
        }

        return $next($request);
    }
}
