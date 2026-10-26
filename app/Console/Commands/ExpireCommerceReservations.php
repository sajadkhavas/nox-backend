<?php

namespace App\Console\Commands;

use App\Enums\InventoryReservationStatus;
use App\Models\InventoryReservation;
use App\Models\ProductVariant;
use App\Services\Commerce\InventoryLedgerService;
use Illuminate\Console\Command;

class ExpireCommerceReservations extends Command
{
    protected $signature = 'commerce:expire-reservations';

    protected $description = 'Expire stale commerce reservations through the inventory ledger.';

    public function handle(InventoryLedgerService $inventory): int
    {
        $variantIds = InventoryReservation::query()
            ->where('status', InventoryReservationStatus::Active->value)
            ->where('expires_at', '<=', now())
            ->orderBy('variant_id')
            ->distinct()
            ->pluck('variant_id');

        $expired = 0;
        foreach ($variantIds as $variantId) {
            $variant = ProductVariant::query()->find($variantId);
            if ($variant) {
                $expired += $inventory->expireStaleReservationsForVariant($variant);
            }
        }
        $this->info("expired_reservations={$expired}");

        return self::SUCCESS;
    }
}
