<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueMonitorPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Queue Monitor';
    protected static ?string $navigationGroup = 'سیستم';
    protected static ?int    $navigationSort  = 5;
    protected static ?string $title           = 'مانیتور Queue';
    protected static string  $view            = 'filament.pages.queue-monitor';

    public function getStats(): array
    {
        $failed = 0;
        $pending = 0;

        try {
            $failed = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {}

        try {
            $pending = DB::table('jobs')->count();
        } catch (\Exception $e) {}

        return [
            'driver'  => config('queue.default'),
            'pending' => $pending,
            'failed'  => $failed,
        ];
    }

    public function getFailedJobs(): array
    {
        try {
            return DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(20)
                ->get()
                ->map(fn ($job) => [
                    'id'         => $job->id,
                    'connection' => $job->connection,
                    'queue'      => $job->queue,
                    'payload'    => json_decode($job->payload, true)['displayName'] ?? 'Unknown',
                    'exception'  => substr($job->exception, 0, 100) . '...',
                    'failed_at'  => $job->failed_at,
                ])
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function retryAll(): void
    {
        Artisan::call('queue:retry all');
        Notification::make()->title('همه job های failed مجدداً اجرا شدند')->success()->send();
    }

    public function flushFailed(): void
    {
        Artisan::call('queue:flush');
        Notification::make()->title('همه failed jobs پاک شدند')->success()->send();
    }

    public function getViewData(): array
    {
        return [
            'stats'      => $this->getStats(),
            'failedJobs' => $this->getFailedJobs(),
        ];
    }
}
