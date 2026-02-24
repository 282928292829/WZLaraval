<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ __('Welcome back') }}, {{ Auth::user()->name }}
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    @if (Auth::user()->hasRole('superadmin'))
                        <span class="inline-flex items-center gap-1 text-purple-600 font-medium">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            {{ __('Super Admin') }}
                        </span>
                    @elseif (Auth::user()->hasRole('admin'))
                        <span class="inline-flex items-center gap-1 text-indigo-600 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            {{ __('Admin') }}
                        </span>
                    @elseif (Auth::user()->hasRole('editor'))
                        <span class="inline-flex items-center gap-1 text-teal-600 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            {{ __('Editor') }}
                        </span>
                    @else
                        <span class="text-gray-400">{{ __('Customer') }}</span>
                    @endif
                </p>
            </div>

            {{-- Customer: primary CTA --}}
            @if (Auth::user()->hasRole('customer'))
                <a href="{{ url('/new-order') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New Order') }}
                </a>
            @endif

            {{-- Staff: quick Filament link --}}
            @can('access-filament')
                <a href="{{ url('/admin') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ __('Admin Panel') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ════════════════════════════════════════════════════════════ --}}
            {{-- CUSTOMER DASHBOARD                                           --}}
            {{-- ════════════════════════════════════════════════════════════ --}}
            @if (Auth::user()->hasRole('customer'))

                {{-- Stats row --}}
                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                    <x-dashboard.stat-card
                        :label="__('Total Orders')"
                        :value="$stats['total']"
                        color="gray"
                        icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                    <x-dashboard.stat-card
                        :label="__('Open Orders')"
                        :value="$stats['open']"
                        color="blue"
                        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                    <x-dashboard.stat-card
                        :label="__('Needs Action')"
                        :value="$stats['needs_action']"
                        color="orange"
                        icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </div>

                {{-- Recent orders --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-900">{{ __('Recent Orders') }}</h2>
                        <a href="{{ url('/orders') }}"
                           class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                            {{ __('View all') }} →
                        </a>
                    </div>

                    @if ($recentOrders->isEmpty())
                        {{-- Empty state --}}
                        <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
                            <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">{{ __('No orders yet') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Your orders will appear here once you place one.') }}</p>
                            <a href="{{ url('/new-order') }}"
                               class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ __('Place your first order') }}
                            </a>
                        </div>
                    @else
                        <ul class="divide-y divide-gray-50">
                            @foreach ($recentOrders as $order)
                                <li>
                                    <a href="{{ url('/orders/' . $order->id) }}"
                                       class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition-colors group">
                                        <div class="flex items-center gap-3 min-w-0">
                                            {{-- Status dot --}}
                                            <span class="shrink-0 w-2 h-2 rounded-full
                                                @if ($order->status === 'completed') bg-green-400
                                                @elseif ($order->status === 'cancelled') bg-gray-300
                                                @elseif ($order->status === 'needs_payment') bg-red-400
                                                @elseif ($order->status === 'on_hold') bg-orange-400
                                                @elseif (in_array($order->status, ['processing','purchasing'])) bg-blue-400
                                                @else bg-yellow-400
                                                @endif"></span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate">
                                                    {{ $order->order_number }}
                                                </p>
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    {{ $order->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                                @if ($order->status === 'completed') bg-green-50 text-green-700
                                                @elseif ($order->status === 'cancelled') bg-gray-100 text-gray-500
                                                @elseif ($order->status === 'needs_payment') bg-red-50 text-red-700
                                                @elseif ($order->status === 'on_hold') bg-orange-50 text-orange-700
                                                @elseif (in_array($order->status, ['processing','purchasing'])) bg-blue-50 text-blue-700
                                                @else bg-yellow-50 text-yellow-700
                                                @endif">
                                                {{ $order->statusLabel() }}
                                            </span>
                                            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-400 transition-colors rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Quick help card --}}
                <div class="bg-primary-50 rounded-2xl p-5 border border-primary-100">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-9 h-9 rounded-xl bg-primary-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-primary-900">{{ __('How it works') }}</p>
                            <p class="text-xs text-primary-700 mt-1 leading-relaxed">
                                {{ __('Place your order, upload products from any store. Our team will purchase and ship them to you.') }}
                            </p>
                        </div>
                    </div>
                </div>

            @else

            {{-- ════════════════════════════════════════════════════════════ --}}
            {{-- STAFF DASHBOARD (editor / admin / superadmin)                --}}
            {{-- ════════════════════════════════════════════════════════════ --}}

                {{-- Order stats grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    <x-dashboard.stat-card
                        :label="__('Orders Today')"
                        :value="$orderStats['total_today']"
                        color="primary"
                        icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                    <x-dashboard.stat-card
                        :label="__('Open Orders')"
                        :value="$orderStats['open']"
                        color="blue"
                        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                    <x-dashboard.stat-card
                        :label="__('Needs Payment')"
                        :value="$orderStats['needs_payment']"
                        color="red"
                        icon="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                    />
                    <x-dashboard.stat-card
                        :label="__('Processing')"
                        :value="$orderStats['processing']"
                        color="indigo"
                        icon="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </div>

                {{-- Quick actions --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <a href="{{ url('/orders') }}"
                       class="flex items-center gap-3 px-4 py-3.5 bg-white rounded-xl border border-gray-100 shadow-sm hover:border-gray-200 hover:shadow transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0 group-hover:bg-blue-100 transition-colors">
                            <svg class="w-4.5 h-4.5 w-[18px] h-[18px] text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700">{{ __('All Orders') }}</span>
                    </a>

                    @can('export-csv')
                        <a href="{{ url('/orders?export=csv') }}"
                           class="flex items-center gap-3 px-4 py-3.5 bg-white rounded-xl border border-gray-100 shadow-sm hover:border-gray-200 hover:shadow transition-all group">
                            <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center shrink-0 group-hover:bg-green-100 transition-colors">
                                <svg class="w-[18px] h-[18px] text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ __('Export CSV') }}</span>
                        </a>
                    @endcan

                    @can('access-filament')
                        <a href="{{ url('/admin/blog-posts') }}"
                           class="flex items-center gap-3 px-4 py-3.5 bg-white rounded-xl border border-gray-100 shadow-sm hover:border-gray-200 hover:shadow transition-all group">
                            <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center shrink-0 group-hover:bg-purple-100 transition-colors">
                                <svg class="w-[18px] h-[18px] text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ __('Blog Posts') }}</span>
                        </a>

                        <a href="{{ url('/admin/settings') }}"
                           class="flex items-center gap-3 px-4 py-3.5 bg-white rounded-xl border border-gray-100 shadow-sm hover:border-gray-200 hover:shadow transition-all group">
                            <div class="w-9 h-9 rounded-lg bg-gray-50 flex items-center justify-center shrink-0 group-hover:bg-gray-100 transition-colors">
                                <svg class="w-[18px] h-[18px] text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ __('Settings') }}</span>
                        </a>
                    @endcan

                    @can('manage-users')
                        <a href="{{ url('/admin/users') }}"
                           class="flex items-center gap-3 px-4 py-3.5 bg-white rounded-xl border border-gray-100 shadow-sm hover:border-gray-200 hover:shadow transition-all group">
                            <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center shrink-0 group-hover:bg-teal-100 transition-colors">
                                <svg class="w-[18px] h-[18px] text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ __('Users') }}</span>
                        </a>
                    @endcan
                </div>

                {{-- Recent activity feed --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-900">{{ __('Recent Activity') }}</h2>
                        @can('view-staff-dashboard')
                            <a href="{{ url('/inbox') }}"
                               class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                {{ __('View inbox') }} →
                            </a>
                        @endcan
                    </div>

                    @if ($recentActivity->isEmpty())
                        <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                            <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400">{{ __('No activity yet') }}</p>
                        </div>
                    @else
                        <ul class="divide-y divide-gray-50">
                            @foreach ($recentActivity as $item)
                                <li class="flex items-start gap-3 px-5 py-3.5 hover:bg-gray-50 transition-colors">
                                    {{-- Icon --}}
                                    <div class="shrink-0 mt-0.5 w-8 h-8 rounded-lg
                                        @if ($item->type === 'new_order') bg-blue-50
                                        @elseif ($item->type === 'comment') bg-purple-50
                                        @elseif ($item->type === 'payment') bg-green-50
                                        @elseif ($item->type === 'status_change') bg-teal-50
                                        @else bg-gray-50
                                        @endif
                                        flex items-center justify-center">
                                        <svg class="w-4 h-4
                                            @if ($item->type === 'new_order') text-blue-500
                                            @elseif ($item->type === 'comment') text-purple-500
                                            @elseif ($item->type === 'payment') text-green-500
                                            @elseif ($item->type === 'status_change') text-teal-500
                                            @else text-gray-400
                                            @endif"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item->typeIcon() }}"/>
                                        </svg>
                                    </div>
                                    {{-- Content --}}
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium">
                                                {{ $item->causer?->name ?? __('System') }}
                                            </span>
                                            —
                                            {{ $item->typeLabel() }}
                                            @if (!empty($item->data['order_number']))
                                                <span class="text-gray-500">{{ $item->data['order_number'] }}</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $item->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    {{-- Unread dot --}}
                                    @if (is_null($item->read_at))
                                        <span class="shrink-0 mt-1.5 w-2 h-2 rounded-full bg-primary-400"></span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

            @endif
        </div>
    </div>
</x-app-layout>
