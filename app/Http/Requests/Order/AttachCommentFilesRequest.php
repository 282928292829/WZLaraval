<?php

namespace App\Http\Requests\Order;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class AttachCommentFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxFiles = (int) Setting::get('comment_max_files', 10);
        $maxFileKb = (int) Setting::get('comment_max_file_size_mb', 10) * 1024;

        return [
            'files' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'files.*' => ['required', 'file', 'max:'.$maxFileKb, 'mimes:'.allowed_upload_mimes()],
        ];
    }
}
