<?php

namespace App\Filament\Pages;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationOutbox;
use App\Models\PushSubscription;
use App\Services\Notifications\WebPushService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class PushCampaigns extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'اعلان‌های فروشگاه';

    protected static ?string $navigationGroup = 'محتوا';

    protected static string $view = 'filament.pages.push-campaigns';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('sendCampaign')
            ->label('آماده‌سازی و صف‌بندی اعلان')
            ->form([
                Forms\Components\TextInput::make('title')->label('عنوان')->required()->maxLength(100),
                Forms\Components\Textarea::make('body')->label('متن پیام')->required()->maxLength(300),
                Forms\Components\TextInput::make('url')->label('مسیر داخلی سایت')
                    ->default('/shop')->required()->maxLength(300)
                    ->rule('regex:/^\/(?!\/)[^\s]*$/'),
            ])
            ->requiresConfirmation()
            ->modalDescription('اعلان برای همهٔ دستگاه‌های سایت و وب‌اپ، شامل مهمان‌ها، که دریافت اعلان فروشگاه را فعال کرده‌اند در صف قرار می‌گیرد. ارسال توسط پردازشگر اعلان انجام می‌شود.')
            ->action(function (array $data, WebPushService $webPush): void {
                if (! $webPush->ready()) {
                    Notification::make()->danger()->title('سرویس اعلان روی سرور فعال نیست.')->send();

                    return;
                }

                $campaignId = (string) Str::ulid();
                $count = 0;
                PushSubscription::query()->active()->where('marketing_enabled', true)
                    ->orderBy('id')->chunkById(100, function ($subscriptions) use ($data, $campaignId, &$count): void {
                        foreach ($subscriptions as $subscription) {
                            NotificationOutbox::query()->create([
                                'customer_id' => $subscription->customer_id,
                                'order_id' => null,
                                'channel' => NotificationChannel::WebPush,
                                'destination' => $subscription->public_id,
                                'template_key' => 'campaign',
                                'payload' => [
                                    'campaign_id' => $campaignId,
                                    'topic' => 'broadcast',
                                    'title' => $data['title'],
                                    'body' => $data['body'],
                                    'url' => $data['url'],
                                ],
                                'status' => NotificationStatus::Pending,
                                'provider' => 'web-push',
                                'available_at' => now(),
                            ]);
                            $count++;
                        }
                    });

                Notification::make()->success()->title("{$count} اعلان در صف قرار گرفت.")->send();
            })];
    }
}
