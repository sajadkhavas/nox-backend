<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('disable')
                ->label('غیرفعال‌کردن حساب')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => (bool) $this->record->is_active)
                ->action(function (): void {
                    $this->record->update(['is_active' => false]);
                    $this->refreshFormData(['is_active']);
                }),
            Actions\Action::make('enable')
                ->label('فعال‌کردن حساب')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! $this->record->is_active)
                ->action(function (): void {
                    $this->record->update(['is_active' => true]);
                    $this->refreshFormData(['is_active']);
                }),
        ];
    }
}
