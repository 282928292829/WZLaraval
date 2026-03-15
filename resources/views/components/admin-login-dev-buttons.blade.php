@if (app()->environment('local') && config('app.dev_toolbar', true))
@php
    $adminRoles = [
        'admin'      => ['label' => __('dev.admin'), 'short' => 'A', 'color' => 'bg-indigo-600 hover:bg-indigo-700'],
        'superadmin' => ['label' => __('dev.super_admin'), 'short' => 'SA', 'color' => 'bg-purple-600 hover:bg-purple-700'],
    ];
@endphp
<div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/50">
    <p class="mb-3 text-sm font-medium text-amber-800 dark:text-amber-200">
        {{ __('dev.quick_login_local') }}
    </p>
    <div class="flex flex-wrap gap-2">
        @foreach ($adminRoles as $role => $cfg)
            <form method="POST" action="{{ route('dev.login-as') }}" class="inline">
                @csrf
                <input type="hidden" name="role" value="{{ $role }}">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-white shadow transition-colors {{ $cfg['color'] }}">
                    <span class="rounded bg-white/20 px-1.5 py-0.5 text-xs">{{ $cfg['short'] }}</span>
                    {{ $cfg['label'] }}
                </button>
            </form>
        @endforeach
    </div>
</div>
@endif
