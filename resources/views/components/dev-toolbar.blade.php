@if (app()->environment('local') && config('app.dev_toolbar', true))
@php
    $roles = [
        'customer'   => ['label' => __('dev.customer'),   'short' => 'C', 'color' => 'bg-sky-500 hover:bg-sky-600'],
        'staff'      => ['label' => __('dev.staff'),      'short' => 'S', 'color' => 'bg-teal-500 hover:bg-teal-600'],
        'admin'      => ['label' => __('dev.admin'),      'short' => 'A', 'color' => 'bg-indigo-500 hover:bg-indigo-600'],
        'superadmin' => ['label' => __('dev.super_admin'),'short' => 'SA','color' => 'bg-purple-500 hover:bg-purple-600'],
    ];
    $currentUser  = auth()->user();
    $currentRole  = $currentUser?->getRoleNames()->first();
@endphp

<div x-data="{ open: true }"
     class="z-[9999] flex flex-col items-start gap-1"
     style="position:fixed; bottom:8px; left:8px; max-width:calc(100vw - 16px);">

    {{-- Toolbar --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="flex flex-wrap items-center gap-1 sm:gap-1.5 bg-gray-900/95 backdrop-blur text-white text-[10px] sm:text-xs rounded-xl sm:rounded-2xl shadow-2xl px-2 py-1.5 sm:px-3 sm:py-2 ring-1 ring-white/10 max-w-[calc(100vw-16px)]">

        {{-- Dev badge --}}
        <span class="px-1.5 py-0.5 rounded-full bg-yellow-400 text-yellow-900 font-bold text-[9px] sm:text-[10px] tracking-wide me-0.5 sm:me-1 shrink-0">{{ __('dev.badge') }}</span>

        @if ($currentUser)
            {{-- Current role --}}
            <span class="text-white/80 me-0.5 sm:me-1 font-medium" title="{{ $roles[$currentRole]['label'] ?? $currentRole }}">
                {{ $roles[$currentRole]['short'] ?? $currentRole }}
            </span>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-red-500 hover:bg-red-600 text-white font-medium transition-colors text-[10px] sm:text-[11px] shrink-0">
                    {{ __('dev.logout') }}
                </button>
            </form>

            {{-- Switch role buttons --}}
            <span class="w-px h-3 sm:h-4 bg-white/20 mx-0.5 sm:mx-1 hidden sm:inline"></span>

            @foreach ($roles as $role => $cfg)
                @if ($role !== $currentRole)
                    <form method="POST" action="{{ route('dev.login-as') }}">
                        @csrf
                        <input type="hidden" name="role" value="{{ $role }}">
                        <button type="submit"
                                title="{{ $cfg['label'] }}"
                                class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg {{ $cfg['color'] }} text-white font-medium transition-colors text-[10px] sm:text-[11px] shrink-0">
                            {{ $cfg['short'] }}
                        </button>
                    </form>
                @endif
            @endforeach

        @else
            {{-- Not logged in — show all role buttons --}}
            <span class="text-white/50 me-0.5 sm:me-1 shrink-0">{{ __('dev.login_as') }}:</span>

            @foreach ($roles as $role => $cfg)
                <form method="POST" action="{{ route('dev.login-as') }}">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role }}">
                    <button type="submit"
                            title="{{ $cfg['label'] }}"
                            class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg {{ $cfg['color'] }} text-white font-medium transition-colors text-[10px] sm:text-[11px] shrink-0">
                        {{ $cfg['short'] }}
                    </button>
                </form>
            @endforeach
        @endif

        {{-- Collapse button --}}
        <span class="w-px h-3 sm:h-4 bg-white/20 mx-0.5 sm:mx-1"></span>
        <button @click="open = false"
                class="p-0.5 sm:p-1 rounded-lg hover:bg-white/10 text-white/50 hover:text-white transition-colors shrink-0"
                title="{{ __('Hide toolbar') }}">
            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>

    {{-- Re-open pill --}}
    <button x-show="!open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            @click="open = true"
            class="flex items-center gap-1 sm:gap-1.5 bg-gray-900/90 backdrop-blur text-white text-[10px] sm:text-[11px] font-medium rounded-full shadow-xl ring-1 ring-white/10 hover:bg-gray-800 transition-colors shrink-0"
            style="display:none; padding:4px 10px;">
        <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-yellow-400 shrink-0"></span>
        {{ __('dev.badge') }}
        @if ($currentUser)
            <span class="text-white/50" title="{{ $roles[$currentRole]['label'] ?? $currentRole }}">· {{ $roles[$currentRole]['short'] ?? $currentRole }}</span>
        @endif
    </button>

</div>
@endif
