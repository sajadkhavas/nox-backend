<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;

class CacheManagerPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Cache Manager';
    protected static ?string $navigationGroup = 'سیستم';
    protected static ?int    $navigationSort  = 4;
    protected static ?string $title           = 'مدیریت Cache';
    protected static string  $view            = 'filament.pages.cache-manager';

    public array $results = [];

    public function clearAll(): void
    {
        Artisan::call('optimize:clear');
        $this->results[] = ['type' => 'success', 'msg' => 'همه cache ها پاک شد', 'time' => now()->format('H:i:s')];
        Notification::make()->title('همه Cache ها پاک شد')->success()->send();
    }

    public function clearCache(): void
    {
        Artisan::call('cache:clear');
        $this->results[] = ['type' => 'success', 'msg' => 'Application Cache پاک شد', 'time' => now()->format('H:i:s')];
        Notification::make()->title('Cache پاک شد')->success()->send();
    }

    public function clearConfig(): void
    {
        Artisan::call('config:clear');
        $this->results[] = ['type' => 'success', 'msg' => 'Config Cache پاک شد', 'time' => now()->format('H:i:s')];
        Notification::make()->title('Config Cache پاک شد')->success()->send();
    }

    public function clearRoutes(): void
    {
        Artisan::call('route:clear');
        $this->results[] = ['type' => 'success', 'msg' => 'Route Cache پاک شد', 'time' => now()->format('H:i:s')];
        Notification::make()->title('Route Cache پاک شد')->success()->send();
    }

    public function clearViews(): void
    {
        Artisan::call('view:clear');
        $this->results[] = ['type' => 'success', 'msg' => 'View Cache پاک شد', 'time' => now()->format('H:i:s')];
        Notification::make()->title('View Cache پاک شد')->success()->send();
    }

    public function clearEvents(): void
    {
        Artisan::call('event:clear');
        $this->results[] = ['type' => 'success', 'msg' => 'Event Cache پاک شد', 'time' => now()->format('H:i:s')];
        Notification::make()->title('Event Cache پاک شد')->success()->send();
    }

    public function cacheConfig(): void
    {
        Artisan::call('config:cache');
        Notification::make()->title('Config Cache ساخته شد')->success()->send();
        $this->redirect(request()->header('Referer') ?? static::getUrl());
    }

    public function cacheRoutes(): void
    {
        Artisan::call('route:cache');
        Notification::make()->title('Route Cache ساخته شد')->success()->send();
        $this->redirect(request()->header('Referer') ?? static::getUrl());
    }

    public function cacheViews(): void
    {
        Artisan::call('view:cache');
        $this->results[] = ['type' => 'info', 'msg' => 'View Cache ساخته شد', 'time' => now()->format('H:i:s')];
        Notification::make()->title('View Cache ساخته شد')->success()->send();
    }

    public function clearResults(): void
    {
        $this->results = [];
    }

    public function getStats(): array
    {
        return [
            'php'        => PHP_VERSION,
            'laravel'    => app()->version(),
            'env'        => app()->environment(),
            'cache'      => config('cache.default'),
            'disk_free'  => round(disk_free_space(base_path()) / 1024 / 1024 / 1024, 2) . ' GB',
            'disk_total' => round(disk_total_space(base_path()) / 1024 / 1024 / 1024, 2) . ' GB',
            'memory'     => round(memory_get_usage(true) / 1024 / 1024, 1) . ' MB',
        ];
    }

    public function getViewData(): array
    {
        return ['stats' => $this->getStats()];
    }
}
