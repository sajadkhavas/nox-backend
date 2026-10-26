<?php

namespace App\Filament\Resources\ExchangeRequestResource\Pages;

use App\Filament\Resources\ExchangeRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListExchangeRequests extends ListRecords
{
    protected static string $resource = ExchangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
