<?php

namespace App\Support;

use App\Enums\ApiErrorCode;
use App\Exceptions\CommerceException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ApiExceptionRenderer
{
    public static function render(Throwable $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof CommerceException => ApiResponse::error(
                $exception->getMessage(), $exception->status, $exception->errors, $exception->meta, $exception->commerceCode->value,
            ),
            $exception instanceof ValidationException => ApiResponse::error(
                'اطلاعات ارسال‌شده معتبر نیست.', 422, $exception->errors(), code: ApiErrorCode::ValidationFailed,
            ),
            $exception instanceof AuthenticationException => ApiResponse::error(
                'برای انجام این عملیات باید وارد حساب کاربری شوید.', 401, code: ApiErrorCode::AuthenticationRequired,
            ),
            $exception instanceof AuthorizationException => ApiResponse::error(
                'اجازه انجام این عملیات را ندارید.', 403, code: ApiErrorCode::AccessDenied,
            ),
            $exception instanceof ModelNotFoundException, $exception instanceof NotFoundHttpException => ApiResponse::error(
                'منبع درخواستی پیدا نشد.', 404, code: ApiErrorCode::ResourceNotFound,
            ),
            $exception instanceof ThrottleRequestsException => ApiResponse::error(
                'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', 429, code: ApiErrorCode::RateLimited,
            ),
            $exception instanceof HttpExceptionInterface => ApiResponse::error(
                self::messageForStatus($exception->getStatusCode()), $exception->getStatusCode(), code: ApiErrorCode::forStatus($exception->getStatusCode()),
            ),
            default => ApiResponse::error(
                'خطای داخلی رخ داد. شناسه درخواست را برای پشتیبانی نگه دارید.', 500, code: ApiErrorCode::InternalError,
            ),
        };
    }

    private static function messageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'درخواست معتبر نیست.', 401 => 'برای انجام این عملیات باید وارد حساب کاربری شوید.',
            403 => 'اجازه انجام این عملیات را ندارید.', 404 => 'منبع درخواستی پیدا نشد.',
            409 => 'درخواست با وضعیت فعلی منبع سازگار نیست.', 422 => 'اطلاعات ارسال‌شده معتبر نیست.',
            429 => 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', 503 => 'سرویس موقتاً در دسترس نیست.',
            default => $status >= 500 ? 'خطای داخلی رخ داد. شناسه درخواست را برای پشتیبانی نگه دارید.' : 'درخواست انجام نشد.',
        };
    }
}
