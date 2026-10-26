<?php

namespace App\Services\Commerce;

use App\Enums\CommerceErrorCode;
use App\Enums\InventoryLedgerEventType;
use App\Enums\OrderStatus;
use App\Enums\ReturnResolution;
use App\Enums\ReturnStatus;
use App\Exceptions\CommerceException;
use App\Exceptions\IdempotencyConflict;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class ReturnService
{
    public function __construct(
        private readonly InventoryLedgerService $inventory,
        private readonly CommerceAuditService $audit,
        private readonly RefundService $refunds,
    ) {}

    /** @return array{return: ReturnRequest,replayed: bool} */
    public function request(Customer $customer, Order $order, array $payload, string $idempotencyKey): array
    {
        $canonical = [
            'orderId' => $order->public_id,
            'reason' => trim((string) ($payload['reason'] ?? '')),
            'items' => collect($payload['items'] ?? [])
                ->map(fn (array $item): array => [
                    'orderItemId' => trim((string) ($item['orderItemId'] ?? '')),
                    'quantity' => (int) ($item['quantity'] ?? 0),
                ])
                ->sortBy('orderItemId')->values()->all(),
        ];
        $requestHash = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $existing = ReturnRequest::query()->where('customer_id', $customer->getKey())->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if (! hash_equals($existing->request_hash, $requestHash)) {
                throw new IdempotencyConflict;
            }

            return ['return' => $existing->load('items.orderItem'), 'replayed' => true];
        }

        try {
            return DB::transaction(function () use ($customer, $order, $canonical, $idempotencyKey, $requestHash): array {
                $lockedOrder = Order::query()
                    ->ownedBy($customer)
                    ->whereKey($order->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($lockedOrder->status !== OrderStatus::Delivered) {
                    throw new CommerceException(
                        CommerceErrorCode::ReturnUnavailable,
                        'فقط سفارش تحویل‌شده می‌تواند وارد فرآیند مرجوعی شود.',
                        409,
                    );
                }
                if ($canonical['reason'] === '' || $canonical['items'] === []) {
                    throw new CommerceException(
                        CommerceErrorCode::ReturnUnavailable,
                        'دلیل و حداقل یک قلم برای مرجوعی الزامی است.',
                        422,
                    );
                }

                $itemIds = collect($canonical['items'])->pluck('orderItemId');
                $orderItems = OrderItem::query()
                    ->where('order_id', $lockedOrder->getKey())
                    ->whereIn('public_id', $itemIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('public_id');
                if ($orderItems->count() !== $itemIds->unique()->count()) {
                    throw new CommerceException(CommerceErrorCode::ReturnUnavailable, 'یک یا چند قلم متعلق به این سفارش نیست.', 422);
                }

                $return = ReturnRequest::query()->create([
                    'customer_id' => $customer->getKey(),
                    'order_id' => $lockedOrder->getKey(),
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $requestHash,
                    'status' => ReturnStatus::Requested,
                    'reason' => $canonical['reason'],
                    'requested_at' => now(),
                ]);

                foreach ($canonical['items'] as $requested) {
                    /** @var OrderItem $line */
                    $line = $orderItems->get($requested['orderItemId']);
                    $quantity = (int) $requested['quantity'];
                    if ($quantity < 1 || $quantity > (int) $line->quantity) {
                        throw new CommerceException(CommerceErrorCode::InvalidQuantity, 'تعداد مرجوعی معتبر نیست.', 422);
                    }
                    $already = (int) DB::table('return_items')
                        ->join('return_requests', 'return_requests.id', '=', 'return_items.return_request_id')
                        ->where('return_items.order_item_id', $line->getKey())
                        ->whereNotIn('return_requests.status', [ReturnStatus::Rejected->value, ReturnStatus::Cancelled->value])
                        ->sum('return_items.quantity');
                    if ($already + $quantity > (int) $line->quantity) {
                        throw new CommerceException(CommerceErrorCode::InvalidQuantity, 'تعداد مرجوعی از تعداد خرید بیشتر است.', 409);
                    }

                    $return->items()->create([
                        'order_item_id' => $line->getKey(),
                        'quantity' => $quantity,
                        'refund_value_toman' => (int) $line->unit_price_toman * $quantity,
                    ]);
                }

                $this->audit->record('return.requested', 'return_request', $return->public_id, $lockedOrder, 'customer', $customer->getKey(), [
                    'itemCount' => count($canonical['items']),
                ]);

                return ['return' => $return->load('items.orderItem'), 'replayed' => false];
            }, 3);
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }
            $existing = ReturnRequest::query()->where('customer_id', $customer->getKey())->where('idempotency_key', $idempotencyKey)->first();
            if (! $existing || ! hash_equals($existing->request_hash, $requestHash)) {
                throw $exception;
            }

            return ['return' => $existing->load('items.orderItem'), 'replayed' => true];
        }
    }

    public function approve(ReturnRequest $return, ?int $adminId, ?string $note = null): ReturnRequest
    {
        return $this->transition($return, ReturnStatus::Requested, ReturnStatus::Approved, $adminId, $note, 'approved_at', 'return.approved');
    }

    public function reject(ReturnRequest $return, ?int $adminId, string $note): ReturnRequest
    {
        return $this->transition($return, ReturnStatus::Requested, ReturnStatus::Rejected, $adminId, $note, 'rejected_at', 'return.rejected');
    }

    public function receive(ReturnRequest $return, ?int $adminId, ?string $note = null): ReturnRequest
    {
        return $this->transition($return, ReturnStatus::Approved, ReturnStatus::Received, $adminId, $note, 'received_at', 'return.received');
    }

    public function resolve(
        ReturnRequest $return,
        ReturnResolution $resolution,
        bool $restock,
        ?int $adminId,
        ?string $note = null,
    ): ReturnRequest {
        return DB::transaction(function () use ($return, $resolution, $restock, $adminId, $note): ReturnRequest {
            $locked = ReturnRequest::query()->whereKey($return->getKey())->lockForUpdate()->with(['items.orderItem', 'order'])->firstOrFail();
            if ($locked->status !== ReturnStatus::Received) {
                throw new CommerceException(CommerceErrorCode::InvalidTransition, 'مرجوعی هنوز در وضعیت قابل نهایی‌سازی نیست.', 409);
            }

            if ($restock) {
                foreach ($locked->items as $item) {
                    $variant = $item->orderItem?->variant;
                    if ($variant) {
                        $this->inventory->recordReturnStock(
                            $variant,
                            (int) $item->quantity,
                            'return_request',
                            $locked->public_id,
                            $locked->order,
                            'return_received_restock',
                            "return:restock:{$locked->public_id}:{$item->order_item_id}",
                            'admin',
                            $adminId,
                            InventoryLedgerEventType::Return,
                        );
                    }
                }
            }

            $locked->forceFill([
                'status' => ReturnStatus::Resolved,
                'resolution' => $resolution,
                'admin_note' => $this->mergeNote($locked->admin_note, $note),
                'resolved_at' => now(),
            ])->save();

            if ($resolution === ReturnResolution::RefundRequested) {
                $amount = (int) $locked->items->sum('refund_value_toman');
                $this->refunds->requestForReturn($locked, $amount, $adminId);
            }
            $this->audit->record('return.resolved', 'return_request', $locked->public_id, $locked->order, 'admin', $adminId, [
                'resolution' => $resolution->value,
                'restocked' => $restock,
            ]);

            return $locked->fresh(['items.orderItem', 'refunds']);
        }, 3);
    }

    private function transition(ReturnRequest $return, ReturnStatus $from, ReturnStatus $to, ?int $adminId, ?string $note, string $timestamp, string $event): ReturnRequest
    {
        return DB::transaction(function () use ($return, $from, $to, $adminId, $note, $timestamp, $event): ReturnRequest {
            $locked = ReturnRequest::query()->whereKey($return->getKey())->lockForUpdate()->with('order')->firstOrFail();
            if ($locked->status !== $from) {
                throw new CommerceException(CommerceErrorCode::InvalidTransition, 'انتقال وضعیت مرجوعی مجاز نیست.', 409);
            }
            $locked->forceFill([
                'status' => $to,
                $timestamp => now(),
                'admin_note' => $this->mergeNote($locked->admin_note, $note),
            ])->save();
            $this->audit->record($event, 'return_request', $locked->public_id, $locked->order, 'admin', $adminId);

            return $locked->fresh(['items.orderItem']);
        }, 3);
    }

    private function mergeNote(?string $current, ?string $note): ?string
    {
        $note = trim((string) $note);
        if ($note === '') {
            return $current;
        }

        return trim(($current ? $current."\n" : '').$note);
    }
}
