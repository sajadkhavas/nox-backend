<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'مشتریان';

    protected static ?string $modelLabel = 'مشتری';

    protected static ?string $pluralModelLabel = 'مشتریان';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('حساب مشتری')
                ->description('شماره موبایل فقط از مسیر OTP تأیید می‌شود و در پنل قابل تغییر نیست.')
                ->schema([
                    TextInput::make('public_id')
                        ->label('شناسه عمومی')
                        ->disabled(),
                    TextInput::make('mobile')
                        ->label('شماره موبایل')
                        ->disabled(),
                    TextInput::make('full_name')
                        ->label('نام و نام خانوادگی')
                        ->maxLength(120),
                    TextInput::make('email')
                        ->label('ایمیل')
                        ->email()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Toggle::make('is_active')
                        ->label('حساب فعال')
                        ->helperText('با غیرفعال‌کردن حساب، ورودهای بعدی مسدود می‌شوند.'),
                    Toggle::make('marketing_consent')
                        ->label('رضایت دریافت پیام‌های بازاریابی')
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mobile')
                    ->label('موبایل')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('نام مشتری')
                    ->placeholder('ثبت نشده')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->placeholder('ثبت نشده')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('mobile_verified_at')
                    ->label('موبایل تأییدشده')
                    ->getStateUsing(fn (Customer $record): bool => $record->mobile_verified_at !== null)
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخرین ورود')
                    ->dateTime('Y/m/d H:i')
                    ->placeholder('بدون ورود')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ عضویت')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('وضعیت حساب'),
                Tables\Filters\TernaryFilter::make('marketing_consent')->label('رضایت بازاریابی'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
