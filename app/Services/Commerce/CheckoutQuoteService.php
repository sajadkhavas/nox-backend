<?php

namespace App\Services\Commerce;

use App\Enums\CheckoutQuoteStatus;
use App\Models\CheckoutQuote;
use App\Models\Customer;

final class CheckoutQuoteService
{
    public function __construct(
        private readonly CartValidationService $cart,
        private readonly CommerceAuditService $audit,
    ) {}

    /** @return array{quote: CheckoutQuote, snapshot: array<string,mixed>} */
    public function create(Customer $customer, array $payload): array
    {
        $snapshot = $this->cart->validate($customer, $payload);
        $requestHash = hash('sha256', json_encode([
            'customerId' => $customer->public_id,
            'items' => $snapshot['items'],
            'recipient' => $snapshot['recipient'],
            'deliveryMethod' => $snapshot['deliveryMethod'],
            'totals' => [
                $snapshot['subtotalToman'],
                $snapshot['deliveryFeeToman'],
                $snapshot['packagingFeeToman'],
                $snapshot['grandTotalToman'],
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $expiresAt = now()->addMinutes(max(1, (int) config('lbb.commerce.quote_minutes', 5)));

        $quote = CheckoutQuote::query()->create([
            'customer_id' => $customer->getKey(),
            'request_hash' => $requestHash,
            'status' => CheckoutQuoteStatus::Active,
            'delivery_method' => $snapshot['deliveryMethod'],
            'delivery_zone_id' => $snapshot['deliveryZoneId'],
            'items_snapshot' => $snapshot['items'],
            'recipient_snapshot' => $snapshot['recipient'],
            'subtotal_toman' => $snapshot['subtotalToman'],
            'delivery_fee_toman' => $snapshot['deliveryFeeToman'],
            'packaging_fee_toman' => $snapshot['packagingFeeToman'],
            'discount_total_toman' => $snapshot['discountTotalToman'],
            'grand_total_toman' => $snapshot['grandTotalToman'],
            'currency' => $snapshot['currency'],
            'expires_at' => $expiresAt,
        ]);
        $this->audit->record('checkout.quote.created', 'checkout_quote', $quote->public_id, null, 'customer', $customer->getKey(), [
            'grandTotalToman' => $quote->grand_total_toman,
            'expiresAt' => $expiresAt->toIso8601String(),
        ]);

        return ['quote' => $quote, 'snapshot' => $snapshot];
    }
}
