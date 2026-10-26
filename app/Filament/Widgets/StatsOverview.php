<?php
namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Models\Product;
use App\Models\RfqRequest;
use App\Models\BlogPost;
use Filament\Widgets\StatsOverviewWidget as Base;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends Base
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        $rfqToday = RfqRequest::whereDate('created_at', today())->count();
        $rfqThisWeek = RfqRequest::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $rfqLastWeek = RfqRequest::whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
        $rfqTrend = $rfqLastWeek > 0 ? round((($rfqThisWeek - $rfqLastWeek) / $rfqLastWeek) * 100) : 0;

        // Chart data for last 7 days
        $rfqChart = collect(range(6, 0))->map(fn($d) =>
            RfqRequest::whereDate('created_at', now()->subDays($d))->count()
        )->toArray();

        $contactChart = collect(range(6, 0))->map(fn($d) =>
            Contact::whereDate('created_at', now()->subDays($d))->count()
        )->toArray();

        return [
            Stat::make('استعلام‌های در انتظار', RfqRequest::where('status', 'pending')->count())
                ->description('امروز: ' . $rfqToday . ' | این هفته: ' . $rfqThisWeek)
                ->descriptionIcon('heroicon-m-clock')
                ->chart($rfqChart)
                ->color('warning'),

            Stat::make('پیام‌های خوانده نشده', Contact::where('status', 'unread')->count())
                ->description('کل پیام‌ها: ' . Contact::count())
                ->descriptionIcon('heroicon-m-envelope')
                ->chart($contactChart)
                ->color('danger'),

            Stat::make('محصولات فعال', Product::where('status', 'published')->count())
                ->description('کل محصولات: ' . Product::count())
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),

            Stat::make('مقالات منتشر شده', BlogPost::where('status', 'published')->count())
                ->description('کل بازدید محصولات: ' . number_format((int) Product::sum('view_count')))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
