<?php

namespace App\Filament\Resources\InventoryLedgerEntryResource\Pages;

use App\Filament\Resources\InventoryLedgerEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListInventoryLedgerEntries extends ListRecords
{
    protected static string $resource = InventoryLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
