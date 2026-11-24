<?php

namespace App\Http\Requests;

use App\Support\CustomerDetails;
use Illuminate\Foundation\Http\FormRequest;

class CustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('postalCode');
        if (is_string($value)) {
            $this->merge(['postalCode' => CustomerDetails::postalCode($value)]);
        }
    }

    public function messages(): array
    {
        return ['postalCode.required' => CustomerDetails::POSTAL_CODE_ERROR];
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
            'postalCode' => ['bail', 'required', 'string', static function (string $attribute, string $value, \Closure $fail): void {
                if (! CustomerDetails::validPostalCode($value)) {
                    $fail(CustomerDetails::POSTAL_CODE_ERROR);
                }
            }],
            'isDefault' => ['sometimes', 'boolean'],
        ];
    }
}
