<?php

namespace App\Http\Controllers;

use App\Models\OrderComment;
use App\Models\Setting;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderComment::query()
            ->with(['order:id,order_number,user_id,status', 'user:id,name', 'files'])
            ->when(auth()->user()?->isStaffOrAbove(), fn ($q) => $q->withTrashed());

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($internal = $request->get('internal')) {
            if ($internal === '1') {
                $query->where('is_internal', true);
            } elseif ($internal === '0') {
                $query->where('is_internal', false);
            }
        }

        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy('created_at', $sort);

        $perPage = in_array((int) $request->get('per_page', 25), [10, 25, 50, 100], true)
            ? (int) $request->get('per_page', 25)
            : 25;

        $comments = $query->paginate($perPage)->withQueryString();

        $formAction = route('comments.index');
        $clearFiltersUrl = route('comments.index');
        $maxFilesPerComment = (int) Setting::get('comment_max_files', 10);
        $maxFileSizeMb = (int) Setting::get('comment_max_file_size_mb', 10);
        $acceptFileTypes = implode(',', array_map(fn (string $ext): string => '.'.$ext, explode(',', allowed_upload_mimes())));

        return view('comments.index', compact(
            'comments',
            'formAction',
            'clearFiltersUrl',
            'maxFilesPerComment',
            'maxFileSizeMb',
            'acceptFileTypes'
        ));
    }
}
