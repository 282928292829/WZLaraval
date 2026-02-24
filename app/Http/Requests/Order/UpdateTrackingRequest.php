<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_company' => ['nullable', 'string', 'max:50'],
        ];
    }
}
