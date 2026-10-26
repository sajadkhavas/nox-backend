<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ProductReviewResource\Pages;
use App\Models\ProductReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'نظرات محصولات';

    protected static ?string $modelLabel = 'نظر محصول';

    protected static ?string $pluralModelLabel = 'نظرات محصولات';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('product.name')->label('محصول')->disabled(),
            Forms\Components\TextInput::make('customer.full_name')->label('مشتری')->disabled(),
            Forms\Components\TextInput::make('order.order_number')->label('سفارش')->disabled(),
            Forms\Components\TextInput::make('rating')->label('امتیاز')->disabled(),
            Forms\Components\TextInput::make('title')->label('عنوان')->disabled()->columnSpanFull(),
            Forms\Components\Textarea::make('body')->label('متن نظر')->disabled()->rows(5)->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label('وضعیت بررسی')
                ->options(collect(ReviewStatus::cases())->mapWithKeys(
                    fn (ReviewStatus $status): array => [$status->value => $status->label()],
                ))
                ->required(),
            Forms\Components\DateTimePicker::make('published_at')->label('زمان انتشار'),
            Forms\Components\Textarea::make('moderation_note')->label('یادداشت بررسی')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('محصول')->searchable(),
                Tables\Columns\TextColumn::make('customer.full_name')->label('مشتری')->searchable(),
                Tables\Columns\TextColumn::make('rating')->label('امتیاز')->badge()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('عنوان')->limit(45),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (ReviewStatus $state): string => $state->label()),
                Tables\Columns\IconColumn::make('is_verified_purchase')->label('خرید تأییدشده')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('ثبت')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(collect(ReviewStatus::cases())->mapWithKeys(
                        fn (ReviewStatus $status): array => [$status->value => $status->label()],
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('تأیید')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProductReview $record): bool => $record->status !== ReviewStatus::Approved)
                    ->action(fn (ProductReview $record): bool => $record->update([
                        'status' => ReviewStatus::Approved,
                        'published_at' => now(),
                        'moderation_note' => null,
                    ])),
                Tables\Actions\Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('note')->label('دلیل رد')->required()->maxLength(1000),
                    ])
                    ->visible(fn (ProductReview $record): bool => $record->status !== ReviewStatus::Rejected)
                    ->action(fn (ProductReview $record, array $data): bool => $record->update([
                        'status' => ReviewStatus::Rejected,
                        'published_at' => null,
                        'moderation_note' => trim($data['note']),
                    ])),
                Tables\Actions\EditAction::make()->label('جزئیات'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageProductReviews::route('/')];
    }
}
