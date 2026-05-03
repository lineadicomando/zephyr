<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_group_id' => ['required', 'integer', 'exists:product_groups,id'],
            'product_type_id' => ['required', 'integer', 'exists:product_types,id'],
            'product_brand_id' => ['nullable', 'integer', 'exists:product_brands,id'],
            'product_model_id' => ['nullable', 'integer', 'exists:product_models,id'],
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }
}
