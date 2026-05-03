<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
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
            'product_group_id' => ['sometimes', 'required', 'integer', 'exists:product_groups,id'],
            'product_type_id' => ['sometimes', 'required', 'integer', 'exists:product_types,id'],
            'product_brand_id' => ['sometimes', 'nullable', 'integer', 'exists:product_brands,id'],
            'product_model_id' => ['sometimes', 'nullable', 'integer', 'exists:product_models,id'],
            'code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
