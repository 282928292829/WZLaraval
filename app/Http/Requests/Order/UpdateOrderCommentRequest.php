<?php

namespace App\Http\Requests\Order;

use App\Models\OrderComment;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'body' => ['required', 'string', 'max:5000'],
        ];

        $maxFiles = (int) Setting::get('comment_max_files', 10);
        $maxFileKb = (int) Setting::get('comment_max_file_size_mb', 10) * 1024;

        $commentId = $this->route('commentId');
        $existingCount = 0;
        if ($commentId) {
            $comment = OrderComment::find($commentId);
            if ($comment) {
                $existingCount = $comment->files()->count();
            }
        }
        $remainingSlots = max(0, $maxFiles - $existingCount);

        if ($remainingSlots > 0) {
            $rules['files'] = ['sometimes', 'array', 'max:'.$remainingSlots];
            $rules['files.*'] = ['file', 'max:'.$maxFileKb, 'mimes:'.allowed_upload_mimes()];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'body.required' => __('orders.comment_body_required'),
        ];
    }
}
