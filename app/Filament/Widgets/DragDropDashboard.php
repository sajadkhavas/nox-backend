<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Product;
use App\Models\BlogPost;
use App\Models\NewsletterSubscriber;
use App\Models\AbTest;
use App\Models\Coupon;
use App\Models\Review;
use App\Models\ShortUrl;

class DragDropDashboard extends Widget
{
    protected static string $view = 'filament.widgets.drag-drop-dashboard';
    protected static ?int $sort = -1;
    protected int | string | array $columnSpan = 'full';

    public array $widgetOrder = [];

    public function mount(): void
    {
        $this->widgetOrder = session('dashboard_widget_order', [
            'stats', 'reviews', 'newsletter', 'abtests', 'coupons', 'shortlinks'
        ]);
    }

    public function saveOrder(array $order): void
    {
        $this->widgetOrder = $order;
        session(['dashboard_widget_order' => $order]);
    }

    public function getStats(): array
    {
        return [
            ['label' => 'محصولات فعال',       'value' => Product::count(),                    'icon' => '📦', 'color' => 'blue'],
            ['label' => 'مقالات منتشر شده',   'value' => BlogPost::where('status','published')->count(), 'icon' => '📝', 'color' => 'green'],
            ['label' => 'مشترکین خبرنامه',    'value' => NewsletterSubscriber::count(), 'icon' => '📧', 'color' => 'purple'],
            ['label' => 'تست‌های A/B فعال',   'value' => AbTest::where('status','running')->count(), 'icon' => '🧪', 'color' => 'orange'],
            ['label' => 'کوپن‌های فعال',      'value' => Coupon::where('is_active',true)->count(), 'icon' => '🎫', 'color' => 'red'],
            ['label' => 'نظرات در انتظار',    'value' => Review::where('status','pending')->count(), 'icon' => '⭐', 'color' => 'yellow'],
        ];
    }

    public function getRecentReviews(): array
    {
        return Review::latest()->limit(5)->get()->map(fn($r) => [
            'name'   => $r->reviewer_name,
            'rating' => $r->rating,
            'title'  => $r->title ?? '-',
            'status' => $r->status,
        ])->toArray();
    }

    public function getRecentSubscribers(): array
    {
        return NewsletterSubscriber::latest()->limit(5)->get()->map(fn($s) => [
            'email'  => $s->email,
            'status' => $s->status,
            'date'   => $s->created_at->diffForHumans(),
        ])->toArray();
    }
}
