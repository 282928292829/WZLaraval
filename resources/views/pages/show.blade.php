<x-app-layout :minimal-footer="true">
    @include('components.page-seo-slots', ['page' => $page])
    @php
        $body = app()->getLocale() === 'ar'
            ? ($page->body_ar ?: $page->body_en)
            : ($page->body_en ?: $page->body_ar);
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        <article class="bg-white">
            <header class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-snug">
                    {{ $page->getTitle() }}
                </h1>
            </header>

            <div class="prose prose-gray max-w-none prose-headings:font-semibold prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl">
                {!! $body !!}
            </div>
        </article>

        @if ($commentsEnabled)
        <hr class="my-10 border-gray-100">

        @if (session('status'))
            <div class="mb-6 p-4 rounded-xl text-sm {{ session('status') === __('blog.comment_posted') ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                {{ session('status') }}
            </div>
        @endif

        <section id="comments" class="scroll-mt-20">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                {{ $comments->count() > 0 ? __('blog.comments_count', ['count' => $comments->count()]) : __('blog.leave_a_comment') }}
            </h2>

            @if ($comments->isNotEmpty())
                <div class="space-y-6 mb-10">
                    @foreach ($comments as $comment)
                        @include('pages._comment', ['comment' => $comment, 'depth' => 0])
                    @endforeach
                </div>
            @endif

            <div class="bg-gray-50 rounded-2xl p-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-5">
                    {{ __('blog.leave_a_comment') }}
                </h3>

                <form method="POST" action="{{ route('pages.comments.store', $page) }}"
                      x-data="{ replyTo: null }">
                    @csrf

                    <div class="absolute -left-[9999px] top-0" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

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

                    <input type="hidden" name="parent_id" :value="replyTo">

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

    </div>
</x-app-layout>
