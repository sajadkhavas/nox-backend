<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'امنیت';
    protected static ?string $navigationLabel = '2FA Manager';
    protected static ?string $title = 'احراز هویت دو مرحله‌ای';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.two-factor';

    public bool $showQr = false;
    public bool $showRecoveryCodes = false;

    public function enableAction(): Action
    {
        return Action::make('enable')
            ->label('فعال کردن 2FA')
            ->icon('heroicon-o-shield-check')
            ->color('success')
            ->requiresConfirmation()
            ->action(function () {
                app(EnableTwoFactorAuthentication::class)(auth()->user());
                $this->showQr = true;
                Notification::make()
                    ->success()
                    ->title('2FA فعال شد')
                    ->body('لطفاً QR Code را با اپلیکیشن Google Authenticator اسکن کنید')
                    ->send();
            });
    }

    public function disableAction(): Action
    {
        return Action::make('disable')
            ->label('غیرفعال کردن 2FA')
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function () {
                app(DisableTwoFactorAuthentication::class)(auth()->user());
                $this->showQr = false;
                Notification::make()
                    ->warning()
                    ->title('2FA غیرفعال شد')
                    ->send();
            });
    }

    public function regenerateCodesAction(): Action
    {
        return Action::make('regenerate')
            ->label('تولید کدهای بازیابی جدید')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->action(function () {
                app(GenerateNewRecoveryCodes::class)(auth()->user());
                $this->showRecoveryCodes = true;
                Notification::make()
                    ->success()
                    ->title('کدهای بازیابی جدید تولید شدند')
                    ->send();
            });
    }

    public function getUser()
    {
        return auth()->user();
    }
}
