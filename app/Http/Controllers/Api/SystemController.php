<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JsonException;
use Throwable;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'ok',
            'service' => 'lbb-backend',
            'time' => now()->toIso8601String(),
        ]);
    }

    public function ready(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            return ApiResponse::success([
                'status' => 'ready',
                'checks' => [
                    'application' => 'ok',
                    'database' => 'ok',
                ],
                'time' => now()->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error(
                'سرویس هنوز آماده دریافت درخواست‌های وابسته به دیتابیس نیست.',
                503,
                [],
                ['checks' => ['application' => 'ok', 'database' => 'failed']],
            );
        }
    }

    public function meta(): JsonResponse
    {
        return ApiResponse::success([
            'service' => 'lbb-backend',
            'brand' => [
                'name' => config('lbb.brand.name'),
                'nameEn' => config('lbb.brand.name_en'),
            ],
            'apiVersion' => (string) config('lbb.api.version'),
            'contractVersion' => (string) config('lbb.api.contract_version'),
            'framework' => [
                'name' => 'Laravel',
                'version' => app()->version(),
            ],
            'backendComplete' => (bool) config('lbb.launch.backend_complete', false),
            'openApiUrl' => '/api/system/openapi',
        ]);
    }

    public function contracts(): JsonResponse
    {
        return ApiResponse::success([
            'contractVersion' => (string) config('lbb.api.contract_version'),
            'contracts' => config('lbb.contracts', []),
            'launch' => config('lbb.launch', []),
            'policies' => config('lbb.policies', []),
            'notes' => [
                'قرارداد نسخه‌دار /api/v1 در F14-BE-F منجمد شده است.',
                'قیمت، موجودی، سفارش و وضعیت پرداخت در بک‌اند authoritative هستند.',
                'پرداخت، بازپرداخت و پیامک بدون Provider و تنظیمات واقعی fail-closed باقی می‌مانند.',
                'frontend integration و production deployment هنوز در این وضعیت ادعا نمی‌شوند.',
            ],
        ]);
    }

    /** @throws JsonException */
    public function openapi(): JsonResponse
    {
        $path = (string) config('lbb.api.openapi_path', base_path('docs/openapi.json'));
        $document = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $etag = '"'.hash_file('sha256', $path).'"';

        return response()
            ->json($document)
            ->header('Cache-Control', 'public, max-age=300')
            ->header('ETag', $etag);
    }
}
