<?php

namespace App\Filament\Resources\ProductMediaAssetResource\Pages;

use App\Filament\Resources\ProductMediaAssetResource;
use App\Models\Product;
use App\Models\ProductMediaAsset;
use App\Models\StorefrontMediaAsset;
use App\Support\StorefrontMediaOptions;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageProductMediaAssets extends ManageRecords
{
    protected static string $resource = ProductMediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('attachLibraryImage')
                ->label('انتخاب تصویر از کتابخانه')
                ->form([
                    Forms\Components\Select::make('product_id')->label('محصول')->required()
                        ->options(fn (): array => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    Forms\Components\Select::make('source_id')->label('تصویر آماده')->required()
                        ->options(fn (): array => StorefrontMediaOptions::ids())->searchable(),
                    Forms\Components\Select::make('role')->label('نقش تصویر')->required()
                        ->options(['front' => 'جلو', 'back' => 'پشت', 'detail' => 'جزئیات', 'lifestyle' => 'استایل'])
                        ->default('detail'),
                    Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->minValue(0)->default(0),
                ])
                ->action(function (array $data): void {
                    $source = StorefrontMediaAsset::query()->where('status', 'ready')->findOrFail($data['source_id']);
                    if (! $source->isReady() || ! $source->previewPath()) {
                        throw new \DomainException('نسخه بهینه‌شده تصویر آماده نیست.');
                    }

                    $asset = ProductMediaAsset::query()->create([
                        'product_id' => $data['product_id'],
                        'role' => $data['role'],
                        'sort_order' => $data['sort_order'] ?? 0,
                        'alt_text' => $source->alt_text ?: $source->title,
                        'verification_state' => 'missing',
                    ]);

                    try {
                        $asset->addMediaFromDisk($source->previewPath(), 'public')
                            ->usingFileName('lbb-media-'.$source->getKey().'.webp')
                            ->toMediaCollection('asset', 'public');
                    } catch (\Throwable $exception) {
                        $asset->forceDelete();
                        throw $exception;
                    }

                    Notification::make()->success()->title('تصویر به محصول اضافه شد؛ پیش از انتشار تأییدش کنید.')->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
