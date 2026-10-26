<?php
namespace App\Jobs;
use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public string $event,
        public array $payload = []
    ) {}

    public function handle(): void
    {
        Webhook::where('is_active', true)
            ->whereJsonContains('events', $this->event)
            ->each(fn($webhook) => $webhook->trigger($this->event, $this->payload));
    }
}
