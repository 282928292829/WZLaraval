@php
    $typeFilters = [
        'all'           => ['label' => __('inbox.all'),             'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
        'new_order'     => ['label' => __('New Order'),            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        'comment'       => ['label' => __('New Comment'),          'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        'payment'       => ['label' => __('Payment Received'),     'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
        'contact_form'  => ['label' => __('Contact Form'),         'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        'status_change' => ['label' => __('Status Changed'),       'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];

    $typeColors = [
        'new_order'     => ['bg' => 'bg-blue-50',   'ic' => 'text-blue-500'],
        'comment'       => ['bg' => 'bg-purple-50', 'ic' => 'text-purple-500'],
        'payment'       => ['bg' => 'bg-green-50',  'ic' => 'text-green-500'],
        'contact_form'  => ['bg' => 'bg-amber-50',  'ic' => 'text-amber-500'],
        'status_change' => ['bg' => 'bg-teal-50',   'ic' => 'text-teal-500'],
    ];
@endphp

<x-app-layout>

{{-- ── Page header ──────────────────────────────────────────────────────────── --}}
<div class="bg-white border-b border-gray-100">
    <div class="max-w-3xl mx-auto px-4 py-4 sm:py-5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="relative">
                <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                @if ($unreadCount > 0)
                    <span class="absolute -top-1 -end-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </div>
            <div class="min-w-0">
                <h1 class="text-base font-bold text-gray-900">{{ __('inbox.inbox') }}</h1>
                @if ($unreadCount > 0)
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ __('inbox.unread_count', ['count' => $unreadCount]) }}
                    </p>
                @else
                    <p class="text-xs text-gray-400 mt-0.5">{{ __('inbox.all_caught_up') }}</p>
                @endif
            </div>
        </div>

        {{-- Mark all read --}}
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('inbox.mark-all-read') }}">
                @csrf
                <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('inbox.mark_all_read') }}
                </button>
            </form>
        @endif
    </div>
</div>

<div class="max-w-3xl mx-auto px-4 py-5 sm:py-6 space-y-4">

    {{-- Flash --}}
    @if (session('status') === 'all-read')
        <div class="flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('inbox.all_read') }}
        </div>
    @endif

    {{-- ── Filter tabs ──────────────────────────────────────────────────────── --}}
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 scrollbar-hide">
        @foreach ($typeFilters as $type => $info)
            <a href="{{ route('inbox.index', $type !== 'all' ? ['type' => $type] : []) }}"
               class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-medium whitespace-nowrap transition-colors shrink-0
                      {{ $filter === $type
                          ? 'bg-primary-500 text-white shadow-sm'
                          : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-300' }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $info['icon'] }}"/>
                </svg>
                {{ $info['label'] }}
            </a>
        @endforeach
    </div>

    {{-- ── Activity list ────────────────────────────────────────────────────── --}}
    @if ($activities->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-14 text-center">
            <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500">{{ __('inbox.no_activity') }}</p>
            <p class="text-xs text-gray-400 mt-1">
                @if ($filter !== 'all')
                    <a href="{{ route('inbox.index') }}" class="text-primary-500 hover:underline">{{ __('inbox.view_all') }}</a>
                @else
                    {{ __('inbox.no_activity_hint') }}
                @endif
            </p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <ul class="divide-y divide-gray-50">
                @foreach ($activities as $activity)
                    @php
                        $colors = $typeColors[$activity->type] ?? ['bg' => 'bg-gray-50', 'ic' => 'text-gray-400'];
                        $isUnread = is_null($activity->read_at);
                        $hasLink  = $activity->subject_type === 'App\\Models\\Order' && $activity->subject_id;
                    @endphp

                    <li class="relative {{ $isUnread ? 'bg-primary-50/30' : '' }} hover:bg-gray-50 transition-colors">
                        {{-- Unread left border --}}
                        @if ($isUnread)
                            <span class="absolute start-0 top-0 bottom-0 w-0.5 bg-primary-400 rounded-e"></span>
                        @endif

                        <div class="flex items-start gap-3 px-5 py-4">
                            {{-- Type icon --}}
                            <div class="w-9 h-9 rounded-xl {{ $colors['bg'] }} flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 {{ $colors['ic'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ ($typeFilters[$activity->type] ?? $typeFilters['all'])['icon'] }}"/>
                                </svg>
                            </div>

                            {{-- Content --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm {{ $isUnread ? 'font-semibold text-gray-900' : 'font-medium text-gray-800' }}">
                                            {{ $activity->typeLabel() }}
                                            @if (!empty($activity->data['order_number']))
                                                <span class="text-primary-600 font-bold">#{{ $activity->data['order_number'] }}</span>
                                            @endif
                                        </p>

                                        @if ($activity->causer)
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                {{ $activity->causer->name }}
                                                @if ($activity->causer->email)
                                                    <span class="text-gray-400">· {{ $activity->causer->email }}</span>
                                                @endif
                                            </p>
                                        @else
                                            <p class="text-xs text-gray-400 mt-0.5">{{ __('System') }}</p>
                                        @endif

                                        @if (!empty($activity->data['note']))
                                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $activity->data['note'] }}</p>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        @if ($isUnread)
                                            <span class="w-2 h-2 rounded-full bg-primary-400 shrink-0 mt-1"></span>
                                        @endif
                                        <span class="text-xs text-gray-400 whitespace-nowrap">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Action buttons --}}
                                @if ($hasLink || $isUnread)
                                    <div class="flex items-center gap-3 mt-2.5">
                                        @if ($hasLink)
                                            <form method="POST" action="{{ route('inbox.mark-read', $activity) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                                    {{ __('inbox.view') }}
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="{{ app()->getLocale() === 'ar' ? 'M10 19l-7-7m0 0l7-7m-7 7h18' : 'M14 5l7 7m0 0l-7 7m7-7H3' }}"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        @if ($isUnread && !$hasLink)
                                            <form method="POST" action="{{ route('inbox.mark-read', $activity) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                                                    {{ __('inbox.mark_read') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Pagination --}}
            @if ($activities->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    @endif

</div>

</x-app-layout>
