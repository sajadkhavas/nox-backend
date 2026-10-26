<?php
namespace App\Filament\Widgets;

use App\Models\Contact;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestContactWidget extends TableWidget
{
    protected static ?string $heading = 'آخرین پیام‌های تماس';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(Contact::query()->latest()->limit(6))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->copyable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('موضوع')
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unread'  => 'danger',
                        'read'    => 'warning',
                        'replied' => 'success',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'unread'  => 'خوانده نشده',
                        'read'    => 'خوانده شده',
                        'replied' => 'پاسخ داده شده',
                        default   => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('مشاهده')
                    ->icon('heroicon-m-eye')
                    ->url(fn(Contact $record): string => route('filament.admin.resources.contacts.edit', $record)),
            ]);
    }
}
