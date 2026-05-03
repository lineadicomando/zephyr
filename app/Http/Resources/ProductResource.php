<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_group_id' => $this->product_group_id,
            'product_type_id' => $this->product_type_id,
            'product_brand_id' => $this->product_brand_id,
            'product_model_id' => $this->product_model_id,
            'code' => $this->code,
            'name' => $this->name,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
