<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'in:bank_transfer,credit_card,cash,other'],
            'payment_receipts' => ['sometimes', 'array', 'max:5'],
            'payment_receipts.*' => ['file', 'mimes:'.allowed_upload_mimes(), 'max:10240'],
        ];
    }
}
