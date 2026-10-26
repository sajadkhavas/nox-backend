<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LbbCommerceOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('محصولات', Product::query()->count())
                ->description('محصولات ثبت‌شده در فروشگاه')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('دسته‌ها', Category::query()->count())
                ->description('دسته‌های فروشگاه')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),

            Stat::make('مشتریان', Customer::query()->count())
                ->description('مشتریان ثبت‌شده')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('سفارش‌ها', Order::query()->count())
                ->description('کل سفارش‌های ثبت‌شده')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning'),
        ];
    }
}
