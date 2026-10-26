<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreSettingResource\Pages;
use App\Models\StoreSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreSettingResource extends Resource
{
    protected static ?string $model = StoreSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'تنظیمات خام توسعه‌دهنده';

    protected static ?string $modelLabel = 'تنظیم خام';

    protected static ?string $pluralModelLabel = 'تنظیمات خام توسعه‌دهنده';

    protected static ?string $navigationGroup = 'سیستم';

    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('warning')
                ->label('سطح توسعه‌دهنده')
                ->content('این صفحه برای نگهداری فنی است. مدیریت عادی ویترین باید از «کنترل ویترین» و Resourceهای دامنه انجام شود.')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('group')
                ->label('گروه')
                ->required()
                ->maxLength(80),
            Forms\Components\TextInput::make('key')
                ->label('کلید')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(140),
            Forms\Components\TextInput::make('label')
                ->label('عنوان')
                ->required()
                ->maxLength(180),
            Forms\Components\Select::make('type')
                ->label('نوع')
                ->options([
                    'string' => 'متن',
                    'integer' => 'عدد',
                    'boolean' => 'بله/خیر',
                    'json' => 'JSON',
                ])
                ->required()
                ->default('string'),
            Forms\Components\Textarea::make('value')
                ->label('مقدار')
                ->rows(5)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_public')
                ->label('قابل نمایش در API عمومی')
                ->helperText('تنظیمات حساس و مدیریتی را عمومی نکنید.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')->label('گروه')->badge()->sortable(),
                Tables\Columns\TextColumn::make('key')->label('کلید')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('label')->label('عنوان')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('نوع')->badge(),
                Tables\Columns\IconColumn::make('is_public')->label('عمومی')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('آخرین تغییر')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')->label('گروه')->options(
                    fn (): array => StoreSetting::query()->distinct()->orderBy('group')->pluck('group', 'group')->all()
                ),
                Tables\Filters\TernaryFilter::make('is_public')->label('عمومی'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([])
            ->defaultSort('group');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStoreSettings::route('/'),
        ];
    }
}
