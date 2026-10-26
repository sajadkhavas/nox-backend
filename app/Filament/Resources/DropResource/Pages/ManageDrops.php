<?php

namespace App\Filament\Resources\DropResource\Pages;

use App\Filament\Resources\DropResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDrops extends ManageRecords
{
    protected static string $resource = DropResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
