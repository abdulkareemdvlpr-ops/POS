<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'            => 'nullable|exists:customers,id',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.qty'            => 'required|integer|min:1',
            'items.*.price'          => 'required|numeric|min:0',
            'discount'               => 'nullable|numeric|min:0',
            'discount_type'          => 'nullable|in:flat,percent',
            'tax'                    => 'nullable|numeric|min:0|max:100',
            'service_fee'            => 'nullable|numeric|min:0',
            'subtotal'               => 'required|numeric|min:0',
            'total'                  => 'required|numeric|min:0',
            'payment_method'         => 'required|in:cash,card,bank_transfer,credit',
            'status'                 => 'required|in:paid,pending,cancelled',
            'invoice_date'           => 'nullable|date',
            'notes'                  => 'nullable|string|max:1000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->items ?? [] as $i => $item) {
                if (empty($item['product_id'])) continue;

                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $qty = (int) ($item['qty'] ?? 0);

                if ($product->stock < $qty) {
                    $validator->errors()->add(
                        "items.$i.qty",
                        "Only {$product->stock} units of \"{$product->name}\" are in stock."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required'             => 'Please add at least one product.',
            'items.*.product_id.required' => 'Please select a product.',
            'items.*.qty.min'            => 'Quantity must be at least 1.',
            'items.*.price.numeric'      => 'Price must be a valid number.',
        ];
    }
}
