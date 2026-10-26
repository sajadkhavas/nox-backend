<?php

namespace App\Filament\Resources\CustomerAddressResource\Pages;

use App\Filament\Resources\CustomerAddressResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomerAddresses extends ManageRecords
{
    protected static string $resource = CustomerAddressResource::class;
}
