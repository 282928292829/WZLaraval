<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'in:preview,publish'],
            'custom_amount' => ['nullable', 'numeric', 'min:0'],
            'custom_notes' => ['nullable', 'string', 'max:1000'],
            'comment_message' => ['nullable', 'string', 'max:2000'],
            'invoice_type' => ['sometimes', 'in:detailed,simple'],
            'show_original_currency' => ['sometimes', 'boolean'],
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
