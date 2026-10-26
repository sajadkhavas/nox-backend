<?php

namespace App\Services\Commerce;

use App\Models\CommerceAuditEvent;
use App\Models\Order;

final class CommerceAuditService
{
    public function record(
        string $eventKey,
        string $subjectType,
        ?string $subjectPublicId = null,
        ?Order $order = null,
        string $actorType = 'system',
        ?int $actorId = null,
        array $metadata = [],
    ): CommerceAuditEvent {
        return CommerceAuditEvent::query()->create([
            'order_id' => $order?->getKey(),
            'event_key' => $eventKey,
            'subject_type' => $subjectType,
            'subject_public_id' => $subjectPublicId,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'request_id' => app()->bound('lbb.request_id') ? (string) app('lbb.request_id') : null,
            'metadata' => $this->sanitize($metadata),
            'created_at' => now(),
        ]);
    }

    private function sanitize(array $metadata): array
    {
        $blocked = ['password', 'secret', 'token', 'authorization', 'merchant_id', 'api_key', 'card_pan', 'card_hash'];

        $walk = function (array $data) use (&$walk, $blocked): array {
            $clean = [];
            foreach ($data as $key => $value) {
                $normalized = strtolower((string) $key);
                if (in_array($normalized, $blocked, true)) {
                    continue;
                }
                $clean[$key] = is_array($value) ? $walk($value) : $value;
            }

            return $clean;
        };

        return $walk($metadata);
    }
}
