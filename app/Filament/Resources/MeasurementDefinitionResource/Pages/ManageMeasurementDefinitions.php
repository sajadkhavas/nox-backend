<?php

namespace App\Filament\Resources\MeasurementDefinitionResource\Pages;

use App\Filament\Resources\MeasurementDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMeasurementDefinitions extends ManageRecords
{
    protected static string $resource = MeasurementDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
