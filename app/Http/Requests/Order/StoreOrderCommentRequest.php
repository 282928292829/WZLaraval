<?php

namespace App\Http\Requests\Order;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxFiles = (int) Setting::get('comment_max_files', 5);
        $maxFileKb = (int) Setting::get('comment_max_file_size_mb', 10) * 1024;

        return [
            'body' => ['required_without:files.0', 'nullable', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
            'template_id' => ['sometimes', 'nullable', 'integer', 'exists:comment_templates,id'],
            'files' => ['sometimes', 'array', 'max:'.$maxFiles],
            'files.*' => ['file', 'max:'.$maxFileKb, 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without' => __('orders.comment_body_required'),
        ];
    }
}
