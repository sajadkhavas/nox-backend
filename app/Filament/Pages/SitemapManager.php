<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;

class SitemapManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Sitemap';
    protected static ?string $title = 'مدیریت Sitemap';
    protected static ?string $navigationGroup = 'سیستم';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.sitemap-manager';

    public ?string $lastGenerated = null;
    public ?int $urlCount = null;
    public bool $fileExists = false;

    public function mount(): void
    {
        $this->loadStatus();
    }

    public function loadStatus(): void
    {
        $path = public_path('sitemap.xml');
        $this->fileExists = file_exists($path);

        if ($this->fileExists) {
            $this->lastGenerated = date('Y/m/d H:i:s', filemtime($path));
            $content = file_get_contents($path);
            $this->urlCount = substr_count($content, '<url>');
        }
    }

    public function generate(): void
    {
        Artisan::call('sitemap:generate');
        $this->loadStatus();
        Notification::make()
            ->title('Sitemap با موفقیت ساخته شد!')
            ->success()
            ->send();
    }
}
