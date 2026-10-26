<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StorefrontMediaAssetResource\Pages;
use App\Models\StorefrontMediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class StorefrontMediaAssetResource extends Resource
{
    protected static ?string $model = StorefrontMediaAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'کتابخانه تصاویر';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('original_image')
                ->label('فایل اصلی')
                ->content(fn (?StorefrontMediaAsset $record): string => $record?->originalUrl() ?? 'فایل ندارد؛ برای ثبت تصویر، از «بارگذاری تصویر» استفاده کنید.')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('title')->label('عنوان داخلی')->required()->maxLength(220),
            Forms\Components\TextInput::make('alt_text')->label('متن جایگزین')->maxLength(500),
            Forms\Components\Select::make('usage')->label('کاربرد')
                ->options([
                    'unassigned' => 'تخصیص‌نیافته', 'product' => 'محصول',
                    'category' => 'دسته', 'article' => 'مقاله', 'gallery' => 'گالری',
                    'page' => 'صفحه', 'hero' => 'بنر', 'brand' => 'برند',
                ])->default('unassigned')->required(),
            Forms\Components\Placeholder::make('processing_info')
                ->label('وضعیت پردازش')
                ->content('پس از آپلود، نسخه WebP ساخته و در صورت آماده بودن خودکار تأیید می‌شود.'),
            Forms\Components\Textarea::make('notes')->label('یادداشت')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('preview')->label('تصویر')
                ->getStateUsing(fn (StorefrontMediaAsset $record): ?string => $record->previewUrl())->square(),
            Tables\Columns\TextColumn::make('title')->label('عنوان')->searchable(),
            Tables\Columns\TextColumn::make('conversion_state')->label('نسخه مصرفی')
                ->state(fn (StorefrontMediaAsset $record): string => $record->conversionState())
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'ready' => 'WebP آماده ≤ 1MB', 'oversized' => 'بیشتر از 1MB',
                    'missing' => 'فایل اصلی ندارد', 'broken' => 'فایل/Storage ناسالم',
                    default => 'در انتظار پردازش',
                })
                ->color(fn (string $state): string => match ($state) {
                    'ready' => 'success', 'pending' => 'warning', default => 'danger',
                })->badge(),
            Tables\Columns\TextColumn::make('original_size')->label('حجم Original')
                ->state(fn (StorefrontMediaAsset $record): string => $record->originalSizeLabel()),
            Tables\Columns\TextColumn::make('dimensions')->label('ابعاد Original')
                ->state(fn (StorefrontMediaAsset $record): string => $record->dimensionsLabel())->toggleable(),
            Tables\Columns\TextColumn::make('format')->label('فرمت Original')
                ->state(fn (StorefrontMediaAsset $record): string => $record->formatLabel())->toggleable(),
            Tables\Columns\TextColumn::make('original_url')->label('URL اصلی')
                ->state(fn (StorefrontMediaAsset $record): ?string => $record->originalUrl())
                ->copyable()->limit(18)->placeholder('—'),
            Tables\Columns\TextColumn::make('optimized_url')->label('URL بهینه')
                ->state(fn (StorefrontMediaAsset $record): ?string => $record->optimizedUrl())
                ->copyable()->limit(18)->placeholder('در انتظار پردازش'),
            Tables\Columns\TextColumn::make('usage')->label('کاربرد')->badge(),
            Tables\Columns\TextColumn::make('status')->label('وضعیت')->badge(),
            Tables\Columns\TextColumn::make('preview_size')->label('حجم نسخه بهینه')
                ->state(function (StorefrontMediaAsset $record): string {
                    $media = $record->sourceMedia();
                    if (! $media || ! $media->hasGeneratedConversion('preview')) {
                        return 'در انتظار پردازش';
                    }
                    try {
                        $path = $media->getPath('preview');
                        return is_file($path) ? number_format(filesize($path) / 1024).' KB' : 'فایل پیدا نشد';
                    } catch (Throwable) {
                        return 'فایل قابل خواندن نیست';
                    }
                }),
            Tables\Columns\TextColumn::make('updated_at')->label('آخرین ویرایش')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('retryPreview')
                ->label('بازسازی تصویر بهینه')
                ->icon('heroicon-o-arrow-path')->color('warning')
                ->visible(fn (StorefrontMediaAsset $record): bool => $record->sourceMedia() !== null && ! $record->isReady())
                ->requiresConfirmation()
                ->modalDescription('فقط نسخه‌های thumb و preview بازسازی می‌شوند؛ فایل اصلی حفظ می‌شود.')
                ->action(function (StorefrontMediaAsset $record): void {
                    $media = $record->sourceMedia();
                    if (! $media) return;
                    try {
                        Artisan::call('media-library:regenerate', [
                            '--ids' => [(string) $media->getKey()],
                            '--only' => ['thumb', 'preview'],
                            '--force' => true,
                        ]);
                        $ready = $record->markReadyAfterUpload();
                        $notification = Notification::make()
                            ->title($ready ? 'تصویر بهینه آماده و تأیید شد.' : 'نسخه WebP هنوز آماده یا زیر ۱ مگابایت نیست.');
                        if ($ready) {
                            $notification->success();
                        } else {
                            $notification->warning();
                        }
                        $notification->send();
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()->danger()->title('بازسازی تصویر انجام نشد؛ فایل اصلی حفظ شد.')->send();
                    }
                }),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageStorefrontMediaAssets::route('/')];
    }
}
