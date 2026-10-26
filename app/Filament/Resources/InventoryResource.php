<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\ProductVariant;
use App\Services\Commerce\InventoryLedgerService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class InventoryResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $slug = 'inventory';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'موجودی';

    protected static ?string $navigationGroup = 'عملیات فروش';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable(),
            Tables\Columns\TextColumn::make('product.name')->label('محصول')->searchable(),
            Tables\Columns\TextColumn::make('color.name')->label('رنگ'), Tables\Columns\TextColumn::make('size.name')->label('سایز'),
            Tables\Columns\TextColumn::make('stock_quantity')->label('On hand')->numeric(),
            Tables\Columns\TextColumn::make('reserved_quantity')->label('Reserved')->state(fn (ProductVariant $record): int => $record->reserved_quantity),
            Tables\Columns\TextColumn::make('available_quantity')->label('Available')->state(fn (ProductVariant $record): int => $record->available_quantity),
        ])->actions([
            Tables\Actions\Action::make('adjust')->label('اصلاح موجودی')->icon('heroicon-o-adjustments-horizontal')->requiresConfirmation()
                ->form([
                    Forms\Components\TextInput::make('delta')->label('Delta')->numeric()->step(1)->required()->rules(['integer', 'not_in:0']),
                    Forms\Components\Textarea::make('reason')->label('دلیل')->required()->minLength(3)->maxLength(255),
                ])->action(function (ProductVariant $record, array $data): void {
                    app(InventoryLedgerService::class)->adjust(
                        $record, (int) $data['delta'], (string) $data['reason'],
                        'admin-adjust:'.auth()->id().':'.$record->public_id.':'.Str::uuid(), 'admin', auth()->id(),
                    );
                }),
        ])->bulkActions([])->defaultSort('id');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInventory::route('/')];
    }
}
