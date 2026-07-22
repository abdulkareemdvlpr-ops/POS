<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name'                 => 'required|string|max:255',
            'category_id'         => 'required|exists:categories,id',
            'supplier_id'         => 'nullable|exists:suppliers,id',
            'sku'                  => "nullable|string|max:100|unique:products,sku,{$productId}",
            'buy_price'           => 'required|numeric|min:0',
            'price'               => 'required|numeric|min:0',
            'stock'               => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'unit'                => 'nullable|string|max:20',
            'description'         => 'nullable|string|max:1000',
            'status'              => 'nullable|boolean',
            'image'               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
