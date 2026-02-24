<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PaymentNotifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_bank' => ['required', 'string', 'max:100'],
            'transfer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
