<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $validated = $request->validate([
            'fullName' => ['sometimes', 'nullable', 'string', 'min:2', 'max:120'],
            'email' => [
                'sometimes',
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'marketingConsent' => ['sometimes', 'boolean'],
        ]);

        $customer->fill(array_filter([
            'full_name' => array_key_exists('fullName', $validated)
                ? $validated['fullName']
                : null,
            'email' => array_key_exists('email', $validated)
                ? $validated['email']
                : null,
            'marketing_consent' => array_key_exists('marketingConsent', $validated)
                ? $validated['marketingConsent']
                : null,
        ], static fn (mixed $value): bool => $value !== null));

        if (array_key_exists('fullName', $validated) && $validated['fullName'] === null) {
            $customer->full_name = null;
        }

        if (array_key_exists('email', $validated) && $validated['email'] === null) {
            $customer->email = null;
        }

        $customer->save();

        return ApiResponse::success([
            'user' => (new CustomerResource($customer->fresh()))->resolve($request),
        ], 'اطلاعات حساب به‌روزرسانی شد.');
    }
}
