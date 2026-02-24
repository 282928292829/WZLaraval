<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orderId = (int) $this->route('id');

        return [
            'merge_with' => ['required', 'integer', Rule::notIn([$orderId]), 'exists:orders,id'],
        ];
    }
}
