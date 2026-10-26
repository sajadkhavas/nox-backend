<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    public function rules(): array
    {
        return [
            'orderItemId' => ['required', 'string', 'size:26'],
            'destinationVariantId' => ['required', 'string', 'size:26'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
