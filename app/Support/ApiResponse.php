<?php

namespace App\Support;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'data' => $data,
            'meta' => self::meta($meta),
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    public static function error(
        string $message,
        int $status,
        array $errors = [],
        array $meta = [],
        ApiErrorCode|string|null $code = null,
    ): JsonResponse {
        $resolvedCode = $code instanceof ApiErrorCode
            ? $code->value
            : ($code ?? ApiErrorCode::forStatus($status)->value);

        return response()->json([
            'success' => false,
            'code' => $resolvedCode,
            'message' => $message,
            'errors' => $errors,
            'meta' => self::meta($meta),
        ], $status);
    }

    private static function meta(array $meta): array
    {
        return array_filter([
            'requestId' => self::requestId(),
            'apiVersion' => (string) config('lbb.api.version', '1'),
            'contractVersion' => (string) config('lbb.api.contract_version', 'unknown'),
            ...$meta,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private static function requestId(): ?string
    {
        return app()->bound('lbb.request_id')
            ? (string) app('lbb.request_id')
            : null;
    }
}
