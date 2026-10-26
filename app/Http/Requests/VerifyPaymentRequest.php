<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'authority' => trim((string) ($this->input('authority') ?? $this->input('Authority'))),
            'status' => trim((string) ($this->input('status') ?? $this->input('Status'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'authority' => ['required', 'string', 'max:128'],
            'status' => ['nullable', 'string', 'max:32'],
        ];
    }
}
