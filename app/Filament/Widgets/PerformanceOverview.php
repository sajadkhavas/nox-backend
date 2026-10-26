<?php

namespace App\Filament\Widgets;

use App\Models\PerformanceMetric;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PerformanceOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $avg = PerformanceMetric::query()
            ->selectRaw('AVG(lcp) as lcp, AVG(fid) as fid, AVG(cls) as cls, AVG(fcp) as fcp, AVG(ttfb) as ttfb, COUNT(*) as total')
            ->first();

        return [
            Stat::make('LCP میانگین', round($avg->lcp ?? 0, 0) . ' ms')
                ->description('Largest Contentful Paint')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color(($avg->lcp ?? 0) < 2500 ? 'success' : (($avg->lcp ?? 0) < 4000 ? 'warning' : 'danger')),

            Stat::make('FID میانگین', round($avg->fid ?? 0, 0) . ' ms')
                ->description('First Input Delay')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color(($avg->fid ?? 0) < 100 ? 'success' : (($avg->fid ?? 0) < 300 ? 'warning' : 'danger')),

            Stat::make('CLS میانگین', round($avg->cls ?? 0, 3))
                ->description('Cumulative Layout Shift')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color(($avg->cls ?? 0) < 0.1 ? 'success' : (($avg->cls ?? 0) < 0.25 ? 'warning' : 'danger')),

            Stat::make('TTFB میانگین', round($avg->ttfb ?? 0, 0) . ' ms')
                ->description('Time to First Byte')
                ->descriptionIcon('heroicon-m-clock')
                ->color(($avg->ttfb ?? 0) < 800 ? 'success' : 'warning'),

            Stat::make('تعداد اندازه‌گیری', number_format($avg->total ?? 0))
                ->description('کل داده‌های ثبت شده')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}
