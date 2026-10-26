<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OtpCooldown;
use App\Exceptions\OtpDeliveryUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Services\Auth\OtpService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OtpAuthController extends Controller
{
    public function requestOtp(Request $request, OtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'max:32'],
        ]);

        try {
            $challenge = $otp->request($validated['mobile'], $request);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['mobile' => [$exception->getMessage()]]);
        } catch (OtpCooldown $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                429,
                [],
                ['retryAfter' => $exception->retryAfter],
            )->header('Retry-After', (string) $exception->retryAfter);
        } catch (OtpDeliveryUnavailable $exception) {
            return ApiResponse::error($exception->getMessage(), 503);
        }

        return ApiResponse::success(
            $challenge,
            'اگر شماره قابل ارسال باشد، کد ورود ارسال شد.',
            202,
        );
    }

    public function verify(Request $request, OtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'max:32'],
            'challengeId' => ['required', 'string', 'size:26'],
            'code' => ['required', 'string', 'max:12'],
        ]);

        try {
            $customer = $otp->verify(
                $validated['mobile'],
                $validated['challengeId'],
                $validated['code'],
                $request,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['mobile' => [$exception->getMessage()]]);
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return ApiResponse::success([
            'user' => (new CustomerResource($customer))->resolve($request),
        ], 'ورود با موفقیت انجام شد.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => (new CustomerResource($request->user('customer')))->resolve($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(null, 'از حساب خارج شدید.');
    }
}
