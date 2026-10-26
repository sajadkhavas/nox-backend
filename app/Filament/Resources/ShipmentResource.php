<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationLabel = 'ارسال‌ها';

    protected static ?string $navigationGroup = 'عملیات فروش';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('order.order_number')->label('سفارش')->searchable(), Tables\Columns\TextColumn::make('method_snapshot')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('carrier'), Tables\Columns\TextColumn::make('tracking_reference')->copyable(),
            Tables\Columns\TextColumn::make('shipped_at')->dateTime(), Tables\Columns\TextColumn::make('delivered_at')->dateTime(),
        ])->actions([])->bulkActions([])->defaultSort('id', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListShipments::route('/')];
    }
}
