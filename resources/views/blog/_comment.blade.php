{{-- Recursive comment partial. $depth: 0 = top-level, 1 = reply --}}
<div id="comment-{{ $comment->id }}" class="flex gap-3 scroll-mt-24 {{ $depth > 0 ? 'ps-6 pt-4 border-s-2 border-gray-100' : '' }}">

    {{-- Avatar --}}
    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full shrink-0 text-xs font-semibold
                 {{ $depth > 0 ? 'bg-gray-100 text-gray-500' : 'bg-primary-100 text-primary-700' }}">
        {{ mb_substr($comment->getAuthorName(), 0, 1) }}
    </span>

    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap mb-1">
            <span class="text-sm font-semibold text-gray-900">{{ $comment->getAuthorName() }}</span>
            @if ($comment->user && $comment->user->hasAnyRole(['editor', 'admin', 'superadmin']))
                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600">
                    {{ __('blog.team') }}
                </span>
            @endif
            <time class="text-xs text-gray-400" datetime="{{ $comment->created_at->toIso8601String() }}">
                {{ $comment->created_at->diffForHumans() }}
            </time>
            @if ($comment->is_edited)
                <span class="text-xs text-gray-400 italic">({{ __('blog.edited') }})</span>
            @endif
        </div>

        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $comment->body }}</p>

        {{-- Reply button — only for top-level comments --}}
        @if ($depth === 0)
            <button type="button"
                    class="mt-2 text-xs text-gray-400 hover:text-primary-600 transition-colors flex items-center gap-1"
                    @click="replyTo = {{ $comment->id }}; $nextTick(() => document.getElementById('body').focus())">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                {{ __('blog.reply') }}
            </button>
        @endif

        {{-- Nested replies --}}
        @if ($comment->relationLoaded('replies') && $comment->replies->isNotEmpty())
            <div class="mt-4 space-y-4">
                @foreach ($comment->replies as $reply)
                    @include('blog._comment', ['comment' => $reply, 'depth' => 1])
                @endforeach
            </div>
        @endif
    </div>
</div>
