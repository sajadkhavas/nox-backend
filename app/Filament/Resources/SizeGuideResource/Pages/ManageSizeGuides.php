<?php

namespace App\Filament\Resources\SizeGuideResource\Pages;

use App\Filament\Resources\SizeGuideResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSizeGuides extends ManageRecords
{
    protected static string $resource = SizeGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
