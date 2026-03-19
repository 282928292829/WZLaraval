<x-app-layout :minimal-footer="true">

@php $filterKeys = ['search','internal','sort','per_page','order_status','date_range','awaiting','no_response_preset','no_response_value','no_response_unit','unread']; @endphp
<div x-data="{}" @keydown.escape.window="$store.commentExpanded.id = null">
<div x-data="{ open: {{ request()->hasAny($filterKeys) ? 'true' : 'false' }}, customNoResponse: {{ request('no_response_preset') === 'custom' ? 'true' : 'false' }} }"
     x-init="try { var s = localStorage.getItem('wasetzon_comments_filter_open'); if (s !== null && !{{ request()->hasAny($filterKeys) ? 'true' : 'false' }}) open = s === 'true'; } catch(e){}; $watch('open', function(v){ try { localStorage.setItem('wasetzon_comments_filter_open', v); } catch(e){} })">
{{-- Merged header: one row — title + count | clear + filter toggle — flex-wrap on mobile --}}
<div class="bg-white border-b border-gray-100">
    <div class="max-w-4xl mx-auto px-4 py-3 sm:py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-primary-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div class="flex flex-col gap-0.5">
                    <h1 class="text-base font-bold text-gray-900">{{ __('comments.title') }}</h1>
                    <span class="text-xs text-gray-500 font-normal flex items-center gap-1.5">
                        {{ __('comments.unread') }}: {{ $unreadCount }}
                        @if ($unreadCount > 0)
                            <form method="POST" action="{{ route('comments.mark-all-read') }}?{{ http_build_query(request()->query()) }}" class="inline" x-data="{ submitting: false }" @submit="submitting = true">
                                @csrf
                                <button type="submit" :disabled="submitting"
                                        class="inline-flex p-0.5 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-1 focus:ring-gray-300"
                                        title="{{ __('comments.mark_all_read') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if (request()->hasAny($filterKeys))
                    <a href="{{ route('comments.index') }}?cleared=1" class="text-xs font-medium text-primary-500 hover:text-primary-600">{{ __('orders.filter_clear') }}</a>
                @endif
                <button type="button" @click="open = !open"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded-lg transition-colors">
                    <template x-if="open"><span>{{ __('orders.filter_hide') }}</span></template>
                    <template x-if="!open"><span>{{ __('orders.filter_show') }}</span></template>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-5 sm:py-6 space-y-4">

    {{-- localStorage: restore/clear/save filter state (runs on load) --}}
    <script>
    (function() {
        var KEY_OPEN = 'wasetzon_comments_filter_open';
        var KEY_FILTERS = 'wasetzon_comments_last_filters';
        var FILTER_KEYS = ['search','internal','sort','per_page','order_status','date_range','awaiting','no_response_preset','no_response_value','no_response_unit','unread'];
        try {
            var params = new URLSearchParams(window.location.search);
            if (params.has('cleared')) {
                localStorage.removeItem(KEY_FILTERS);
                params.delete('cleared');
                var qs = params.toString();
                var url = window.location.pathname + (qs ? '?' + qs : '');
                window.history.replaceState({}, '', url);
            } else {
                var hasFilters = FILTER_KEYS.some(function(k) { return params.has(k); });
                if (hasFilters) {
                    var obj = {};
                    FILTER_KEYS.forEach(function(k) {
                        if (params.has(k)) obj[k] = params.get(k);
                    });
                    localStorage.setItem(KEY_FILTERS, JSON.stringify(obj));
                } else {
                    var stored = localStorage.getItem(KEY_FILTERS);
                    if (stored) {
                        var parsed = JSON.parse(stored);
                        var qs = Object.keys(parsed).map(function(k) { return encodeURIComponent(k) + '=' + encodeURIComponent(parsed[k]); }).join('&');
                        if (qs) window.location.replace(window.location.pathname + '?' + qs);
                    }
                }
            }
        } catch (e) {}
    })();
    </script>

    {{-- Filter form (toggle in merged header above) --}}
    <form method="GET" action="{{ $formAction }}" x-show="open" x-cloak
          class="mt-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('orders.search_label') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="{{ __('comments.search_placeholder') }}"
                           class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('comments.internal_filter') }}</label>
                    <select name="internal" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                        <option value="" @selected(request('internal') === null)>{{ __('comments.internal_all') }}</option>
                        <option value="1" @selected(request('internal') === '1')>{{ __('comments.internal_only') }}</option>
                        <option value="0" @selected(request('internal') === '0')>{{ __('comments.internal_exclude') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('comments.read_filter') }}</label>
                    <select name="unread" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                        <option value="" @selected(request('unread') !== '1')>{{ __('comments.read_all') }}</option>
                        <option value="1" @selected(request('unread') === '1')>{{ __('comments.unread_only') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('comments.order_status_filter') }}</label>
                    <select name="order_status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                        <option value="">{{ __('orders.all_statuses') }}</option>
                        @foreach ($statuses ?? [] as $value => $label)
                            <option value="{{ $value }}" @selected(request('order_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('comments.date_range_filter') }}</label>
                    <select name="date_range" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                        <option value="" @selected(request('date_range') === null)>{{ __('comments.date_range_all') }}</option>
                        <option value="today" @selected(request('date_range') === 'today')>{{ __('comments.date_range_today') }}</option>
                        <option value="7days" @selected(request('date_range') === '7days')>{{ __('comments.date_range_7days') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('comments.awaiting_response') }}</label>
                    <select name="awaiting" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                        <option value="">{{ __('comments.awaiting_none') }}</option>
                        <option value="customer" @selected(request('awaiting') === 'customer')>{{ __('comments.awaiting_customer') }}</option>
                        <option value="staff" @selected(request('awaiting') === 'staff')>{{ __('comments.awaiting_staff') }}</option>
                        <option value="staff_public" @selected(request('awaiting') === 'staff_public')>{{ __('comments.awaiting_staff_public') }}</option>
                        <option value="staff_internal" @selected(request('awaiting') === 'staff_internal')>{{ __('comments.awaiting_staff_internal') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('comments.no_response_for') }}</label>
                    <select name="no_response_preset" @change="customNoResponse = ($event.target.value === 'custom')"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                        <option value="">{{ __('comments.no_response_any') }}</option>
                        @foreach (['4h','8h','24h','1d','2d','7d'] as $k)
                            <option value="{{ $k }}" @selected(request('no_response_preset') === $k)>{{ __('comments.no_response_' . $k) }}</option>
                        @endforeach
                        <option value="custom" @selected(request('no_response_preset') === 'custom')>{{ __('comments.no_response_custom') }}</option>
                    </select>
                </div>
                <div x-show="customNoResponse" x-cloak class="sm:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('comments.no_response_value') }}</label>
                        <input type="number" name="no_response_value" min="1" max="99" value="{{ request('no_response_value', 1) }}"
                               class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('comments.no_response_unit') }}</label>
                        <select name="no_response_unit" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                            <option value="hours" @selected(request('no_response_unit') === 'hours')>{{ __('comments.unit_hours') }}</option>
                            <option value="days" @selected(request('no_response_unit') === 'days')>{{ __('comments.unit_days') }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('orders.sort_label') }}</label>
                    <select name="sort" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                        <option value="desc" @selected(request('sort', 'desc') === 'desc')>{{ __('orders.sort_newest') }}</option>
                        <option value="asc" @selected(request('sort') === 'asc')>{{ __('orders.sort_oldest') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('orders.per_page_label') }}</label>
                    <select name="per_page" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                        <option value="10"  @selected(request('per_page', '25') === '10')>10</option>
                        <option value="25"  @selected(request('per_page', '25') === '25')>25</option>
                        <option value="50"  @selected(request('per_page') === '50')>50</option>
                        <option value="100" @selected(request('per_page') === '100')>100</option>
                    </select>
                </div>
            </div>
            <div class="px-4 pb-4 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold rounded-lg transition-colors">{{ __('orders.filter_apply') }}</button>
                <a href="{{ route('comments.index') }}?cleared=1" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-semibold rounded-lg transition-colors">{{ __('orders.filter_reset') }}</a>
            </div>
        </form>

    {{-- Comments list --}}
    @if ($comments->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16 px-6">
            <div class="w-16 h-16 mx-auto rounded-full bg-gray-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <h2 class="text-base font-semibold text-gray-700">{{ __('comments.no_comments') }}</h2>
            <p class="mt-1.5 text-sm text-gray-400 max-w-xs mx-auto">{{ __('inbox.no_activity_hint') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($comments as $comment)
                @php $canEdit = !$comment->trashed() && $comment->canBeEditedBy(auth()->user()); @endphp
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden transition-colors {{ $comment->trashed() ? 'opacity-60' : '' }}"
                     :class="{ 'ring-2 ring-primary-200 ring-offset-2': $store.commentExpanded.id === {{ $comment->id }} }"
                     x-data="commentCard({
                         id: {{ $comment->id }},
                         body: @js($comment->body),
                         orderId: {{ $comment->order_id }},
                         orderSlug: @js($comment->order?->order_number ?? (string) $comment->order_id),
                         orderNumber: @js($comment->order?->order_number ?? '#' . $comment->order_id),
                         author: @js($comment->user?->name ?? __('System')),
                         createdAt: @js(__('comments.date') . ': ' . $comment->created_at->format('d/m/Y') . ' ' . __('comments.hour') . ': ' . $comment->created_at->format('H:i')),
                         isoCreatedAt: @js($comment->created_at->toIso8601String()),
                         isInternal: {{ $comment->is_internal ? 'true' : 'false' }},
                         trashed: {{ $comment->trashed() ? 'true' : 'false' }},
                         canEdit: {{ $canEdit ? 'true' : 'false' }},
                         markReadUrl: @js(route('orders.comments.mark-read', $comment->order)),
                         updateUrl: @js(route('orders.comments.update', [$comment->order_id, $comment->id])),
                         bodyRequired: @js(__('orders.comment_body_required')),
                         editSuccess: @js(__('comments.edit_success')),
                         editError: @js(__('comments.edit_error')),
                         savingText: @js(__('comments.saving')),
                         files: @js($comment->files->map(fn ($f) => ['id' => $f->id, 'url' => $f->url(), 'original_name' => $f->original_name, 'size' => $f->size, 'human_size' => $f->humanSize(), 'is_image' => $f->isImage()])->values()->all()),
                         attachUrl: @js(route('orders.comments.attach-files', [$comment->order_id, $comment->id])),
                         maxFiles: {{ $maxFilesPerComment }},
                         maxFileSizeMb: {{ $maxFileSizeMb }},
                         acceptFileTypes: @js($acceptFileTypes),
                         attachSuccess: @js(__('comments.file_attached')),
                         attachError: @js(__('comments.edit_error')),
                         attachLimitExceeded: @js(__('comments.attach_limit_exceeded'))
                     })">
                    <div class="p-4 cursor-pointer hover:bg-gray-50/50 transition-colors" @click="toggle()">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 min-w-0">
                                @php $orderSlug = $comment->order?->order_number ?? $comment->order_id; @endphp
                                <a href="{{ route('orders.show', $orderSlug) }}" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-600 hover:text-primary-700 shrink-0" @click.stop>
                                    {{ $comment->order?->order_number ?? '#' . $comment->order_id }}
                                </a>
                                <span class="shrink-0">·</span>
                                <span x-text="author" class="shrink-0"></span>
                                <span class="shrink-0">·</span>
                                <time :datetime="isoCreatedAt" x-text="createdAt" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="shrink-0"></time>
                                @if ($comment->is_internal)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 shrink-0">{{ __('orders.internal_note') }}</span>
                                @endif
                                @if ($comment->trashed())
                                    <span class="text-red-500 shrink-0">{{ __('Deleted') }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0" @click.stop>
                                <a href="{{ route('orders.show', $orderSlug) }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    {{ __('comments.open_order') }}
                                </a>
                                <a href="{{ route('orders.show', $orderSlug) }}#comment-{{ $comment->id }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    {{ __('comments.open_comment') }}
                                </a>
                            </div>
                        </div>
                        <p class="text-sm text-gray-800 leading-relaxed line-clamp-3 text-start w-full" dir="auto" x-text="body"></p>
                    </div>

                    {{-- Inline expanded: full body + edit --}}
                    <div x-show="$store.commentExpanded.id === id"
                         x-collapse
                         x-cloak
                         class="border-t border-gray-100">
                        <div class="px-4 py-4 bg-gray-50/50">
                            <div x-show="!editing">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 mb-3">
                                    <a href="{{ route('orders.show', $orderSlug) }}" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-600 hover:text-primary-700" x-text="orderNumber"></a>
                                    <span>·</span>
                                    <span x-text="author"></span>
                                    <span>·</span>
                                    <time :datetime="isoCreatedAt" x-text="createdAt" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"></time>
                                </div>
                                <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap" dir="auto" x-text="body"></p>

                                {{-- Attachments --}}
                                <template x-if="files.length > 0 || (canEdit && files.length < maxFiles)">
                                    <div class="mt-4 space-y-2">
                                        <p class="text-xs font-semibold text-gray-600" x-show="files.length > 0" x-text="files.length ? '{{ __('comments.attachments') }}' : ''"></p>
                                        <div class="flex flex-wrap gap-2" x-show="files.length > 0">
                                            <template x-for="f in files" :key="f.id">
                                                <a :href="f.url" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                                    <template x-if="f.is_image">
                                                        <img :src="f.url" :alt="f.original_name" class="w-5 h-5 object-cover rounded">
                                                    </template>
                                                    <template x-if="!f.is_image">
                                                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </template>
                                                    <span class="truncate max-w-[140px]" x-text="f.original_name"></span>
                                                    <span class="text-gray-400 text-[10px]" x-text="f.human_size"></span>
                                                </a>
                                            </template>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2" x-show="canEdit && files.length < maxFiles">
                                            <input type="file" x-ref="attachInput" class="hidden" multiple
                                                   :accept="acceptFileTypes"
                                                   @change="onAttachInputChange($event)">
                                            <button type="button" @click.stop="triggerAttach()" :disabled="attaching"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a3 3 0 104.243 4.243l2.872-2.876"/>
                                                </svg>
                                                <span x-show="!attaching">{{ __('comments.attach_files') }}</span>
                                                <span x-show="attaching">{{ __('comments.saving') }}</span>
                                            </button>
                                            <span class="text-[10px] text-gray-400" x-text="'{{ __('comments.attach_limit') }}'.replace(':max', maxFiles).replace(':size', maxFileSizeMb)"></span>
                                            <p x-show="attachErrorMsg" x-text="attachErrorMsg" class="text-xs text-red-600 w-full"></p>
                                        </div>
                                    </div>
                                </template>

                                <div class="flex flex-wrap gap-2 mt-4">
                                    <a href="{{ route('orders.show', $orderSlug) }}" target="_blank" rel="noopener noreferrer" @click.stop
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        {{ __('comments.open_order') }}
                                    </a>
                                    <a href="{{ route('orders.show', $orderSlug) }}#comment-{{ $comment->id }}" target="_blank" rel="noopener noreferrer" @click.stop
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        {{ __('comments.open_comment') }}
                                    </a>
                                    <template x-if="canEdit">
                                        <button type="button" @click.stop="startEdit()"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            {{ __('comments.edit') }}
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div x-show="editing" x-cloak style="display: none;">
                                <textarea x-model="editBody" rows="8" dir="auto"
                                          class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none resize-y min-h-[120px]"
                                          :placeholder="bodyRequired"></textarea>
                                <p x-show="editErrorMsg" x-text="editErrorMsg" class="mt-2 text-sm text-red-600"></p>
                                <div class="flex gap-2 mt-4">
                                    <button type="button" @click.stop="saveEdit()" :disabled="saving"
                                            class="px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white text-sm font-semibold rounded-lg transition-colors">
                                        <span x-show="!saving">{{ __('comments.save') }}</span>
                                        <span x-show="saving" x-text="savingText"></span>
                                    </button>
                                    <button type="button" @click.stop="cancelEdit()"
                                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-semibold rounded-lg transition-colors">
                                        {{ __('comments.cancel') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($comments->hasPages())
            <div class="pt-4">
                {{ $comments->links() }}
            </div>
        @endif
    @endif

</div>
</div>
</div>
</x-app-layout>
