<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryZoneResource\Pages;
use App\Models\DeliveryZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'مناطق ارسال';

    protected static ?string $modelLabel = 'منطقه ارسال';

    protected static ?string $pluralModelLabel = 'مناطق ارسال';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('محدوده')->schema([
                Forms\Components\TextInput::make('name')->label('نام')->required()->maxLength(140),
                Forms\Components\TextInput::make('province')->label('استان')->maxLength(100),
                Forms\Components\TextInput::make('city')->label('شهر')->maxLength(100),
                Forms\Components\TextInput::make('priority')->label('اولویت')->numeric()->default(100)->required(),
                Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
            ])->columns(3),
            Forms\Components\Section::make('سیاست فعال ارسال فروشگاه')->description('هر سه روش فعال پس‌کرایه هستند؛ هزینه حمل به مبلغ پرداخت آنلاین اضافه نمی‌شود.')->schema([
                Forms\Components\Toggle::make('immediate_courier_enabled')->label('ارسال فوری — اسنپ / اسنپ‌باکس (فقط تهران و کرج)'),
                Forms\Components\Placeholder::make('immediate_courier_policy')->label('کرایه ارسال فوری')->content('پس‌کرایه — خارج از پرداخت آنلاین فروشگاه'),
                Forms\Components\Toggle::make('tipax_enabled')->label('تیپاکس — تمام نقاط ایران (۳ تا ۷ روز)'),
                Forms\Components\Placeholder::make('tipax_policy')->label('کرایه تیپاکس')->content('پس‌کرایه — خارج از پرداخت آنلاین فروشگاه'),
                Forms\Components\Toggle::make('decapost_enabled')->label('دکاپست — تمام نقاط ایران (۳ تا ۷ روز)'),
                Forms\Components\Placeholder::make('decapost_policy')->label('کرایه دکاپست')->content('پس‌کرایه — خارج از پرداخت آنلاین فروشگاه'),
                Forms\Components\TextInput::make('packaging_fee_toman')->label('هزینه بسته‌بندی فروشگاه')->numeric()->default(0)->suffix(' تومان'),
            ])->columns(2),
            Forms\Components\Section::make('روش‌های خارج از سیاست فعلی — فعال نکنید')->schema([
                Forms\Components\Toggle::make('express_post_enabled')->label('پست پیشتاز')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('express_post_fee_toman')->label('هزینه پست پیشتاز')->numeric()->disabled()->dehydrated(false)->suffix(' تومان'),
                Forms\Components\TextInput::make('free_delivery_threshold_toman')->label('آستانه ارسال رایگان قدیمی')->numeric()->disabled()->dehydrated(false)->suffix(' تومان'),
                Forms\Components\Toggle::make('standard_enabled')->label('ارسال عادی قدیمی')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('standard_fee_toman')->label('هزینه عادی قدیمی')->numeric()->disabled()->dehydrated(false)->suffix(' تومان'),
                Forms\Components\Toggle::make('pickup_enabled')->label('تحویل حضوری قدیمی')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('pickup_fee_toman')->label('هزینه حضوری قدیمی')->numeric()->disabled()->dehydrated(false)->suffix(' تومان'),
            ])->columns(2)->collapsed(),
            Forms\Components\Section::make('محدودیت عملیات')->schema([
                Forms\Components\TextInput::make('minimum_order_toman')->label('حداقل سفارش')->numeric()->nullable()->suffix(' تومان'),
                Forms\Components\TextInput::make('preparation_min_days')->label('حداقل روز پردازش')->numeric()->default(0),
                Forms\Components\TextInput::make('preparation_max_days')->label('حداکثر روز پردازش')->numeric()->default(0),
                Forms\Components\TextInput::make('daily_order_limit')->label('ظرفیت روزانه')->numeric()->nullable(),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('province')->label('استان')->searchable()->placeholder('همه'),
                Tables\Columns\TextColumn::make('city')->label('شهر')->searchable()->placeholder('همه'),
                Tables\Columns\IconColumn::make('immediate_courier_enabled')->label('فوری')->boolean(),
                Tables\Columns\IconColumn::make('tipax_enabled')->label('تیپاکس')->boolean(),
                Tables\Columns\IconColumn::make('decapost_enabled')->label('دکاپست')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('priority')->label('اولویت')->sortable(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_active')->label('فعال')])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('priority');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDeliveryZones::route('/')];
    }
}
