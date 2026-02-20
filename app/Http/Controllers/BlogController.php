<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $category = null;

        $query = Post::query()
            ->published()
            ->with(['author', 'category'])
            ->orderByDesc('published_at');

        if ($categorySlug) {
            $category = PostCategory::where('slug', $categorySlug)->firstOrFail();
            $query->where('post_category_id', $category->id);
        }

        $posts = $query->paginate(12)->withQueryString();
        $categories = PostCategory::has('posts')->orderBy('sort_order')->get();

        return view('blog.index', compact('posts', 'categories', 'category'));
    }

    public function show(string $slug): View
    {
        $post = Post::query()
            ->published()
            ->with(['author', 'category'])
            ->where('slug', $slug)
            ->firstOrFail();

        $comments = PostComment::query()
            ->approved()
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with(['user', 'replies' => function ($q) {
                $q->approved()->with('user')->orderBy('created_at');
            }])
            ->orderBy('created_at')
            ->get();

        $relatedPosts = Post::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->when($post->post_category_id, fn ($q) => $q->where('post_category_id', $post->post_category_id))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('blog.show', compact('post', 'comments', 'relatedPosts'));
    }

    public function storeComment(Request $request, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'guest_name' => ['required_unless:user_id,null', 'nullable', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:post_comments,id'],
        ]);

        PostComment::create([
            'post_id' => $post->id,
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
            ->route('blog.show', $post->slug)
            ->with('status', $message)
            ->with('hash', 'comments');
    }
}
