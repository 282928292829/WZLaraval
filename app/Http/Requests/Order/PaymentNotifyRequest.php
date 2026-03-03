<?php

namespace App\Http\Requests\Order;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class PaymentNotifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('transfer_amount')) {
            $this->merge(['transfer_amount' => to_english_digits($this->input('transfer_amount'))]);
        }
    }

    public function rules(): array
    {
        $maxFiles = max(0, (int) Setting::get('payment_notify_order_max_files', 5));
        $maxFileKb = max(1, (int) Setting::get('comment_max_file_size_mb', 10)) * 1024;

        $rules = [
            'transfer_amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_bank' => ['required', 'string', 'max:100'],
            'transfer_notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($maxFiles > 0) {
            $rules['receipts'] = ['nullable', 'array', 'max:50'];
            $rules['receipts.*'] = ['file', 'max:'.$maxFileKb, 'mimes:'.allowed_upload_mimes()];
        }

        return $rules;
    }
}
