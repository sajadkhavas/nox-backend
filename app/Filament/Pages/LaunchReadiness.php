<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class LaunchReadiness extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'آمادگی تحویل';

    protected static ?string $title = 'Launch Readiness — وضعیت تحویل';

    protected static ?string $navigationGroup = 'سیستم';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.launch-readiness';

    public array $checks = [];

    public function mount(): void
    {
        $this->refreshChecks();
    }

    public function refreshChecks(): void
    {
        $requiredSettings = [
            'brand.identity',
            'brand.copy',
            'contact.public',
            'home.presentation',
            'seo.defaults',
        ];
        $requiredPages = ['about', 'contact', 'terms', 'privacy'];

        $publishedProducts = DB::table('products')
            ->whereNull('deleted_at')
            ->where('publication_status', 'published')
            ->where('is_active', true)
            ->count();
        $publishedCategories = DB::table('categories')
            ->whereNull('deleted_at')
            ->where('publication_status', 'published')
            ->where('is_active', true)
            ->count();
        $settingsCount = DB::table('store_settings')
            ->where('is_public', true)
            ->whereIn('key', $requiredSettings)
            ->distinct()
            ->count('key');
        $pageCount = DB::table('content_pages')
            ->where('publication_status', 'published')
            ->whereIn('slug', $requiredPages)
            ->distinct()
            ->count('slug');
        $faqCount = DB::table('faqs')->where('is_active', true)->count();
        $deliveryCount = DB::table('delivery_zones')->where('is_active', true)->count();

        $this->checks = [
            $this->check('catalog', 'کاتالوگ واقعی منتشرشده', $publishedProducts > 0, "{$publishedProducts} محصول فعال/منتشرشده", 'قبل از تحویل باید محصولات واقعی از Admin ثبت شوند.'),
            $this->check('categories', 'دسته‌های فعال', $publishedCategories > 0, "{$publishedCategories} دسته فعال/منتشرشده", 'فقط دسته‌های دارای محصول واقعی فعال شوند.'),
            $this->check('settings', 'Storefront Settings', $settingsCount === count($requiredSettings), "{$settingsCount}/".count($requiredSettings).' تنظیم کلیدی', 'Brand/Contact/Home/SEO باید از StoreSetting عمومی تأمین شوند.'),
            $this->check('legal', 'صفحات ضروری', $pageCount === count($requiredPages), "{$pageCount}/".count($requiredPages).' صفحه', 'about/contact/terms/privacy باید از Admin منتشر شوند.'),
            $this->check('faq', 'FAQ', $faqCount > 0, "{$faqCount} پرسش فعال", 'FAQ خالی در Frontend به‌صورت fail-closed noindex باقی می‌ماند.'),
            $this->check('delivery', 'روش‌های ارسال', $deliveryCount > 0, "{$deliveryCount} محدوده/روش فعال", 'ارسال باید قبل از Checkout نهایی دوباره با داده واقعی تطبیق داده شود.'),
            [
                'key' => 'checkout',
                'label' => 'Checkout',
                'status' => config('lbb.checkout.enabled', false) ? 'warning' : 'ok',
                'value' => config('lbb.checkout.enabled', false) ? 'فعال' : 'خاموش — حالت امن قبل از فعال‌سازی',
                'note' => 'فعال‌سازی Checkout فقط در Gate کنترل‌شده بعد از ورود داده واقعی انجام می‌شود.',
            ],
            [
                'key' => 'payment',
                'label' => 'Payment',
                'status' => config('lbb.payment.enabled', false) ? 'warning' : 'ok',
                'value' => config('lbb.payment.enabled', false) ? 'فعال' : 'خاموش — حالت امن قبل از درگاه واقعی',
                'note' => 'Zarinpal و credential واقعی خارج از این Final Technical Pass فعال می‌شوند.',
            ],
        ];
    }

    private function check(string $key, string $label, bool $ok, string $value, string $note): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $ok ? 'ok' : 'warning',
            'value' => $value,
            'note' => $note,
        ];
    }
}
