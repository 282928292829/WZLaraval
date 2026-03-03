<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        foreach ($items as $i => $item) {
            $converted = [];
            foreach (['unit_price', 'commission', 'shipping', 'final_price'] as $key) {
                if (isset($item[$key]) && $item[$key] !== '' && $item[$key] !== null) {
                    $converted[$key] = to_english_digits((string) $item[$key]);
                }
            }
            if (! empty($converted)) {
                $items[$i] = array_merge($item, $converted);
            }
        }
        if (! empty($items)) {
            $this->merge(['items' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.commission' => ['nullable', 'numeric', 'min:0'],
            'items.*.shipping' => ['nullable', 'numeric', 'min:0'],
            'items.*.final_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.currency' => ['nullable', 'string', 'max:10'],
        ];
    }
}
