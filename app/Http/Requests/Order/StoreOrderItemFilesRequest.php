<?php

namespace App\Http\Requests\Order;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderItemFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxPerItem = max(1, (int) Setting::get('max_files_per_item_after_submit', 5));
        $maxFileSize = max(1, (int) Setting::get('max_file_size_mb', 2)) * 1024;

        return [
            'files' => ['required', 'array', 'min:1', 'max:'.$maxPerItem],
            'files.*' => ['required', 'file', 'max:'.$maxFileSize, 'mimes:'.allowed_upload_mimes()],
        ];
    }

    public function messages(): array
    {
        $maxPerItem = max(1, (int) Setting::get('max_files_per_item_after_submit', 5));
        $maxFileSizeMb = max(1, (int) Setting::get('max_file_size_mb', 2));

        return [
            'files.required' => __('orders.item_files_required'),
            'files.min' => __('orders.item_files_required'),
            'files.max' => __('orders.item_file_limit_reached', ['max' => $maxPerItem]),
            'files.*.file' => __('orders.item_file_invalid'),
            'files.*.max' => __('orders.item_file_too_large', ['mb' => $maxFileSizeMb]),
            'files.*.mimes' => __('orders.item_file_type_invalid'),
        ];
    }

    public function attributes(): array
    {
        return [
            'files' => __('orders.files'),
        ];
    }
}
