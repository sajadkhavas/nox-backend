<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:80'],
            'recipientName' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => ['required', 'string', 'max:32'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'min:5', 'max:1200'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'isDefault' => ['sometimes', 'boolean'],
        ];
    }
}
