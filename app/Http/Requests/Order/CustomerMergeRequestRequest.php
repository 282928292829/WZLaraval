<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerMergeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orderId = (int) $this->route('id');

        return [
            'merge_with_order' => ['required', 'integer', Rule::notIn([$orderId])],
        ];
    }
}
