<?php

use App\Models\OtpChallenge;
use Illuminate\Support\Facades\Schedule;

Schedule::command('sitemap:generate')->dailyAt('03:00');
Schedule::command('cache:prune-stale-tags')->hourly();
Schedule::command('queue:prune-batches')->daily();
Schedule::command('inventory:release-expired')
    ->everyMinute()->withoutOverlapping()->name('release-expired-inventory-reservations');
Schedule::command('commerce:expire-reservations')
    ->everyMinute()->withoutOverlapping()->name('expire-commerce-reservations');
Schedule::command('notifications:dispatch --limit=100')
    ->everyMinute()->withoutOverlapping()->onOneServer()->name('dispatch-lbb-notification-outbox');
Schedule::call(function (): void {
    OtpChallenge::query()->where(function ($query): void {
        $query->whereNotNull('consumed_at')->orWhere('expires_at', '<', now()->subDay());
    })->delete();
})->hourly()->name('prune-otp-challenges')->withoutOverlapping();
