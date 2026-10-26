<?php

namespace App\Enums;

enum DeliveryMethod: string
{
    /** @deprecated Kept only for backward-compatible pre-P4 requests. */
    case Standard = 'standard';

    /** @deprecated Kept only for backward-compatible pre-P4 requests. */
    case Pickup = 'pickup';

    case ImmediateCourier = 'immediate_courier';
    case Tipax = 'tipax';
    case Decapost = 'decapost';
    case ExpressPost = 'express_post';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'ارسال معمولی (قدیمی)',
            self::Pickup => 'تحویل حضوری (قدیمی)',
            self::ImmediateCourier => 'ارسال فوری — اسنپ / اسنپ‌باکس',
            self::Tipax => 'تیپاکس — پس‌کرایه',
            self::Decapost => 'دکاپست — پس‌کرایه',
            self::ExpressPost => 'پست پیشتاز',
        };
    }

    public function requiresAddress(): bool
    {
        return $this !== self::Pickup;
    }

    public function isOfficialP4Method(): bool
    {
        return match ($this) {
            self::ImmediateCourier, self::Tipax, self::Decapost, self::ExpressPost => true,
            self::Standard, self::Pickup => false,
        };
    }

    public function isEmployerApprovedMethod(): bool
    {
        return match ($this) {
            self::ImmediateCourier, self::Tipax, self::Decapost => true,
            self::ExpressPost, self::Standard, self::Pickup => false,
        };
    }

    public function isFreightCollect(): bool
    {
        return $this->isEmployerApprovedMethod();
    }

    public function carrierLabel(): ?string
    {
        return match ($this) {
            self::ImmediateCourier => 'اسنپ / اسنپ‌باکس',
            self::Tipax => 'تیپاکس',
            self::Decapost => 'دکاپست',
            default => null,
        };
    }

    public function coverageLabel(): ?string
    {
        return match ($this) {
            self::ImmediateCourier => 'تهران و کرج',
            self::Tipax, self::Decapost => 'تمام نقاط ایران',
            default => null,
        };
    }

    public function etaLabel(): ?string
    {
        return match ($this) {
            self::ImmediateCourier => 'فوری',
            self::Tipax, self::Decapost => '۳ تا ۷ روز',
            default => null,
        };
    }

    /** @return array{minDays: ?int, maxDays: ?int} */
    public function etaDays(): array
    {
        return match ($this) {
            self::Tipax, self::Decapost => ['minDays' => 3, 'maxDays' => 7],
            default => ['minDays' => null, 'maxDays' => null],
        };
    }
}
