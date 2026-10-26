<?php

namespace App\Services\Commerce;

use App\Enums\CommerceErrorCode;
use App\Enums\DeliveryMethod;
use App\Enums\PublicationStatus;
use App\Exceptions\CommerceException;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\InventoryReservation;
use App\Models\ProductVariant;
use App\Services\Store\DeliveryConfigurationService;
use App\Support\IranianMobile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class CartValidationService
{
    public function __construct(private readonly DeliveryConfigurationService $delivery) {}

    /** @return array<string,mixed> */
    public function validate(Customer $customer, array $payload, bool $lockVariants = false): array
    {
        $recipient = $this->resolveRecipient($customer, $payload);
        $items = collect($payload['items'] ?? [])
            ->map(fn (array $item): array => [
                'variantId' => trim((string) ($item['variantId'] ?? '')),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'expectedUnitPriceToman' => array_key_exists('expectedUnitPriceToman', $item)
                    ? (int) $item['expectedUnitPriceToman']
                    : null,
            ])
            ->groupBy('variantId')
            ->map(function (Collection $group, string $variantId): array {
                $expected = $group->pluck('expectedUnitPriceToman')->filter(fn ($value): bool => $value !== null)->unique();
                if ($expected->count() > 1) {
                    throw new CommerceException(
                        CommerceErrorCode::PriceChanged,
                        'قیمت مورد انتظار یک Variant در سبد ناسازگار است.',
                        409,
                        ['variantId' => $variantId],
                    );
                }

                return [
                    'variantId' => $variantId,
                    'quantity' => $group->sum('quantity'),
                    'expectedUnitPriceToman' => $expected->first(),
                ];
            })
            ->sortBy('variantId')
            ->values();

        if ($items->isEmpty()) {
            throw new CommerceException(
                CommerceErrorCode::InvalidQuantity,
                'سبد خرید خالی است.',
                errors: ['items' => ['حداقل یک آیتم لازم است.']],
            );
        }

        $variantIds = $items->pluck('variantId')->values();
        $query = ProductVariant::query()
            ->withTrashed()
            ->whereIn('public_id', $variantIds)
            ->with([
                'product' => fn ($query) => $query->withTrashed()->with('category'),
                'color',
                'size',
            ])
            ->orderBy('id');
        if ($lockVariants) {
            $query->lockForUpdate();
        }
        $variants = $query->get()->keyBy('public_id');

        if ($variants->count() !== $variantIds->unique()->count()) {
            $missing = $variantIds->reject(fn (string $id): bool => $variants->has($id))->values()->all();
            throw new CommerceException(
                CommerceErrorCode::InvalidVariant,
                'یک یا چند Variant معتبر نیستند.',
                422,
                ['variantIds' => $missing],
            );
        }

        $snapshots = [];
        $subtotal = 0;
        foreach ($items as $item) {
            /** @var ProductVariant $variant */
            $variant = $variants->get($item['variantId']);
            $product = $variant->product;
            if ($variant->trashed() || ! $variant->is_active) {
                throw new CommerceException(
                    CommerceErrorCode::VariantUnavailable,
                    'Variant انتخاب‌شده قابل سفارش نیست.',
                    409,
                    ['variantId' => $variant->public_id],
                );
            }
            if (! $product) {
                throw new CommerceException(CommerceErrorCode::ProductUnavailable, 'محصول انتخاب‌شده در دسترس نیست.', 409);
            }
            if ($product->trashed() || $product->publication_status === PublicationStatus::Archived) {
                throw new CommerceException(
                    CommerceErrorCode::ArchivedProduct,
                    'محصول انتخاب‌شده آرشیو شده است.',
                    409,
                    ['productId' => $product->public_id],
                );
            }
            if ($product->publication_status !== PublicationStatus::Published) {
                throw new CommerceException(
                    CommerceErrorCode::UnpublishedProduct,
                    'محصول انتخاب‌شده منتشر نشده است.',
                    409,
                    ['productId' => $product->public_id],
                );
            }
            if (! $product->is_active || ! $product->category?->is_active || $product->category?->publication_status !== PublicationStatus::Published) {
                throw new CommerceException(
                    CommerceErrorCode::ProductUnavailable,
                    'محصول انتخاب‌شده در حال حاضر قابل سفارش نیست.',
                    409,
                    ['productId' => $product->public_id],
                );
            }
            if (! $variant->color?->is_active || ! $variant->size?->is_active || blank($variant->sku)) {
                throw new CommerceException(
                    CommerceErrorCode::VariantUnavailable,
                    'رنگ، سایز یا هویت Variant معتبر نیست.',
                    409,
                    ['variantId' => $variant->public_id],
                );
            }

            $quantity = (int) $item['quantity'];
            if ($quantity < 1) {
                throw new CommerceException(
                    CommerceErrorCode::InvalidQuantity,
                    'تعداد انتخاب‌شده معتبر نیست.',
                    422,
                    ['variantId' => $variant->public_id],
                );
            }
            $reserved = (int) InventoryReservation::query()
                ->where('variant_id', $variant->getKey())
                ->active()
                ->sum('quantity');
            $available = max(0, (int) $variant->stock_quantity - $reserved);
            if ($quantity > $available) {
                throw new CommerceException(
                    CommerceErrorCode::OutOfStock,
                    'موجودی کافی وجود ندارد.',
                    409,
                    ['variantId' => $variant->public_id, 'requested' => $quantity, 'available' => $available],
                );
            }

            $unitPrice = (int) $variant->current_price_toman;
            if ($item['expectedUnitPriceToman'] !== null && $item['expectedUnitPriceToman'] !== $unitPrice) {
                throw new CommerceException(
                    CommerceErrorCode::PriceChanged,
                    'قیمت محصول از آخرین مشاهده تغییر کرده است.',
                    409,
                    [
                        'variantId' => $variant->public_id,
                        'expectedUnitPriceToman' => $item['expectedUnitPriceToman'],
                        'currentUnitPriceToman' => $unitPrice,
                    ],
                );
            }

            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;
            $snapshots[] = [
                'productId' => $product->public_id,
                'variantId' => $variant->public_id,
                'productName' => $product->name,
                'variantName' => $variant->name,
                'productCode' => $product->product_code,
                'sku' => $variant->sku,
                'colorName' => $variant->color->name,
                'sizeName' => $variant->size->name,
                'unitPriceToman' => $unitPrice,
                'quantity' => $quantity,
                'lineTotalToman' => $lineTotal,
                'available' => $available,
            ];
        }

        $method = DeliveryMethod::from((string) $payload['deliveryMethod']);
        if ($method->requiresAddress()) {
            foreach (['province', 'city', 'address'] as $field) {
                if (blank($recipient[$field] ?? null)) {
                    throw ValidationException::withMessages(["customer.{$field}" => ['این مقدار برای ارسال سفارش الزامی است.']]);
                }
            }
        }
        $delivery = $this->delivery->quote($method, $recipient['province'] ?? null, $recipient['city'] ?? null, $subtotal);

        return [
            'items' => $snapshots,
            'recipient' => $recipient,
            'deliveryMethod' => $method->value,
            'deliveryZoneId' => $delivery['zone']?->getKey(),
            'deliveryZonePublicId' => $delivery['zone']?->public_id,
            'subtotalToman' => $subtotal,
            'deliveryFeeToman' => (int) $delivery['fee_toman'],
            'packagingFeeToman' => (int) $delivery['packaging_fee_toman'],
            'discountTotalToman' => 0,
            'grandTotalToman' => $subtotal + (int) $delivery['fee_toman'] + (int) $delivery['packaging_fee_toman'],
            'preparationMinDays' => (int) $delivery['preparation_min_days'],
            'preparationMaxDays' => max((int) $delivery['preparation_min_days'], (int) $delivery['preparation_max_days']),
            'currency' => 'TOMAN',
        ];
    }

    private function resolveRecipient(Customer $customer, array $payload): array
    {
        $addressId = trim((string) ($payload['addressId'] ?? ''));
        if ($addressId !== '') {
            $address = CustomerAddress::query()
                ->ownedBy($customer)
                ->where('public_id', $addressId)
                ->where('is_active', true)
                ->first();
            if (! $address) {
                throw new CommerceException(
                    CommerceErrorCode::ProductUnavailable,
                    'آدرس انتخاب‌شده معتبر یا متعلق به این حساب نیست.',
                    404,
                    ['addressId' => $addressId],
                );
            }

            return [
                'fullName' => $address->recipient_name,
                'mobile' => $address->mobile,
                'province' => $address->province,
                'city' => $address->city,
                'address' => $address->address_line,
                'postalCode' => $address->postal_code,
                'notes' => $this->nullableTrim(data_get($payload, 'customer.notes')),
            ];
        }

        $customerData = (array) ($payload['customer'] ?? []);
        $mobile = trim((string) ($customerData['mobile'] ?? ''));

        return [
            'fullName' => trim((string) ($customerData['fullName'] ?? '')),
            'mobile' => $mobile === '' ? '' : IranianMobile::normalize($mobile),
            'province' => $this->nullableTrim($customerData['province'] ?? null),
            'city' => $this->nullableTrim($customerData['city'] ?? null),
            'address' => $this->nullableTrim($customerData['address'] ?? null),
            'postalCode' => $this->nullableTrim($customerData['postalCode'] ?? null),
            'notes' => $this->nullableTrim($customerData['notes'] ?? null),
        ];
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
