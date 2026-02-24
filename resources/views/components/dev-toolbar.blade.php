@if (app()->environment('local') && config('app.dev_toolbar', true))
@php
    $roles = [
        'customer'   => ['label' => __('dev.customer'),   'color' => 'bg-sky-500 hover:bg-sky-600'],
        'editor'     => ['label' => __('dev.editor'),     'color' => 'bg-teal-500 hover:bg-teal-600'],
        'admin'      => ['label' => __('dev.admin'),      'color' => 'bg-indigo-500 hover:bg-indigo-600'],
        'superadmin' => ['label' => __('dev.super_admin'),'color' => 'bg-purple-500 hover:bg-purple-600'],
    ];
    $currentUser  = auth()->user();
    $currentRole  = $currentUser?->getRoleNames()->first();
@endphp

<div x-data="{ open: true }"
     class="z-[9999] flex flex-col items-center gap-1.5"
     style="position:fixed; bottom:16px; right:16px;">

    {{-- Toolbar --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="flex items-center gap-1.5 bg-gray-900/95 backdrop-blur text-white text-xs rounded-2xl shadow-2xl px-3 py-2 ring-1 ring-white/10">

        {{-- Dev badge --}}
        <span class="px-2 py-0.5 rounded-full bg-yellow-400 text-yellow-900 font-bold text-[10px] tracking-wide me-1">DEV</span>

        @if ($currentUser)
            {{-- Current user indicator --}}
            <span class="text-white/60 me-1">
                {{ $currentUser->name }}
                <span class="text-white/40">({{ $currentRole }})</span>
            </span>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="px-2.5 py-1 rounded-lg bg-red-500 hover:bg-red-600 text-white font-medium transition-colors text-[11px]">
                    {{ __('dev.logout') }}
                </button>
            </form>

            {{-- Switch role buttons --}}
            <span class="w-px h-4 bg-white/20 mx-1"></span>

            @foreach ($roles as $role => $cfg)
                @if ($role !== $currentRole)
                    <form method="POST" action="{{ route('dev.login-as') }}">
                        @csrf
                        <input type="hidden" name="role" value="{{ $role }}">
                        <button type="submit"
                                class="px-2.5 py-1 rounded-lg {{ $cfg['color'] }} text-white font-medium transition-colors text-[11px]">
                            {{ $cfg['label'] }}
                        </button>
                    </form>
                @endif
            @endforeach

        @else
            {{-- Not logged in — show all role buttons --}}
            <span class="text-white/50 me-1">{{ __('dev.login_as') }}:</span>

            @foreach ($roles as $role => $cfg)
                <form method="POST" action="{{ route('dev.login-as') }}">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role }}">
                    <button type="submit"
                            class="px-2.5 py-1 rounded-lg {{ $cfg['color'] }} text-white font-medium transition-colors text-[11px]">
                        {{ $cfg['label'] }}
                    </button>
                </form>
            @endforeach
        @endif

        {{-- Collapse button --}}
        <span class="w-px h-4 bg-white/20 mx-1"></span>
        <button @click="open = false"
                class="p-1 rounded-lg hover:bg-white/10 text-white/50 hover:text-white transition-colors"
                title="{{ __('Hide toolbar') }}">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
            class="flex items-center gap-1.5 bg-gray-900/90 backdrop-blur text-white text-[11px] font-medium rounded-full shadow-xl ring-1 ring-white/10 hover:bg-gray-800 transition-colors"
            style="display:none; padding:6px 12px;">
        <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
        DEV
        @if ($currentUser)
            <span class="text-white/50">· {{ $currentRole }}</span>
        @endif
    </button>

</div>
@endif
