<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SiteHealthDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'سلامت سایت';
    protected static ?string $title = 'داشبورد سلامت سایت';
    protected static ?string $navigationGroup = 'سیستم';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.site-health-dashboard';

    public array $checks = [];

    public function mount(): void
    {
        $this->runChecks();
    }

    public function runChecks(): void
    {
        $this->checks = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'storage'  => $this->checkStorage(),
            'queue'    => $this->checkQueue(),
            'env'      => $this->checkEnv(),
            'disk'     => $this->checkDisk(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $tables = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()');
            return [
                'status' => 'ok',
                'label'  => 'دیتابیس',
                'value'  => $tables[0]->count . ' جدول',
                'icon'   => 'heroicon-o-circle-stack',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'label'  => 'دیتابیس',
                'value'  => 'خطا: ' . $e->getMessage(),
                'icon'   => 'heroicon-o-circle-stack',
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            $ok = Cache::get('health_check') === true;
            return [
                'status' => $ok ? 'ok' : 'error',
                'label'  => 'کش',
                'value'  => $ok ? 'فعال (' . config('cache.default') . ')' : 'غیرفعال',
                'icon'   => 'heroicon-o-bolt',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'label'  => 'کش',
                'value'  => 'خطا',
                'icon'   => 'heroicon-o-bolt',
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            Storage::put('health_check.txt', 'ok');
            Storage::delete('health_check.txt');
            return [
                'status' => 'ok',
                'label'  => 'استوریج',
                'value'  => 'قابل نوشتن',
                'icon'   => 'heroicon-o-folder',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'label'  => 'استوریج',
                'value'  => 'خطا در نوشتن',
                'icon'   => 'heroicon-o-folder',
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');
            return [
                'status' => 'ok',
                'label'  => 'صف',
                'value'  => 'درایور: ' . $driver,
                'icon'   => 'heroicon-o-queue-list',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'label'  => 'صف',
                'value'  => 'خطا',
                'icon'   => 'heroicon-o-queue-list',
            ];
        }
    }

    private function checkEnv(): array
    {
        $env = app()->environment();
        $debug = config('app.debug');
        $status = ($env === 'production' && !$debug) ? 'ok' : 'warning';
        return [
            'status' => $status,
            'label'  => 'محیط',
            'value'  => $env . ($debug ? ' (debug روشن!)' : ''),
            'icon'   => 'heroicon-o-cog-6-tooth',
        ];
    }

    private function checkDisk(): array
    {
        $free  = disk_free_space(base_path());
        $total = disk_total_space(base_path());
        $usedPercent = round((($total - $free) / $total) * 100);
        $status = $usedPercent > 90 ? 'error' : ($usedPercent > 75 ? 'warning' : 'ok');
        return [
            'status' => $status,
            'label'  => 'دیسک',
            'value'  => $usedPercent . '% استفاده شده (' . round($free / 1073741824, 1) . ' GB آزاد)',
            'icon'   => 'heroicon-o-server',
        ];
    }

    public function clearCache(): void
    {
        Artisan::call('optimize:clear');
        $this->runChecks();
        Notification::make()
            ->title('کش پاک شد!')
            ->success()
            ->send();
    }

    public function refreshChecks(): void
    {
        $this->runChecks();
        Notification::make()
            ->title('وضعیت بروز شد!')
            ->success()
            ->send();
    }
}
