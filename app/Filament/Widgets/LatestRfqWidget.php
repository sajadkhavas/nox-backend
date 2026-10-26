<?php
namespace App\Filament\Widgets;

use App\Models\RfqRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestRfqWidget extends TableWidget
{
    protected static ?string $heading = 'آخرین استعلام‌های قیمت';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(RfqRequest::query()->latest()->limit(8))
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('شماره')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('تعداد اقلام')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'quoted'     => 'success',
                        'closed'     => 'gray',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending'    => 'در انتظار',
                        'processing' => 'در حال بررسی',
                        'quoted'     => 'قیمت داده شده',
                        'closed'     => 'بسته شده',
                        default      => $state,
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
                    ->url(fn(RfqRequest $record): string => route('filament.admin.resources.rfqs.edit', $record)),
            ]);
    }
}
