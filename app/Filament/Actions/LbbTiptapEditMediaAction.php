<?php

namespace App\Filament\Actions;

use App\Support\StorefrontMediaOptions;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use FilamentTiptapEditor\TiptapEditor;

final class LbbTiptapEditMediaAction
{
    public static function make(): Action
    {
        return Action::make('filament_tiptap_edit_media')
            ->arguments([
                'src' => '',
                'alt' => '',
                'title' => '',
                'width' => '',
                'height' => '',
                'lazy' => null,
            ])
            ->modalWidth('md')
            ->modalHeading('ویرایش تصویر محتوا')
            ->modalDescription('تصویر باید از کتابخانه رسانه مرکزی LBB انتخاب شود. ویرایش اینجا فایل جدید و خارج از کتابخانه ایجاد نمی‌کند.')
            ->mountUsing(function (ComponentContainer $form, array $arguments): void {
                $form->fill([
                    'src' => $arguments['src'] ?? '',
                    'alt' => $arguments['alt'] ?? '',
                    'title' => $arguments['title'] ?? '',
                    'width' => $arguments['width'] ?? '',
                    'height' => $arguments['height'] ?? '',
                    'lazy' => $arguments['lazy'] ?? true,
                ]);
            })
            ->form(function (array $arguments): array {
                return [
                    Select::make('src')
                        ->label('تصویر از کتابخانه رسانه')
                        ->options(fn (): array => StorefrontMediaOptions::urls($arguments['src'] ?? null))
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
                    TextInput::make('width')
                        ->label('عرض نمایشی')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                    TextInput::make('height')
                        ->label('ارتفاع نمایشی')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                ];
            })
            ->action(function (TiptapEditor $component, array $data): void {
                $component->getLivewire()->dispatch(
                    event: 'insertFromAction',
                    type: 'media',
                    statePath: $component->getStatePath(),
                    media: [
                        'src' => $data['src'],
                        'alt' => $data['alt'] ?? null,
                        'title' => $data['title'] ?? null,
                        'width' => $data['width'] ?? null,
                        'height' => $data['height'] ?? null,
                        'lazy' => $data['lazy'] ?? true,
                        'link_text' => null,
                    ],
                );
            });
    }
}
