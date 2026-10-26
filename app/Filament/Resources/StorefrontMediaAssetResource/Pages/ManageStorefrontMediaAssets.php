<?php

namespace App\Filament\Resources\StorefrontMediaAssetResource\Pages;

use App\Filament\Resources\StorefrontMediaAssetResource;
use App\Models\StorefrontMediaAsset;
use App\Support\AdminImageUpload;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ManageStorefrontMediaAssets extends ManageRecords
{
    protected static string $resource = StorefrontMediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\Action::make('bulkUpload')->label('آپلود گروهی تصاویر')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                Forms\Components\FileUpload::make('files')->label('تصاویر')->multiple()->image()
                    ->storeFiles(false)->acceptedFileTypes(AdminImageUpload::acceptedMimeTypes())
                    ->maxSize(AdminImageUpload::maxKilobytes())->maxFiles(50)
                    ->rules(AdminImageUpload::dimensionRules())->required(),
                Forms\Components\Select::make('usage')->label('کاربرد اولیه')
                    ->options(['unassigned' => 'تخصیص‌نیافته', 'hero' => 'بنر',
                        'brand' => 'برند', 'category' => 'دسته', 'product' => 'محصول',
                        'article' => 'مقاله', 'gallery' => 'گالری', 'page' => 'صفحه'])
                    ->default('unassigned')->required(),
                Forms\Components\Textarea::make('notes')->label('یادداشت مشترک')->rows(2),
            ])
            ->action(function (array $data): void {
                $ready = 0;
                $pending = 0;
                foreach ($data['files'] ?? [] as $file) {
                    if (! $file instanceof TemporaryUploadedFile) continue;
                    $title = Str::of(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                        ->replace(['_', '-'], ' ')->squish()->limit(220, '')->toString();
                    try {
                        $asset = StorefrontMediaAsset::createFromUpload($file, [
                            'title' => $title ?: 'تصویر LBB', 'usage' => $data['usage'],
                            'notes' => $data['notes'] ?? null,
                        ]);
                        $asset->status === 'ready' ? $ready++ : $pending++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $pending++;
                    }
                }
                Notification::make()->title("{$ready} تصویر آماده؛ {$pending} تصویر نیازمند بررسی")
                    ->body('تصاویر آماده بدون تأیید دستی قابل استفاده‌اند.')->send();
            }),
            Actions\Action::make('uploadImage')->label('بارگذاری تصویر')
            ->icon('heroicon-o-photo')->form([
                Forms\Components\FileUpload::make('source_image')
                    ->label('فایل اصلی')->image()->imageEditor()->storeFiles(false)
                    ->acceptedFileTypes(AdminImageUpload::acceptedMimeTypes())
                    ->maxSize(AdminImageUpload::maxKilobytes())
                    ->rules(AdminImageUpload::dimensionRules())->required(),
                Forms\Components\TextInput::make('title')->label('عنوان داخلی')
                    ->required()->maxLength(220),
                Forms\Components\TextInput::make('alt_text')->label('متن جایگزین')->maxLength(500),
                Forms\Components\Select::make('usage')->label('کاربرد')
                    ->options(['unassigned' => 'تخصیص‌نیافته', 'product' => 'محصول',
                        'category' => 'دسته', 'article' => 'مقاله', 'gallery' => 'گالری',
                        'page' => 'صفحه', 'hero' => 'بنر', 'brand' => 'برند'])
                    ->default('unassigned')->required(),
                Forms\Components\Textarea::make('notes')->label('یادداشت'),
            ])
            ->action(function (array $data): void {
                $file = $data['source_image'] ?? null;
                if (! $file instanceof TemporaryUploadedFile) {
                    Notification::make()->danger()->title('فایل تصویر دریافت نشد.')->send();

                    return;
                }

                try {
                    $record = StorefrontMediaAsset::createFromUpload($file, [
                        'title' => $data['title'],
                        'alt_text' => $data['alt_text'] ?? null,
                        'usage' => $data['usage'],
                        'notes' => $data['notes'] ?? null,
                    ]);
                } catch (Throwable $exception) {
                    report($exception);
                    Notification::make()->danger()->title('بارگذاری تصویر انجام نشد.')
                        ->body('فایل اصلی به کتابخانه متصل نشد؛ دوباره تلاش کنید.')->send();

                    return;
                }

                if ($record->status === 'ready') {
                    Notification::make()->success()->title('نسخه WebP آماده شد؛ تصویر خودکار تأیید شد.')->send();
                } else {
                    Notification::make()->warning()->title('تصویر ذخیره شد، اما نسخه بهینه هنوز آماده نیست.')
                        ->body('فایل اصلی حفظ شد. پس از بررسی وضعیت پردازش، از «بازسازی تصویر بهینه» استفاده کنید.')
                        ->send();
                }
            })];
    }
}
