<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageComment;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $commentsEnabled = (bool) Setting::get('page_comments_enabled', true) && ($page->allow_comments ?? true);

        $comments = $commentsEnabled
            ? PageComment::query()
                ->approved()
                ->where('page_id', $page->id)
                ->whereNull('parent_id')
                ->with(['user', 'replies' => fn ($q) => $q->approved()->with('user')->orderBy('created_at')])
                ->orderBy('created_at')
                ->get()
            : collect();

        $viewData = compact('page', 'commentsEnabled', 'comments');

        // Use a dedicated template if one exists for this slug (e.g. pages/faq.blade.php)
        $dedicatedView = 'pages.'.str_replace('-', '_', $slug);

        if (view()->exists($dedicatedView)) {
            return view($dedicatedView, $viewData);
        }

        return view('pages.show', $viewData);
    }

    public function storeComment(Request $request, Page $page): RedirectResponse
    {
        if ($request->filled('website')) {
            abort(422, __('blog.comment_spam_rejected'));
        }

        if (! (bool) Setting::get('page_comments_enabled', true)) {
            abort(403, __('blog.comments_disabled'));
        }

        if (! ($page->allow_comments ?? true)) {
            abort(403, __('blog.comments_disabled'));
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'guest_name' => [Rule::requiredIf(! auth()->check()), 'nullable', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('page_comments', 'id')->where('page_id', $page->id),
            ],
        ]);

        PageComment::create([
            'page_id' => $page->id,
            'user_id' => auth()->id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'guest_name' => auth()->check() ? null : ($validated['guest_name'] ?? null),
            'guest_email' => auth()->check() ? null : ($validated['guest_email'] ?? null),
            'body' => $validated['body'],
            'status' => auth()->check() ? 'approved' : 'pending',
        ]);

        $message = auth()->check()
            ? __('blog.comment_posted')
            : __('blog.comment_pending_moderation');

        return redirect()
            ->to(route('pages.show', $page->slug).'#comments')
            ->with('status', $message);
    }
}
