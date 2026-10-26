<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class BackendReadiness extends Command
{
    protected $signature = 'backend:readiness {--json : Emit machine-readable JSON}';

    protected $description = 'Validate the frozen LBB backend contract and infrastructure readiness';

    public function handle(): int
    {
        $contractVersion = (string) config('lbb.api.contract_version');
        $freezeVersion = (string) config('lbb.contracts.backend_freeze.contract_version');

        $checks = [
            'contract_version' => $this->check(
                $contractVersion !== '' && $contractVersion === $freezeVersion,
                $contractVersion,
            ),
            'domain_cleanup' => $this->check(
                config('lbb.contracts.domain_cleanup.status') === 'ready',
                (string) config('lbb.contracts.domain_cleanup.status'),
            ),
            'apparel_domain' => $this->check(
                config('lbb.contracts.apparel_domain.status') === 'ready',
                (string) config('lbb.contracts.apparel_domain.status'),
            ),
            'catalog' => $this->check(
                config('lbb.contracts.catalog.status') === 'public-v1-ready',
                (string) config('lbb.contracts.catalog.status'),
            ),
            'authentication' => $this->check(
                config('lbb.contracts.authentication.status') === 'public-v1-ready',
                (string) config('lbb.contracts.authentication.status'),
            ),
            'commerce_operations' => $this->check(
                config('lbb.contracts.orders.status') === 'commerce-operations-ready',
                (string) config('lbb.contracts.orders.status'),
            ),
            'backend_freeze' => $this->check(
                config('lbb.contracts.backend_freeze.status') === 'ready',
                (string) config('lbb.contracts.backend_freeze.status'),
            ),
            'openapi' => $this->openApiCheck(),
            'database' => $this->databaseCheck(),
        ];

        $ready = collect($checks)->every(fn (array $check): bool => $check['ok']);
        $payload = [
            'ready' => $ready,
            'contractVersion' => $contractVersion,
            'checks' => $checks,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['Check', 'Status', 'Detail'],
                collect($checks)->map(fn (array $check, string $name): array => [
                    $name,
                    $check['ok'] ? 'ready' : 'failed',
                    $check['detail'],
                ])->values()->all(),
            );
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    private function openApiCheck(): array
    {
        try {
            $path = (string) config('lbb.api.openapi_path');
            $document = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
            $requiredPaths = [
                '/api/system/openapi',
                '/api/v1/auth/otp/request',
                '/api/v1/auth/otp/verify',
                '/api/v1/auth/me',
                '/api/v1/auth/logout',
                '/api/v1/products',
                '/api/v1/products/{slug}',
                '/api/v1/delivery/options',
                '/api/v1/cart/validate',
                '/api/v1/checkout/quote',
                '/api/v1/checkout/commit',
                '/api/v1/account/orders',
                '/api/v1/orders/{orderId}/payments',
                '/api/v1/payments/verify',
                '/api/v1/orders/{orderId}/returns',
                '/api/v1/orders/{orderId}/exchanges',
                '/api/v1/refunds',
            ];
            $paths = array_keys($document['paths'] ?? []);
            $valid = ($document['openapi'] ?? null) === '3.1.0'
                && ($document['info']['version'] ?? null) === config('lbb.api.contract_version')
                && collect($requiredPaths)->every(fn (string $required): bool => in_array($required, $paths, true));

            return $this->check($valid, $path);
        } catch (Throwable $exception) {
            return $this->check(false, $exception->getMessage());
        }
    }

    private function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->check(true, DB::connection()->getDriverName());
        } catch (Throwable $exception) {
            return $this->check(false, $exception->getMessage());
        }
    }

    private function check(bool $ok, string $detail): array
    {
        return ['ok' => $ok, 'detail' => $detail];
    }
}
