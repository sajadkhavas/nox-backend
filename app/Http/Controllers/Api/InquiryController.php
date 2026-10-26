<?php

namespace App\Http\Controllers\Api;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\InquiryRequest;
use App\Models\Inquiry;
use App\Support\ApiResponse;
use App\Support\IranianMobile;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class InquiryController extends Controller
{
    public function store(InquiryRequest $request): JsonResponse
    {
        $ipHash = $request->ip()
            ? hash_hmac('sha256', $request->ip(), (string) config('app.key'))
            : null;
        $userAgentHash = $request->userAgent()
            ? hash('sha256', $request->userAgent())
            : null;
        $message = trim((string) $request->validated('message'));

        if ($ipHash && Inquiry::query()
            ->where('ip_hash', $ipHash)
            ->where('message', $message)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists()) {
            return ApiResponse::error('این درخواست اخیراً ثبت شده است.', 429);
        }

        try {
            $mobile = $request->filled('mobile')
                ? IranianMobile::normalize((string) $request->validated('mobile'))
                : null;
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error(
                'شماره موبایل معتبر نیست.',
                422,
                ['mobile' => [$exception->getMessage()]],
            );
        }

        $inquiry = Inquiry::query()->create([
            'customer_id' => $request->user('customer')?->getKey(),
            'type' => $request->validated('type'),
            'full_name' => trim((string) $request->validated('fullName')),
            'mobile' => $mobile,
            'email' => $this->nullableTrim($request->validated('email')),
            'subject' => $this->nullableTrim($request->validated('subject')),
            'message' => $message,
            'metadata' => $request->validated('metadata') ?? [],
            'status' => InquiryStatus::New,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
        ]);

        return ApiResponse::success([
            'inquiry' => [
                'id' => $inquiry->public_id,
                'type' => $inquiry->type->value,
                'status' => $inquiry->status->value,
            ],
        ], 'درخواست شما ثبت شد.', 201);
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
