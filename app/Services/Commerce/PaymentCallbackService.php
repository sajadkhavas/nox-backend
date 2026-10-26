<?php

namespace App\Services\Commerce;

use App\Models\PaymentAttempt;
use App\Models\PaymentCallbackEvent;
use Illuminate\Support\Facades\DB;

final class PaymentCallbackService
{
    public function __construct(private readonly CommerceAuditService $audit) {}

    /** @return array{event: PaymentCallbackEvent,replayed: bool} */
    public function receive(PaymentAttempt $attempt, ?string $callbackStatus): array
    {
        $normalizedStatus = mb_substr(trim((string) $callbackStatus), 0, 40);
        $requestHash = hash('sha256', implode('|', [
            $attempt->provider,
            (string) $attempt->authority,
            $normalizedStatus,
        ]));

        return DB::transaction(function () use ($attempt, $normalizedStatus, $requestHash): array {
            $existing = PaymentCallbackEvent::query()
                ->where('provider', $attempt->provider)
                ->where('authority', (string) $attempt->authority)
                ->where('request_hash', $requestHash)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                $this->audit->record('payment.callback.replayed', 'payment_callback_event', $existing->public_id, $attempt->order, 'provider', null, [
                    'provider' => $attempt->provider,
                ]);

                return ['event' => $existing, 'replayed' => true];
            }

            $event = PaymentCallbackEvent::query()->create([
                'payment_attempt_id' => $attempt->getKey(),
                'order_id' => $attempt->order_id,
                'provider' => $attempt->provider,
                'authority' => (string) $attempt->authority,
                'request_hash' => $requestHash,
                'status' => 'received',
                'received_at' => now(),
            ]);
            $this->audit->record('payment.callback.received', 'payment_callback_event', $event->public_id, $attempt->order, 'provider', null, [
                'provider' => $attempt->provider,
                'callbackStatus' => $normalizedStatus,
            ]);

            return ['event' => $event, 'replayed' => false];
        }, 3);
    }

    public function processed(PaymentCallbackEvent $event, ?string $providerReference, bool $verified): void
    {
        DB::transaction(function () use ($event, $providerReference, $verified): void {
            $locked = PaymentCallbackEvent::query()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->processed_at !== null) {
                return;
            }
            $locked->forceFill([
                'status' => $verified ? 'verified' : 'rejected',
                'provider_reference' => $providerReference ? mb_substr($providerReference, 0, 180) : null,
                'processed_at' => now(),
            ])->save();
        }, 3);
    }
}
