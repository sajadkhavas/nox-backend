<?php

namespace App\Http\Requests;

use App\Enums\DeliveryMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CommerceCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    public function rules(): array
    {
        $saved = $this->filled('addressId');
        $requiresAddress = $this->input('deliveryMethod') !== DeliveryMethod::Pickup->value;

        return [
            'addressId' => ['nullable', 'string', 'size:26'],
            'customer' => [Rule::requiredIf(! $saved), 'nullable', 'array'],
            'customer.fullName' => [Rule::requiredIf(! $saved), 'nullable', 'string', 'min:2', 'max:120'],
            'customer.mobile' => [Rule::requiredIf(! $saved), 'nullable', 'string', 'max:32'],
            'customer.province' => [Rule::requiredIf(! $saved && $requiresAddress), 'nullable', 'string', 'max:100'],
            'customer.city' => [Rule::requiredIf(! $saved && $requiresAddress), 'nullable', 'string', 'max:100'],
            'customer.address' => [Rule::requiredIf(! $saved && $requiresAddress), 'nullable', 'string', 'max:1200'],
            'customer.postalCode' => ['nullable', 'string', 'max:20'],
            'customer.notes' => ['nullable', 'string', 'max:1000'],
            'deliveryMethod' => ['required', Rule::enum(DeliveryMethod::class)],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*' => ['required', 'array:variantId,quantity,expectedUnitPriceToman'],
            'items.*.variantId' => ['required', 'string', 'size:26'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.max(1, (int) config('lbb.checkout.max_quantity_per_line', 20))],
            'items.*.expectedUnitPriceToman' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $total = collect($this->input('items', []))->sum(fn (array $item): int => (int) ($item['quantity'] ?? 0));
            $max = max(1, (int) config('lbb.checkout.max_total_units', 50));
            if ($total > $max) {
                $validator->errors()->add('items', "تعداد کل اقلام نمی‌تواند بیشتر از {$max} باشد.");
            }
        }];
    }
}
