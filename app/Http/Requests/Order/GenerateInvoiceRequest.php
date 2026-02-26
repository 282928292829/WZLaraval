<?php

namespace App\Http\Requests\Order;

use App\Enums\InvoiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = array_map(fn ($c) => $c->value, InvoiceType::cases());
        $isFirstPayment = $this->input('invoice_type') === InvoiceType::FirstPayment->value;
        $isPublish = $this->input('action') === 'publish';

        return [
            'action' => ['sometimes', 'in:preview,publish'],
            'invoice_type' => ['required', Rule::in($types)],
            'invoice_language' => ['nullable', 'string', Rule::in(['ar', 'en', 'both'])],
            'custom_filename' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_\-\s\.\(\)\{\}\:]+$/'],
            'custom_notes' => ['nullable', 'string', 'max:1000'],
            'comment_message' => ['nullable', 'string', 'max:2000'],
            'show_original_currency' => ['sometimes', 'boolean'],

            // First payment
            'first_items_total' => array_filter([
                $isFirstPayment && $isPublish ? 'required' : 'nullable',
                'numeric',
                'min:0',
                $isFirstPayment && $isPublish ? 'gt:0' : null,
            ]),
            'first_agent_fee' => ['nullable', 'numeric', 'min:0'],
            'first_commission_overridden' => ['sometimes', 'boolean'],
            'first_other_label' => ['nullable', 'string', 'max:200'],
            'first_other_amount' => ['nullable', 'numeric', 'min:0'],
            'first_total' => ['nullable', 'numeric', 'min:0'],
            'first_total_overridden' => ['sometimes', 'boolean'],
            'first_extras' => ['nullable', 'array'],
            'first_extras.*.label' => ['nullable', 'string', 'max:100'],
            'first_extras.*.amount' => ['nullable', 'numeric', 'min:0'],

            // Second/final
            'show_order_items' => ['sometimes', 'boolean'],
            'custom_lines' => ['nullable', 'array'],
            'custom_lines.*.label' => ['nullable', 'string', 'max:200'],
            'custom_lines.*.amount' => ['nullable', 'numeric', 'min:0'],
            'custom_lines.*.visible' => ['nullable', 'boolean'],
            'second_weight' => ['nullable', 'string', 'max:50'],
            'second_shipping_company' => ['nullable', 'string', 'max:100'],
            'second_product_value' => ['nullable', 'numeric', 'min:0'],
            'second_agent_fee' => ['nullable', 'numeric', 'min:0'],
            'second_shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'second_first_payment' => ['nullable', 'numeric', 'min:0'],
            'second_remaining' => ['nullable', 'numeric', 'min:0'],

            // Items cost
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.currency' => ['nullable', 'string', 'max:10'],

            // General
            'general_lines' => ['nullable', 'array'],
            'general_lines.*.label' => ['nullable', 'string', 'max:200'],
            'general_lines.*.amount' => ['nullable', 'numeric', 'min:0'],

            // Legacy / shared
            'custom_amount' => ['nullable', 'numeric', 'min:0'],
            'include_agent_fee' => ['sometimes', 'boolean'],
            'include_local_shipping' => ['sometimes', 'boolean'],
            'include_international_shipping' => ['sometimes', 'boolean'],
            'include_photo_fee' => ['sometimes', 'boolean'],
            'include_extra_packing' => ['sometimes', 'boolean'],
            'fee_agent_fee' => ['nullable', 'numeric', 'min:0'],
            'fee_local_shipping' => ['nullable', 'numeric', 'min:0'],
            'fee_international_shipping' => ['nullable', 'numeric', 'min:0'],
            'fee_photo_fee' => ['nullable', 'numeric', 'min:0'],
            'fee_extra_packing' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
