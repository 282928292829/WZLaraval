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
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:10240'],
        ];
    }
}
