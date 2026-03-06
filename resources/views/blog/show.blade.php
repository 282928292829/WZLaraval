@php
    $postTitle = $post->getTitle();
    $postDesc = $post->getExcerpt() ?: (app()->getLocale() === 'ar' ? ($post->seo_description_ar ?? '') : ($post->seo_description_en ?? ''));
    $postCanonical = route('blog.show', $post->slug);
    $postOgImage = $post->featured_image
        ? url(\Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image))
        : (($defaultOg = \App\Models\Setting::get('seo_default_og_image'))
            ? url(\Illuminate\Support\Facades\Storage::disk('public')->url($defaultOg))
            : null);
    $postSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $postTitle,
        'description' => $postDesc,
        'url' => $postCanonical,
        'datePublished' => ($post->published_at ?? $post->created_at)->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? config('app.name')],
    ];
    if ($postOgImage) {
        $postSchema['image'] = $postOgImage;
    }
@endphp
<x-app-layout>
    <x-slot name="title">{{ $postTitle }}</x-slot>
    <x-slot name="description">{{ $postDesc }}</x-slot>
    <x-slot name="canonicalUrl">{{ $postCanonical }}</x-slot>
    <x-slot name="ogType">article</x-slot>
    @if($postOgImage)
    <x-slot name="ogImage">{{ $postOgImage }}</x-slot>
    @endif
    <x-slot name="ogImageAlt">{{ $postTitle }}</x-slot>
    <x-slot name="schema">@json($postSchema)</x-slot>

    @push('meta')
        <meta property="og:article:published_time" content="{{ ($post->published_at ?? $post->created_at)->toIso8601String() }}">
        <meta property="og:article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
        @if($post->author)
        <meta property="og:article:author" content="{{ $post->author->name }}">
        @endif
    @endpush

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="lg:grid lg:grid-cols-4 lg:gap-10">

            {{-- Main post --}}
            <article class="lg:col-span-3">

                {{-- Breadcrumb --}}
                <nav class="flex items-center gap-1.5 text-sm text-gray-400 mb-6 flex-wrap">
                    <a href="{{ route('blog.index') }}" class="hover:text-gray-600 transition-colors">{{ __('blog.blog') }}</a>
                    @if ($post->category)
                        <svg class="w-3.5 h-3.5 shrink-0 {{ __('blog_show.text') }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}"
                           class="hover:text-gray-600 transition-colors">{{ $post->category->getName() }}</a>
                    @endif
                    <svg class="w-3.5 h-3.5 shrink-0 {{ __('blog_show.text') }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-gray-600 truncate max-w-[200px]">{{ $post->getTitle() }}</span>
                </nav>

                {{-- Featured image or placeholder --}}
                @if ($post->featured_image)
                    <div class="rounded-2xl overflow-hidden mb-8 aspect-video bg-gray-100">
                        <img src="{{ Storage::url($post->featured_image) }}"
                             alt="{{ $post->getTitle() }}"
                             class="w-full h-full object-cover">
                    </div>
                @else
                    @php $ph = $post->getPlaceholderColors(); @endphp
                    <div class="rounded-2xl overflow-hidden mb-8 aspect-video flex items-center justify-center p-8 text-center" style="background-color: {{ $ph['bg'] }}; color: {{ $ph['text'] }};">
                        <span class="text-xl font-semibold">{{ $post->getTitle() }}</span>
                    </div>
                @endif

                {{-- Post header --}}
                <header class="mb-8">
                    @if ($post->category)
                        <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}"
                           class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700 hover:bg-primary-100 transition-colors mb-3">
                            {{ $post->category->getName() }}
                        </a>
                    @endif

                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-snug mb-4">
                        {{ $post->getTitle() }}
                    </h1>

                    <div class="flex items-center gap-3 text-sm text-gray-500 flex-wrap">
                        {{-- Author avatar --}}
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 text-primary-700 text-xs font-semibold">
                                {{ mb_substr($post->author?->name ?? 'W', 0, 1) }}
                            </span>
                            <span class="font-medium text-gray-700">{{ $post->author?->name ?? __('app.name') }}</span>
                        </div>
                        <span class="text-gray-300">·</span>
                        <time datetime="{{ $post->published_at?->toIso8601String() ?? $post->created_at->toIso8601String() }}">
                            {{ ($post->published_at ?? $post->created_at)->translatedFormat('j F Y') }}
                        </time>
                        @if ($commentsEnabled && $comments->count())
                            <span class="text-gray-300">·</span>
                            <a href="#comments" class="hover:text-gray-700 transition-colors flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                {{ $comments->count() }}
                            </a>
                        @endif
                    </div>
                </header>

                {{-- Post body --}}
                <div class="prose prose-gray max-w-none prose-headings:font-semibold prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl prose-pre:bg-gray-900 prose-pre:text-gray-100">
                    @php
                        $body = app()->getLocale() === 'ar' ? ($post->body_ar ?: $post->body_en) : ($post->body_en ?: $post->body_ar);
                    @endphp
                    {!! $body !!}
                </div>

                {{-- Share buttons --}}
                @php
                    $shareUrl = url()->current();
                    $shareTitle = $post->getTitle();
                    $shareText = $shareTitle . ' ' . $shareUrl;
                    $waUrl = 'https://wa.me/?text=' . rawurlencode($shareText);
                    $twitterUrl = 'https://twitter.com/intent/tweet?url=' . rawurlencode($shareUrl) . '&text=' . rawurlencode($shareTitle);
                @endphp
                <div class="mt-8 pt-6 border-t border-gray-100 flex flex-wrap items-center gap-3" x-data="{ copied: false, url: @js($shareUrl) }">
                    <span class="text-sm font-medium text-gray-500">{{ __('blog.share') }}</span>
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-green-100 hover:text-green-700 transition-colors" aria-label="{{ __('blog.share_whatsapp') }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        {{ __('blog.share_whatsapp') }}
                    </a>
                    <a href="{{ $twitterUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-sky-100 hover:text-sky-700 transition-colors" aria-label="{{ __('blog.share_twitter') }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        {{ __('blog.share_twitter') }}
                    </a>
                    <button type="button" @click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2000)" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors" :class="copied && 'bg-green-100 text-green-700'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span x-text="copied ? '{{ __('blog.copied') }}' : '{{ __('blog.copy_link') }}'"></span>
                    </button>
                </div>

                {{-- Divider and comments section --}}
                @if ($commentsEnabled)
                <hr class="my-10 border-gray-100">

                {{-- Status alert (only when comments visible) --}}
                @if (session('status'))
                    <div class="mb-6 p-4 rounded-xl text-sm {{ session('status') === __('blog.comment_posted') ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Comments section --}}
                <section id="comments" class="scroll-mt-20">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">
                        {{ $comments->count() > 0 ? __('blog.comments_count', ['count' => $comments->count()]) : __('blog.leave_a_comment') }}
                    </h2>

                    {{-- Comment list --}}
                    @if ($comments->isNotEmpty())
                        <div class="space-y-6 mb-10">
                            @foreach ($comments as $comment)
                                @include('blog._comment', ['comment' => $comment, 'depth' => 0])
                            @endforeach
                        </div>
                    @endif

                    {{-- Comment form --}}
                    <div class="bg-gray-50 rounded-2xl p-5 sm:p-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-5">
                            {{ __('blog.leave_a_comment') }}
                        </h3>

                        <form method="POST" action="{{ route('blog.comments.store', $post) }}"
                              x-data="{ replyTo: null }">
                            @csrf

                            {{-- Honeypot: hidden from users, bots fill it --}}
                            <div class="absolute -left-[9999px] top-0" aria-hidden="true">
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            {{-- Guest fields --}}
                            @guest
                                <div class="grid gap-4 sm:grid-cols-2 mb-4">
                                    <div>
                                        <x-input-label for="guest_name" :value="__('blog.your_name')" />
                                        <x-text-input id="guest_name" name="guest_name" type="text"
                                                      class="mt-1 block w-full"
                                                      :value="old('guest_name')"
                                                      required />
                                        <x-input-error :messages="$errors->get('guest_name')" class="mt-1.5" />
                                    </div>
                                    <div>
                                        <x-input-label for="guest_email" :value="__('blog.your_email_optional')" />
                                        <x-text-input id="guest_email" name="guest_email" type="email"
                                                      class="mt-1 block w-full"
                                                      :value="old('guest_email')" />
                                        <x-input-error :messages="$errors->get('guest_email')" class="mt-1.5" />
                                    </div>
                                </div>
                            @endguest

                            {{-- Hidden parent_id for reply --}}
                            <input type="hidden" name="parent_id" :value="replyTo">

                            {{-- Reply indicator --}}
                            <div x-show="replyTo" class="mb-3 flex items-center gap-2 text-sm text-primary-700 bg-primary-50 rounded-lg px-3 py-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                <span>{{ __('blog.replying_to_comment') }}</span>
                                <button type="button" @click="replyTo = null" class="ms-auto text-primary-600 hover:text-primary-800 font-medium text-xs">{{ __('blog.cancel_reply') }}</button>
                            </div>

                            <div>
                                <x-input-label for="body" :value="__('blog.comment')" />
                                <x-text-area id="body" name="body" rows="4"
                                             class="mt-1 block w-full rounded-xl resize-none"
                                             placeholder="{{ __('blog.comment_placeholder') }}"
                                             required
                                             maxlength="2000">{{ old('body') }}</x-text-area>
                                <x-input-error :messages="$errors->get('body')" class="mt-1.5" />
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-3">
                                @guest
                                    <p class="text-xs text-gray-400">{{ __('blog.comment_moderation_notice') }}</p>
                                @endguest
                                <div class="ms-auto">
                                    <x-primary-button>
                                        {{ __('blog.submit_comment') }}
                                    </x-primary-button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
                @endif

            </article>

            {{-- Sidebar --}}
            <aside class="hidden lg:block lg:col-span-1 space-y-6 mt-10 lg:mt-0">

                {{-- Related posts --}}
                @if ($relatedPosts->isNotEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">{{ __('blog.related_posts') }}</h3>
                        <div class="space-y-4">
                            @foreach ($relatedPosts as $related)
                                <a href="{{ route('blog.show', $related->slug) }}"
                                   class="group flex gap-3 items-start">
                                    @if ($related->featured_image)
                                        <img src="{{ Storage::url($related->featured_image) }}"
                                             alt="{{ $related->getTitle() }}"
                                             loading="lazy"
                                             class="w-14 h-14 rounded-lg object-cover shrink-0 bg-gray-100 group-hover:opacity-90 transition-opacity">
                                    @else
                                        @php $rph = $related->getPlaceholderColors(); @endphp
                                        <span class="w-14 h-14 rounded-lg shrink-0" style="background-color: {{ $rph['bg'] }};" title="{{ $related->getTitle() }}"></span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 group-hover:text-primary-600 transition-colors line-clamp-2 leading-snug">
                                            {{ $related->getTitle() }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ ($related->published_at ?? $related->created_at)->diffForHumans() }}
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Back to blog --}}
                <a href="{{ route('blog.index') }}"
                   class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors group px-1">
                    <svg class="w-4 h-4 {{ __('blog_show.text') }} group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('blog.back_to_blog') }}
                </a>

            </aside>

        </div>
    </div>

    {{-- Back to blog on mobile (below post) --}}
    <div class="lg:hidden border-t border-gray-100 bg-white">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <a href="{{ route('blog.index') }}"
               class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-4 h-4 {{ __('blog_show.text') }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                {{ __('blog.back_to_blog') }}
            </a>
        </div>
    </div>
</x-app-layout>
