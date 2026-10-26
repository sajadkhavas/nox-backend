<?php

namespace App\Filament\Actions;

use App\Support\StorefrontMediaOptions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use FilamentTiptapEditor\TiptapEditor;

final class LbbTiptapMediaAction
{
    public static function make(): Action
    {
        return Action::make('filament_tiptap_media')
            ->modalHeading('افزودن تصویر از کتابخانه رسانه')
            ->modalDescription('فقط نسخه‌های WebP آماده و داخل سقف ۱MB کتابخانه LBB قابل انتخاب‌اند. برای آپلود تصویر جدید ابتدا از کتابخانه رسانه استفاده کنید.')
            ->form([
                Select::make('src')
                    ->label('تصویر')
                    ->options(fn (): array => StorefrontMediaOptions::urls())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('alt')
                    ->label('متن جایگزین (Alt)')
                    ->maxLength(500)
                    ->required(),
                TextInput::make('title')
                    ->label('عنوان تصویر')
                    ->maxLength(220),
                Checkbox::make('lazy')
                    ->label('بارگذاری تنبل تصویر')
                    ->default(true),
            ])
            ->action(function (TiptapEditor $component, array $data): void {
                $component->getLivewire()->dispatch(
                    event: 'insertFromAction',
                    type: 'media',
                    statePath: $component->getStatePath(),
                    media: [
                        'src' => $data['src'],
                        'alt' => $data['alt'],
                        'title' => $data['title'] ?? null,
                        'width' => null,
                        'height' => null,
                        'lazy' => $data['lazy'] ?? true,
                        'link_text' => null,
                    ],
                );
            });
    }
}
