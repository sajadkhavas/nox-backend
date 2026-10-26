<?php

namespace App\Filament\Resources;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Filament\Resources\InquiryResource\Pages;
use App\Models\Inquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'درخواست‌ها';

    protected static ?string $modelLabel = 'درخواست';

    protected static ?string $pluralModelLabel = 'درخواست‌های مشتریان';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('public_id')->label('شناسه')->disabled(),
            Forms\Components\Select::make('type')
                ->label('نوع')
                ->options(collect(InquiryType::cases())->mapWithKeys(
                    fn (InquiryType $type): array => [$type->value => $type->label()],
                ))
                ->disabled(),
            Forms\Components\TextInput::make('full_name')->label('نام')->disabled(),
            Forms\Components\TextInput::make('mobile')->label('موبایل')->disabled(),
            Forms\Components\TextInput::make('email')->label('ایمیل')->disabled(),
            Forms\Components\TextInput::make('subject')->label('موضوع')->disabled()->columnSpanFull(),
            Forms\Components\Textarea::make('message')->label('پیام')->disabled()->rows(7)->columnSpanFull(),
            Forms\Components\KeyValue::make('metadata')->label('جزئیات')->disabled()->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label('وضعیت پیگیری')
                ->options(collect(InquiryStatus::cases())->mapWithKeys(
                    fn (InquiryStatus $status): array => [$status->value => $status->label()],
                ))
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('public_id')->label('شناسه')->copyable()->limit(12),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn (InquiryType $state): string => $state->label()),
                Tables\Columns\TextColumn::make('full_name')->label('نام')->searchable(),
                Tables\Columns\TextColumn::make('mobile')->label('موبایل')->searchable(),
                Tables\Columns\TextColumn::make('subject')->label('موضوع')->limit(45),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (InquiryStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('created_at')->label('ثبت')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options(collect(InquiryType::cases())->mapWithKeys(
                        fn (InquiryType $type): array => [$type->value => $type->label()],
                    )),
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(collect(InquiryStatus::cases())->mapWithKeys(
                        fn (InquiryStatus $status): array => [$status->value => $status->label()],
                    )),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('بررسی')])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInquiries::route('/')];
    }
}
