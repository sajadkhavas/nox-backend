<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryReservationResource\Pages;
use App\Models\InventoryReservation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryReservationResource extends Resource
{
    protected static ?string $model = InventoryReservation::class;

    protected static ?string $navigationLabel = 'رزروهای موجودی';

    protected static ?string $navigationGroup = 'عملیات فروش';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('public_id')->label('ID')->copyable(), Tables\Columns\TextColumn::make('order.order_number')->label('سفارش'),
            Tables\Columns\TextColumn::make('variant.sku')->label('SKU'), Tables\Columns\TextColumn::make('quantity')->numeric(),
            Tables\Columns\TextColumn::make('purpose')->badge(), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('expires_at')->dateTime(),
        ])->actions([])->bulkActions([])->defaultSort('id', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInventoryReservations::route('/')];
    }
}
