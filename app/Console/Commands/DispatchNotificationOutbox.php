<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationOutboxService;
use Illuminate\Console\Command;

class DispatchNotificationOutbox extends Command
{
    protected $signature = 'notifications:dispatch {--limit=50 : Maximum notifications to inspect}';

    protected $description = 'Dispatch pending LBB notification outbox records';

    public function handle(NotificationOutboxService $outbox): int
    {
        $sent = $outbox->dispatchPending((int) $this->option('limit'));
        $this->info("{$sent} notification(s) sent.");

        return self::SUCCESS;
    }
}
