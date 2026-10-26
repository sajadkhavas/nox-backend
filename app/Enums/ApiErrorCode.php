<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    case BadRequest = 'bad_request';
    case AuthenticationRequired = 'authentication_required';
    case AccessDenied = 'access_denied';
    case ResourceNotFound = 'resource_not_found';
    case Conflict = 'conflict';
    case ValidationFailed = 'validation_failed';
    case RateLimited = 'rate_limited';
    case ServiceUnavailable = 'service_unavailable';
    case InternalError = 'internal_error';
    case LegacyApiDisabled = 'legacy_api_disabled';
    case RequestFailed = 'request_failed';

    public static function forStatus(int $status): self
    {
        return match ($status) {
            400 => self::BadRequest,
            401 => self::AuthenticationRequired,
            403 => self::AccessDenied,
            404 => self::ResourceNotFound,
            409 => self::Conflict,
            422 => self::ValidationFailed,
            429 => self::RateLimited,
            503 => self::ServiceUnavailable,
            default => $status >= 500 ? self::InternalError : self::RequestFailed,
        };
    }
}
