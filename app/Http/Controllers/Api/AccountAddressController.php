<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAddressRequest;
use App\Http\Resources\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Support\ApiResponse;
use App\Support\IranianMobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = CustomerAddress::query()
            ->ownedBy($request->user('customer'))
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        return ApiResponse::success(
            CustomerAddressResource::collection($addresses)->resolve($request),
        );
    }

    public function store(CustomerAddressRequest $request): JsonResponse
    {
        try {
            $address = DB::transaction(function () use ($request): CustomerAddress {
                $customer = $request->user('customer');
                $hasAddress = CustomerAddress::query()->ownedBy($customer)->where('is_active', true)->exists();
                $data = $this->mapInput($request->validated());
                $data['customer_id'] = $customer->getKey();
                $data['is_default'] = ! $hasAddress || (bool) ($data['is_default'] ?? false);
                $data['is_active'] = true;

                return CustomerAddress::query()->create($data);
            }, 3);
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error(
                'شماره موبایل آدرس معتبر نیست.',
                422,
                ['mobile' => [$exception->getMessage()]],
            );
        }

        return ApiResponse::success([
            'address' => (new CustomerAddressResource($address))->resolve($request),
        ], 'آدرس ذخیره شد.', 201);
    }

    public function update(CustomerAddressRequest $request, string $addressId): JsonResponse
    {
        try {
            $address = DB::transaction(function () use ($request, $addressId): CustomerAddress {
                $address = CustomerAddress::query()
                    ->ownedBy($request->user('customer'))
                    ->where('public_id', $addressId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();
                $address->update($this->mapInput($request->validated()));

                return $address->fresh();
            }, 3);
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error(
                'شماره موبایل آدرس معتبر نیست.',
                422,
                ['mobile' => [$exception->getMessage()]],
            );
        }

        return ApiResponse::success([
            'address' => (new CustomerAddressResource($address))->resolve($request),
        ], 'آدرس به‌روزرسانی شد.');
    }

    public function destroy(Request $request, string $addressId): JsonResponse
    {
        DB::transaction(function () use ($request, $addressId): void {
            $address = CustomerAddress::query()
                ->ownedBy($request->user('customer'))
                ->where('public_id', $addressId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = CustomerAddress::query()
                    ->ownedBy($request->user('customer'))
                    ->where('is_active', true)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();
                $next?->update(['is_default' => true]);
            }
        }, 3);

        return ApiResponse::success(message: 'آدرس حذف شد.');
    }

    private function mapInput(array $data): array
    {
        $mapped = [
            'title' => trim($data['title']),
            'recipient_name' => trim($data['recipientName']),
            'mobile' => IranianMobile::normalize($data['mobile']),
            'province' => trim($data['province']),
            'city' => trim($data['city']),
            'address_line' => trim($data['address']),
            'postal_code' => $this->nullableTrim($data['postalCode'] ?? null),
        ];

        if (array_key_exists('isDefault', $data)) {
            $mapped['is_default'] = (bool) $data['isDefault'];
        }

        return $mapped;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
