<?php

namespace App\Services\Auth;

use App\Exceptions\OtpCooldown;
use App\Models\Customer;
use App\Models\OtpChallenge;
use App\Support\IranianMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OtpService
{
    public function __construct(private readonly OtpSender $sender) {}

    public function request(string $rawMobile, Request $request): array
    {
        $mobile = IranianMobile::normalize($rawMobile);
        $mobileHash = IranianMobile::hash($mobile);
        $retryAfter = (int) config('lbb.otp.retry_after_seconds', 60);
        $expiresIn = (int) config('lbb.otp.expires_seconds', 120);

        $latest = OtpChallenge::query()
            ->where('mobile_hash', $mobileHash)
            ->latest('id')
            ->first();

        if ($latest?->resend_available_at?->isFuture()) {
            throw new OtpCooldown(now()->diffInSeconds($latest->resend_available_at));
        }

        $length = (int) config('lbb.otp.length', 6);
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        $challenge = DB::transaction(function () use (
            $mobile,
            $mobileHash,
            $code,
            $expiresIn,
            $retryAfter,
            $request,
        ): OtpChallenge {
            OtpChallenge::query()
                ->where('mobile_hash', $mobileHash)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return OtpChallenge::query()->create([
                'mobile_hash' => $mobileHash,
                'mobile_payload' => $mobile,
                'code_hash' => Hash::make($code),
                'purpose' => 'login',
                'max_attempts' => (int) config('lbb.otp.max_attempts', 5),
                'expires_at' => now()->addSeconds($expiresIn),
                'resend_available_at' => now()->addSeconds($retryAfter),
                'request_ip_hash' => $this->fingerprint($request->ip()),
                'user_agent_hash' => $this->fingerprint($request->userAgent()),
            ]);
        });

        try {
            $this->sender->send($mobile, $code);
        } catch (Throwable $exception) {
            $challenge->delete();
            throw $exception;
        }

        return array_filter([
            'challengeId' => $challenge->public_id,
            'expiresIn' => $expiresIn,
            'retryAfter' => $retryAfter,
            'debugCode' => $this->mayExposeTestCode() ? $code : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function verify(
        string $rawMobile,
        string $challengeId,
        string $rawCode,
        Request $request,
    ): Customer {
        $mobile = IranianMobile::normalize($rawMobile);
        $mobileHash = IranianMobile::hash($mobile);
        $code = $this->normalizeCode($rawCode);

        $customer = DB::transaction(function () use (
            $mobile,
            $mobileHash,
            $challengeId,
            $code,
            $request,
        ): ?Customer {
            $challenge = OtpChallenge::query()
                ->where('public_id', $challengeId)
                ->lockForUpdate()
                ->first();

            if (! $challenge || ! hash_equals($challenge->mobile_hash, $mobileHash) || ! $challenge->isUsable()) {
                return null;
            }

            $challenge->attempts++;
            $challenge->save();

            if (! Hash::check($code, $challenge->code_hash)) {
                return null;
            }

            $customer = Customer::withTrashed()->firstOrNew(['mobile' => $mobile]);

            if ($customer->exists && ($customer->trashed() || ! $customer->is_active)) {
                throw ValidationException::withMessages([
                    'mobile' => ['امکان ورود به این حساب وجود ندارد.'],
                ]);
            }

            $customer->fill([
                'mobile_verified_at' => now(),
                'last_login_at' => now(),
            ]);
            $customer->save();

            $challenge->forceFill([
                'customer_id' => $customer->id,
                'consumed_at' => now(),
                'request_ip_hash' => $this->fingerprint($request->ip()),
                'user_agent_hash' => $this->fingerprint($request->userAgent()),
            ])->save();

            return $customer->fresh();
        });

        if (! $customer) {
            throw ValidationException::withMessages([
                'code' => ['کد ورود معتبر نیست یا منقضی شده است.'],
            ]);
        }

        return $customer;
    }

    private function normalizeCode(string $value): string
    {
        $digits = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (! preg_match('/^\d{'.(int) config('lbb.otp.length', 6).'}$/', $digits)) {
            throw ValidationException::withMessages(['code' => ['کد ورود معتبر نیست.']]);
        }

        return $digits;
    }

    private function fingerprint(?string $value): ?string
    {
        return $value ? hash_hmac('sha256', $value, (string) config('app.key')) : null;
    }

    private function mayExposeTestCode(): bool
    {
        return (bool) config('lbb.otp.expose_test_code')
            && app()->environment(['local', 'testing']);
    }
}
