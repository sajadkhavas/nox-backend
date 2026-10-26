<?php

namespace App\Http\Requests\Api\V1;

class CatalogSearchRequest extends CatalogIndexRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'q' => ['required', 'string', 'min:1', 'max:120'],
        ];
    }
}
