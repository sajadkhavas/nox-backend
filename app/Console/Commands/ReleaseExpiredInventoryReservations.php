<?php

namespace App\Console\Commands;

use App\Services\Orders\OrderLifecycleService;
use Illuminate\Console\Command;

class ReleaseExpiredInventoryReservations extends Command
{
    protected $signature = 'inventory:release-expired';

    protected $description = 'Release expired unpaid order reservations and expire their orders';

    public function handle(OrderLifecycleService $orders): int
    {
        $count = $orders->expireAwaitingPaymentOrders();
        $this->info("{$count} expired order reservation(s) released.");

        return self::SUCCESS;
    }
}
