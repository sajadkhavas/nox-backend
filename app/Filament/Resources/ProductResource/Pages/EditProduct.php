<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Domain\Apparel\VariantMatrixService;
use App\Filament\Resources\ProductResource;
use App\Models\Color;
use App\Models\Size;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('variant_matrix')
                ->label('Variant Matrix')
                ->icon('heroicon-o-squares-plus')
                ->form([
                    Forms\Components\Select::make('color_ids')
                        ->label('رنگ‌ها')
                        ->options(Color::query()->active()->ordered()->pluck('name', 'id')->all())
                        ->multiple()
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('size_ids')
                        ->label('سایزها')
                        ->options(Size::query()->active()->ordered()->pluck('name', 'id')->all())
                        ->multiple()
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('sku_prefix')
                        ->label('SKU prefix')
                        ->required()
                        ->maxLength(60)
                        ->helperText('Prefix صریح ادمین؛ از نام Product به‌صورت پنهانی تولید نمی‌شود.'),
                    Forms\Components\TextInput::make('regular_price_toman')
                        ->label('قیمت اولیه')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('موجودی اولیه')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->default(0),
                ])
                ->action(function (array $data): void {
                    $result = app(VariantMatrixService::class)->apply(
                        $this->record,
                        $data['color_ids'],
                        $data['size_ids'],
                        $data['sku_prefix'],
                        (int) $data['regular_price_toman'],
                        (int) $data['stock_quantity'],
                    );

                    Notification::make()
                        ->title('Variant Matrix اعمال شد')
                        ->body(
                            "created={$result['created']}, existing={$result['existing']}, skipped_deleted={$result['skipped_deleted']}",
                        )
                        ->success()
                        ->send();

                    $this->refreshFormData(['variants']);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
