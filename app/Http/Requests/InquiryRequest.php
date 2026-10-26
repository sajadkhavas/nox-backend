<?php

namespace App\Http\Requests;

use App\Enums\InquiryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(InquiryType::class)],
            'fullName' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'subject' => ['nullable', 'string', 'max:220'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'metadata' => ['nullable', 'array', 'max:20'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('mobile') && ! $this->filled('email')) {
                    $validator->errors()->add('mobile', 'شماره موبایل یا ایمیل را وارد کنید.');
                }
            },
        ];
    }
}
