<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('اطلاعات فعالیت')->schema([
                TextEntry::make('log_name')->label('نوع لاگ')->badge(),
                TextEntry::make('event')->label('رویداد')->badge()
                    ->color(fn ($state) => match($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    }),
                TextEntry::make('description')->label('توضیحات'),
                TextEntry::make('subject_type')->label('نوع موضوع')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '—'),
                TextEntry::make('subject_id')->label('شناسه موضوع')->default('—'),
                TextEntry::make('causer.name')->label('کاربر')->default('—'),
                TextEntry::make('created_at')->label('زمان')->dateTime('Y/m/d H:i:s'),
            ])->columns(2),

            Section::make('جزئیات')->schema([
                TextEntry::make('id')
                    ->label('مشخصات')
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        $props = $record->properties;
                        if (empty($props)) return '—';
                        $arr = $props instanceof \Illuminate\Support\Collection
                            ? $props->toArray()
                            : (array) $props;
                        if (empty($arr)) return '—';
                        $json = json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        return '<pre style="white-space:pre-wrap;font-family:monospace;font-size:0.8rem">'
                            . htmlspecialchars($json) . '</pre>';
                    }),
            ]),
        ]);
    }
}
