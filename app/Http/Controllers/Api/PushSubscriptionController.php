<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PushSubscription;
use App\Services\Notifications\WebPushService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class PushSubscriptionController extends Controller
{
    public function configuration(WebPushService $webPush): JsonResponse
    {
        return ApiResponse::success([
            'enabled' => $webPush->ready(),
            'publicKey' => $webPush->ready() ? $webPush->publicKey() : null,
        ]);
    }

    public function index(): JsonResponse
    {
        $customer = $this->customer();

        return ApiResponse::success([
            'activeCount' => $customer->pushSubscriptions()->active()->count(),
        ]);
    }

    public function storeGuest(Request $request, WebPushService $webPush): JsonResponse
    {
        if (! $webPush->ready()) {
            return ApiResponse::error('اعلان‌های فروشگاه هنوز فعال نیستند.', 503);
        }

        $validated = $request->validate([
            'guestToken' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43,128}$/'],
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:256'],
            'contentEncoding' => ['nullable', Rule::in(['aes128gcm', 'aesgcm'])],
            'marketingEnabled' => ['accepted'],
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*' => ['required', Rule::in(['product_updates', 'editorial'])],
        ]);

        $hash = PushSubscription::endpointHash($validated['endpoint']);
        $tokenHash = hash('sha256', $validated['guestToken']);
        DB::transaction(function () use ($validated, $hash, $tokenHash, $request): void {
            $existing = PushSubscription::query()->where('endpoint_hash', $hash)->lockForUpdate()->first();
            if ($existing && $existing->customer_id !== null) {
                abort(409, 'این دستگاه قبلاً به حساب کاربری متصل شده است.');
            }
            if ($existing && $existing->guest_token_hash !== $tokenHash) {
                abort(409, 'این دستگاه به اشتراک دیگری متصل شده است.');
            }
            $subscription = $existing ?? new PushSubscription(['endpoint_hash' => $hash]);
            $subscription->forceFill([
                'customer_id' => null,
                'guest_token_hash' => $tokenHash,
                'marketing_enabled' => true,
                'preferences' => array_values(array_unique($validated['preferences'])),
                'endpoint' => $validated['endpoint'],
                'p256dh' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ])->save();
        });

        return ApiResponse::success(['subscribed' => true], 'اعلان‌های این دستگاه فعال شد.');
    }

    public function destroyGuest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guestToken' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43,128}$/'],
            'endpoint' => ['required', 'url', 'max:2048'],
        ]);
        PushSubscription::query()
            ->whereNull('customer_id')
            ->where('guest_token_hash', hash('sha256', $validated['guestToken']))
            ->where('endpoint_hash', PushSubscription::endpointHash($validated['endpoint']))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'updated_at' => now()]);

        return ApiResponse::success(['subscribed' => false], 'اعلان‌های این دستگاه غیرفعال شد.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048', 'url', 'starts_with:https://'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:256'],
            'contentEncoding' => ['nullable', 'string', Rule::in(['aes128gcm', 'aesgcm'])],
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['required', Rule::in(['product_updates', 'editorial'])],
            'guestToken' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{43,128}$/'],
        ]);

        $customer = $this->customer();
        $endpoint = trim($validated['endpoint']);
        $hash = PushSubscription::endpointHash($endpoint);

        $subscription = PushSubscription::query()->firstOrNew(['endpoint_hash' => $hash]);
        if ($subscription->exists && $subscription->customer_id !== null &&
            (int) $subscription->customer_id !== (int) $customer->getKey()) {
            return ApiResponse::error('این دستگاه به حساب دیگری متصل است.', 409);
        }
        if ($subscription->exists && $subscription->customer_id === null &&
            ! hash_equals((string) $subscription->guest_token_hash, hash('sha256', (string) ($validated['guestToken'] ?? '')))) {
            return ApiResponse::error('برای انتقال اعلان‌های مهمان، ابتدا در همین دستگاه دوباره رضایت دهید.', 409);
        }
        $subscription->forceFill([
            'customer_id' => $customer->getKey(),
            'guest_token_hash' => null,
            'marketing_enabled' => ! empty($validated['preferences']),
            'preferences' => array_values(array_unique($validated['preferences'] ?? [])),
            'endpoint' => $endpoint,
            'p256dh' => $validated['keys']['p256dh'],
            'auth_token' => $validated['keys']['auth'],
            'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
            'last_seen_at' => now(),
            'revoked_at' => null,
        ])->save();

        return ApiResponse::success([
            'subscriptionId' => $subscription->public_id,
            'activeCount' => $customer->pushSubscriptions()->active()->count(),
        ], 'اعلان‌های این دستگاه فعال شد.', $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048', 'url'],
        ]);
        $customer = $this->customer();

        PushSubscription::query()
            ->where('customer_id', $customer->getKey())
            ->where('endpoint_hash', PushSubscription::endpointHash($validated['endpoint']))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'updated_at' => now()]);

        return ApiResponse::success([
            'activeCount' => $customer->pushSubscriptions()->active()->count(),
        ], 'اعلان‌های این دستگاه غیرفعال شد.');
    }

    public function test(WebPushService $webPush): JsonResponse
    {
        if (! $webPush->ready()) {
            return ApiResponse::error('سرویس Web Push هنوز در محیط سرور فعال نشده است.', 503);
        }

        $result = $webPush->sendToCustomer($this->customer(), [
            'title' => 'اعلان‌های LBB فعال است',
            'body' => 'این اعلان آزمایشی فقط برای تأیید اتصال همین دستگاه ارسال شد.',
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/icon-192.png',
            'url' => '/account',
            'tag' => 'lbb-web-push-test',
            'data' => ['kind' => 'test'],
        ]);

        if ($result['sent'] < 1) {
            return ApiResponse::error('اعلان آزمایشی به هیچ اشتراک فعالی تحویل نشد.', 503, [], [
                'revoked' => $result['revoked'],
                'failed' => $result['failed'],
            ]);
        }

        return ApiResponse::success($result, 'اعلان آزمایشی ارسال شد.');
    }

    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return $customer;
    }
}
