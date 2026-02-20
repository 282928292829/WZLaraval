<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('My Orders') }}</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    @if ($orders->isEmpty())
                        {{ __('No orders yet') }}
                    @else
                        {{ $orders->count() }} {{ __('order(s)') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('new-order') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Order') }}
            </a>
        </div>
    </x-slot>

    @if ($orders->isEmpty())
        {{-- ── Full page empty state ──────────────────────────────────────────── --}}
        <div class="flex flex-col items-center justify-center py-24 px-6 text-center">
            <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-700">{{ __('No orders yet') }}</h2>
            <p class="mt-2 text-sm text-gray-400 max-w-xs leading-relaxed">
                {{ __('Your orders will appear here once you place one.') }}
            </p>
            <a href="{{ route('new-order') }}"
               class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Place your first order') }}
            </a>
        </div>

    @else
        {{-- ── Kanban board ───────────────────────────────────────────────────── --}}
        @php
            $needsActionCount = $groups['needs_action']->count();
            $inProgressCount  = $groups['in_progress']->count();
            $completedCount   = $groups['completed']->count();

            // Default active tab: needs_action if has items, otherwise in_progress
            $defaultTab = $needsActionCount > 0 ? 'needs_action' : 'in_progress';
        @endphp

        <div x-data="{ tab: '{{ $defaultTab }}' }">

            {{-- ── Mobile: Tab bar ───────────────────────────────────────────── --}}
            <div class="sm:hidden sticky top-0 z-20 bg-white border-b border-gray-100 shadow-sm">
                <div class="flex">
                    {{-- Needs Action --}}
                    <button type="button"
                            @click="tab = 'needs_action'"
                            :class="tab === 'needs_action'
                                ? 'border-b-2 border-primary-500 text-primary-600 bg-primary-50/50'
                                : 'border-b-2 border-transparent text-gray-500'"
                            class="flex-1 flex items-center justify-center gap-1.5 px-2 py-3 text-xs font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>{{ __('Needs Action') }}</span>
                        @if ($needsActionCount)
                            <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold bg-red-500 text-white">
                                {{ $needsActionCount }}
                            </span>
                        @endif
                    </button>

                    {{-- In Progress --}}
                    <button type="button"
                            @click="tab = 'in_progress'"
                            :class="tab === 'in_progress'
                                ? 'border-b-2 border-primary-500 text-primary-600 bg-primary-50/50'
                                : 'border-b-2 border-transparent text-gray-500'"
                            class="flex-1 flex items-center justify-center gap-1.5 px-2 py-3 text-xs font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>{{ __('In Progress') }}</span>
                        @if ($inProgressCount)
                            <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">
                                {{ $inProgressCount }}
                            </span>
                        @endif
                    </button>

                    {{-- Completed --}}
                    <button type="button"
                            @click="tab = 'completed'"
                            :class="tab === 'completed'
                                ? 'border-b-2 border-primary-500 text-primary-600 bg-primary-50/50'
                                : 'border-b-2 border-transparent text-gray-500'"
                            class="flex-1 flex items-center justify-center gap-1.5 px-2 py-3 text-xs font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ __('Completed') }}</span>
                        @if ($completedCount)
                            <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500">
                                {{ $completedCount }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- ── Mobile: Tab content panels ────────────────────────────────── --}}
            <div class="sm:hidden">

                {{-- Needs Action panel --}}
                <div x-show="tab === 'needs_action'" x-cloak>
                    @if ($groups['needs_action']->isEmpty())
                        @include('orders._empty-group', ['icon' => 'check', 'message' => __('No orders need your attention right now.')])
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach ($groups['needs_action'] as $order)
                                @include('orders._card', ['order' => $order, 'highlight' => true])
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- In Progress panel --}}
                <div x-show="tab === 'in_progress'" x-cloak>
                    @if ($groups['in_progress']->isEmpty())
                        @include('orders._empty-group', ['icon' => 'clock', 'message' => __('No orders currently in progress.')])
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach ($groups['in_progress'] as $order)
                                @include('orders._card', ['order' => $order, 'highlight' => false])
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Completed panel --}}
                <div x-show="tab === 'completed'" x-cloak>
                    @if ($groups['completed']->isEmpty())
                        @include('orders._empty-group', ['icon' => 'archive', 'message' => __('No completed orders yet.')])
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach ($groups['completed'] as $order)
                                @include('orders._card', ['order' => $order, 'highlight' => false, 'muted' => true])
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- ── Desktop: 3-column Kanban ───────────────────────────────────── --}}
            <div class="hidden sm:block py-6 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-3 gap-5 items-start">

                    {{-- Column: Needs Action --}}
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 px-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-400 shrink-0"></span>
                            <h2 class="text-sm font-semibold text-gray-700">{{ __('Needs Action') }}</h2>
                            <span class="ms-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold
                                {{ $needsActionCount ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-400' }}">
                                {{ $needsActionCount }}
                            </span>
                        </div>

                        @if ($groups['needs_action']->isEmpty())
                            <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50/60 flex flex-col items-center justify-center py-10 px-4 text-center">
                                <svg class="w-7 h-7 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <p class="text-xs text-gray-400">{{ __('All clear!') }}</p>
                            </div>
                        @else
                            @foreach ($groups['needs_action'] as $order)
                                @include('orders._card', ['order' => $order, 'highlight' => true, 'desktop' => true])
                            @endforeach
                        @endif
                    </div>

                    {{-- Column: In Progress --}}
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 px-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-400 shrink-0"></span>
                            <h2 class="text-sm font-semibold text-gray-700">{{ __('In Progress') }}</h2>
                            <span class="ms-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold
                                {{ $inProgressCount ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }}">
                                {{ $inProgressCount }}
                            </span>
                        </div>

                        @if ($groups['in_progress']->isEmpty())
                            <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50/60 flex flex-col items-center justify-center py-10 px-4 text-center">
                                <svg class="w-7 h-7 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs text-gray-400">{{ __('orders.no_orders_in_group') }}</p>
                            </div>
                        @else
                            @foreach ($groups['in_progress'] as $order)
                                @include('orders._card', ['order' => $order, 'highlight' => false, 'desktop' => true])
                            @endforeach
                        @endif
                    </div>

                    {{-- Column: Completed --}}
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 px-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-400 shrink-0"></span>
                            <h2 class="text-sm font-semibold text-gray-700">{{ __('Completed') }}</h2>
                            <span class="ms-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-500">
                                {{ $completedCount }}
                            </span>
                        </div>

                        @if ($groups['completed']->isEmpty())
                            <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50/60 flex flex-col items-center justify-center py-10 px-4 text-center">
                                <svg class="w-7 h-7 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="text-xs text-gray-400">{{ __('orders.no_orders_in_group') }}</p>
                            </div>
                        @else
                            @foreach ($groups['completed'] as $order)
                                @include('orders._card', ['order' => $order, 'highlight' => false, 'muted' => true, 'desktop' => true])
                            @endforeach
                        @endif
                    </div>

                </div>
            </div>

        </div>{{-- end x-data --}}
    @endif

</x-app-layout>
