<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

/**
 * Legacy source editor intentionally disabled.
 * Production releases are immutable and must only be changed through Git + CI + deployment.
 */
class FileManagerPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationLabel = 'ویرایش فایل‌ها (غیرفعال)';

    protected static ?string $navigationGroup = 'سیستم';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'ویرایش فایل‌ها غیرفعال است';

    protected static string $view = 'filament.pages.file-manager';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return false;
    }

    public function mount(): void
    {
        abort(404);
    }
}
