<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryLedgerEntryResource\Pages;
use App\Models\InventoryLedgerEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryLedgerEntryResource extends Resource
{
    protected static ?string $model = InventoryLedgerEntry::class;

    protected static ?string $navigationLabel = 'دفتر موجودی';

    protected static ?string $navigationGroup = 'عملیات فروش';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(), Tables\Columns\TextColumn::make('variant.sku')->label('SKU')->searchable(),
            Tables\Columns\TextColumn::make('event_type')->badge(), Tables\Columns\TextColumn::make('on_hand_delta')->label('On-hand Δ'),
            Tables\Columns\TextColumn::make('reserved_delta')->label('Reserved Δ'), Tables\Columns\TextColumn::make('on_hand_after')->label('On-hand'),
            Tables\Columns\TextColumn::make('reserved_after')->label('Reserved'), Tables\Columns\TextColumn::make('available_after')->label('Available'),
            Tables\Columns\TextColumn::make('order.order_number')->label('Order'), Tables\Columns\TextColumn::make('reason')->limit(40),
        ])->actions([])->bulkActions([])->defaultSort('id', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInventoryLedgerEntries::route('/')];
    }
}
