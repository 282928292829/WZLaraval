@php
    $roleLabel = match(true) {
        $user->hasRole('superadmin') => ['label' => __('Super Admin'), 'class' => 'bg-purple-100 text-purple-700'],
        $user->hasRole('admin')      => ['label' => __('Admin'),       'class' => 'bg-indigo-100 text-indigo-700'],
        $user->hasRole('staff')      => ['label' => __('Staff'),       'class' => 'bg-teal-100 text-teal-700'],
        default                      => ['label' => __('Customer'),    'class' => 'bg-gray-100 text-gray-600'],
    };

    $hasErrors      = $errors->any() || $errors->updateProfile->any() || $errors->updatePassword->any() || $errors->storeAddress->any() || $errors->updateAddress->any();
    $openAddressAdd = $errors->storeAddress->any() && old('_form') === 'add_address';
    $openEditId     = $errors->updateAddress->any() && old('_form') === 'edit_address' ? (int) old('_address_id') : null;
    $validTabs      = ['profile', 'addresses', 'activity', 'notifications', 'balance'];
@endphp

<x-app-layout :minimal-footer="true">


{{-- ── Vertical accordion (mobile-friendly: all sections visible, tap to expand) ── --}}
<div class="max-w-3xl mx-auto px-4 py-6 sm:py-8"
     x-data="{ open: '{{ $tab }}' }">

    {{-- Flash messages --}}
    @if (session('status') === 'profile-updated')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('account.profile_updated') }}
        </div>
    @elseif (session('status') === 'password-updated')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('account.password_updated') }}
        </div>
    @elseif (session('status') === 'address-saved')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('account.address_saved') }}
        </div>
    @elseif (session('status') === 'address-deleted')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('account.address_deleted') }}
        </div>
    @elseif (session('status') === 'deletion-requested')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-orange-50 border border-orange-200 text-orange-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            {{ __('account.deletion_requested_notice') }}
        </div>
    @elseif (session('status') === 'deletion-already-requested')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 text-gray-600 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            {{ __('account.deletion_already_requested') }}
        </div>
    @elseif (session('status') === 'deletion-cancelled')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('account.deletion_cancelled_notice') }}
        </div>
    @elseif (session('status') === 'email-change-requested')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            {{ __('account.email_change_code_sent') }}
        </div>
    @elseif (session('status') === 'email-changed')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('account.email_changed_success') }}
        </div>
    @elseif (session('status') === 'notifications-updated')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('account.notifications_updated') }}
        </div>
    @elseif (session('status') === 'email-verified')
        <div class="flex items-center gap-2 mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ __('account.email_verified') }}
        </div>
    @endif

    {{-- Order stats --}}
    @if ($orderStats['total'] > 0)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $orderStats['total'] }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ __('account.orders_total') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-primary-600">{{ $orderStats['active'] }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ __('account.orders_active') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $orderStats['shipped'] }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ __('account.orders_shipped') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-gray-400">{{ $orderStats['cancelled'] }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ __('account.orders_cancelled') }}</div>
        </div>
    </div>
    @endif

    {{-- Quick actions --}}
    <div class="flex gap-3 mb-5">
        <a href="{{ route('new-order') }}"
           class="flex-1 flex items-center justify-center gap-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-3 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('account.quick_new_order') }}
        </a>
        <a href="{{ route('orders.index') }}"
           class="flex-1 flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold py-3 rounded-xl border border-gray-200 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            {{ __('account.quick_my_orders') }}
        </a>
    </div>

    {{-- Accordion: Profile --}}
    <div class="space-y-2">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <button type="button" @click="open = open === 'profile' ? '' : 'profile'"
                class="w-full flex items-center justify-between gap-3 px-5 py-4 text-start hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ __('account.profile_tab') }}</span>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="{ 'rotate-180': open === 'profile' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open === 'profile'" x-collapse x-cloak>
        <div class="border-t border-gray-100 py-4 space-y-6">

        {{-- Personal info --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
             x-data="{
                editing: '{{ $errors->updatePassword->hasAny(['current_password','password','password_confirmation']) ? 'password' : ($errors->updateProfile->hasAny(['name','email','phone','phone_secondary']) ? (
                    $errors->updateProfile->has('name') ? 'name' : (
                        $errors->updateProfile->has('email') ? 'email' : 'phone'
                    )
                ) : '') }}',
                open(field) { this.editing = field; },
                close()     { this.editing = ''; }
             }">

            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('account.personal_info') }}</h2>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $roleLabel['class'] }}">
                    {{ $roleLabel['label'] }}
                </span>
            </div>

            {{-- ── Name row ─────────────────────────────────────────────── --}}
            <div class="px-5 py-4 border-b border-gray-100/70">

                {{-- Display --}}
                <div x-show="editing !== 'name'" class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400 mb-0.5">{{ __('Name') }}</p>
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</p>
                    </div>
                    <button type="button" @click="open('name')"
                        class="shrink-0 text-xs font-medium text-primary-600 hover:text-primary-700 transition">
                        {{ __('account.change') }}
                    </button>
                </div>

                {{-- Edit --}}
                <div x-show="editing === 'name'" x-collapse>
                    <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-3 pt-1">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        <input type="hidden" name="phone" value="{{ $user->phone }}">
                        <input type="hidden" name="phone_secondary" value="{{ $user->phone_secondary }}">
                        <div>
                            <label for="name" class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('Name') }}</label>
                            <input id="name" name="name" type="text"
                                value="{{ old('name', $user->name) }}"
                                required autocomplete="name"
                                x-init="$watch('editing', v => { if (v === 'name') $nextTick(() => $el.focus()) })"
                                class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            @error('name', 'updateProfile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 text-xs font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors">
                                {{ __('account.save_changes') }}
                            </button>
                            <button type="button" @click="close()"
                                class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition">
                                {{ __('account.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Email row ────────────────────────────────────────────── --}}
            <div class="px-5 py-4 border-b border-gray-100/70">

                {{-- Display --}}
                <div x-show="editing !== 'email'" class="flex items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-400 mb-0.5">{{ __('Email') }}</p>
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $user->email }}</p>
                        <div class="mt-1.5">
                            @if ($user->email_verified_at === null)
                                @php $waNum = preg_replace('/\D/', '', \App\Models\Setting::get('whatsapp', '966500000000')); @endphp
                                <div x-data="{
                                    cooldown: {{ session('status') === 'verification-link-sent' ? 60 : 0 }},
                                    timer: null,
                                    start() {
                                        this.cooldown = 60;
                                        this.timer = setInterval(() => { if (--this.cooldown <= 0) { this.cooldown = 0; clearInterval(this.timer); } }, 1000);
                                    }
                                }" x-init="cooldown > 0 && start()" class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <form method="POST" action="{{ route('verification.send') }}" @submit="start()">
                                            @csrf
                                            <button type="submit" :disabled="cooldown > 0"
                                                :class="cooldown > 0 ? 'opacity-60 cursor-not-allowed' : 'hover:bg-amber-100'"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 border border-amber-200 text-amber-700 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                                </svg>
                                                <span x-text="cooldown > 0 ? '{{ __('account.email_not_confirmed') }} (' + cooldown + 's)' : '{{ __('account.email_not_confirmed') }}'"></span>
                                            </button>
                                        </form>
                                        @if (session('status') === 'verification-link-sent')
                                            <p class="text-xs text-green-600 font-medium">{{ __('account.verification_sent') }}</p>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400">
                                        {{ app()->getLocale() === 'ar' ? 'لم يصلك البريد؟' : "Didn't receive it?" }}
                                        <a href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener"
                                            class="text-green-600 hover:text-green-700 font-medium underline underline-offset-2 transition">
                                            {{ app()->getLocale() === 'ar' ? 'تواصل معنا عبر واتساب' : 'Contact us on WhatsApp' }}
                                        </a>
                                    </p>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-green-50 border border-green-200 text-green-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ __('account.email_confirmed') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <button type="button" @click="open('email')"
                        class="shrink-0 text-xs font-medium text-primary-600 hover:text-primary-700 transition">
                        {{ __('account.change') }}
                    </button>
                </div>

                {{-- Edit — 2-step email change flow --}}
                <div x-show="editing === 'email'" x-collapse
                     x-data="{ step: '{{ $user->email_change_pending && $user->email_change_expires_at && now()->lt($user->email_change_expires_at) ? 'verify' : 'request' }}' }">

                    {{-- Step 1: enter new email --}}
                    <form method="POST" action="{{ route('account.email-change.request') }}" class="space-y-3 pt-1"
                          x-show="step === 'request'">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('account.new_email_label') }}</label>
                            <input name="email" type="email"
                                value="{{ old('email') }}"
                                required autocomplete="email"
                                x-init="$watch('editing', v => { if (v === 'email') $nextTick(() => $el.focus()) })"
                                class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            @error('email', 'emailChange') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <p class="text-xs text-gray-400">{{ __('account.email_change_code_hint') }}</p>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 text-xs font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors">
                                {{ __('account.email_change_send_code') }}
                            </button>
                            <button type="button" @click="close()"
                                class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition">
                                {{ __('account.cancel') }}
                            </button>
                        </div>
                    </form>

                    {{-- Step 2: enter verification code --}}
                    <form method="POST" action="{{ route('account.email-change.verify') }}" class="space-y-3 pt-1"
                          x-show="step === 'verify'">
                        @csrf
                        @if ($user->email_change_pending)
                            <p class="text-xs text-gray-500">
                                {{ __('account.email_change_code_sent_to', ['email' => $user->email_change_pending]) }}
                            </p>
                            {{-- Debug only (local env): show code in flash --}}
                            @if (session('email_change_code_debug') && app()->environment('local'))
                                <p class="text-xs font-mono bg-yellow-50 border border-yellow-200 text-yellow-800 px-3 py-2 rounded-lg">
                                    [dev] Code: {{ session('email_change_code_debug') }}
                                </p>
                            @endif
                        @endif
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('account.email_change_code_label') }}</label>
                            <input name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                                pattern="[0-9]{6}" required
                                class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 font-mono tracking-widest text-center focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            @error('code', 'emailChange') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 text-xs font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors">
                                {{ __('account.email_change_confirm') }}
                            </button>
                            <button type="button" @click="step = 'request'"
                                class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition">
                                {{ __('account.email_change_resend') }}
                            </button>
                            <button type="button" @click="close()"
                                class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition">
                                {{ __('account.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Password row ──────────────────────────────────────────── --}}
            <div class="px-5 py-4 border-b border-gray-100/70">

                {{-- Display --}}
                <div x-show="editing !== 'password'" class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400 mb-0.5">{{ __('account.password_label') }}</p>
                        <p class="text-sm font-medium text-gray-900 tracking-widest">••••••••</p>
                    </div>
                    <button type="button" @click="open('password')"
                        class="shrink-0 text-xs font-medium text-primary-600 hover:text-primary-700 transition">
                        {{ __('account.change') }}
                    </button>
                </div>

                {{-- Edit --}}
                <div x-show="editing === 'password'" x-collapse>
                    <form method="POST" action="{{ route('account.password.update') }}" class="space-y-3 pt-1">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="current_password" class="block text-xs font-medium text-gray-600 mb-1.5">
                                {{ __('account.current_password') }}
                            </label>
                            <input
                                x-ref="currentPassword"
                                x-init="$watch('editing', v => { if (v === 'password') $nextTick(() => $refs.currentPassword.focus()) })"
                                id="current_password" name="current_password" type="password"
                                autocomplete="current-password"
                                class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            @error('current_password', 'updatePassword')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-medium text-gray-600 mb-1.5">
                                {{ __('account.new_password') }}
                            </label>
                            <input id="password" name="password" type="password" autocomplete="new-password"
                                class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            @error('password', 'updatePassword')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-medium text-gray-600 mb-1.5">
                                {{ __('account.confirm_password') }}
                            </label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                                class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            @error('password_confirmation', 'updatePassword')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 text-xs font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors">
                                {{ __('account.update_password') }}
                            </button>
                            <button type="button" @click="close()"
                                class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition">
                                {{ __('account.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Phone row ────────────────────────────────────────────── --}}
            <div class="px-5 py-4">

                {{-- Display --}}
                <div x-show="editing !== 'phone'" class="flex items-center justify-between gap-4">
                    <div class="min-w-0 flex-1 space-y-1">
                        <div>
                            <p class="text-xs text-gray-400">{{ __('account.mobile_primary_label') }}</p>
                            @if ($user->phone)
                                <p class="text-sm font-medium text-gray-900"><span dir="ltr" style="unicode-bidi:embed">{{ $user->phone }}</span></p>
                            @else
                                <p class="text-sm text-amber-600">{{ __('account.no_phone_notice') }}</p>
                            @endif
                        </div>
                        @if ($user->phone_secondary)
                        <div>
                            <p class="text-xs text-gray-400">{{ __('account.mobile_secondary_label') }}</p>
                            <p class="text-sm font-medium text-gray-900"><span dir="ltr" style="unicode-bidi:embed">{{ $user->phone_secondary }}</span></p>
                        </div>
                        @endif
                    </div>
                    <button type="button" @click="open('phone')"
                        class="shrink-0 text-xs font-medium text-primary-600 hover:text-primary-700 transition">
                        {{ __('account.change') }}
                    </button>
                </div>

                {{-- Edit --}}
                @php
                    $phoneStripped          = preg_replace('/^\+?966/', '', $user->phone ?? '');
                    $phoneSecondaryStripped = preg_replace('/^\+?966/', '', $user->phone_secondary ?? '');
                @endphp
                <div x-show="editing === 'phone'" x-collapse>
                    <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-3 pt-1">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="name" value="{{ $user->name }}">
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="phone" class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('account.mobile') }}</label>
                                <div dir="ltr" class="flex rounded-xl border border-gray-200 bg-white focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500 transition overflow-hidden">
                                    <input type="text" name="phone_code"
                                        value="{{ old('phone_code', '+966') }}"
                                        maxlength="6"
                                        x-init="$watch('editing', v => { if (v === 'phone') $nextTick(() => $refs.phoneInput.focus()) })"
                                        class="shrink-0 w-16 px-2.5 py-2.5 text-sm text-center bg-gray-50 border-r border-gray-200 text-gray-700 focus:outline-none focus:bg-white transition">
                                    <input x-ref="phoneInput" id="phone" name="phone" type="tel"
                                        value="{{ old('phone', $phoneStripped) }}"
                                        placeholder="{{ __('account.mobile_placeholder') }}"
                                        autocomplete="tel" inputmode="numeric"
                                        class="block w-full px-3 py-2.5 text-sm bg-white text-gray-900 placeholder-gray-400 focus:outline-none border-0">
                                </div>
                                @error('phone', 'updateProfile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone_secondary" class="block text-xs font-medium text-gray-600 mb-1.5">
                                    {{ __('account.mobile_secondary') }}
                                    <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                </label>
                                <div dir="ltr" class="flex rounded-xl border border-gray-200 bg-white focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500 transition overflow-hidden">
                                    <input type="text" name="phone_secondary_code"
                                        value="{{ old('phone_secondary_code', '+966') }}"
                                        maxlength="6"
                                        class="shrink-0 w-16 px-2.5 py-2.5 text-sm text-center bg-gray-50 border-r border-gray-200 text-gray-700 focus:outline-none focus:bg-white transition">
                                    <input id="phone_secondary" name="phone_secondary" type="tel"
                                        value="{{ old('phone_secondary', $phoneSecondaryStripped) }}"
                                        placeholder="{{ __('account.mobile_placeholder') }}"
                                        autocomplete="tel" inputmode="numeric"
                                        class="block w-full px-3 py-2.5 text-sm bg-white text-gray-900 placeholder-gray-400 focus:outline-none border-0">
                                </div>
                                @error('phone_secondary', 'updateProfile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 text-xs font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors">
                                {{ __('account.save_changes') }}
                            </button>
                            <button type="button" @click="close()"
                                class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition">
                                {{ __('account.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>


        {{-- Delete Account --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
             x-data="{ showDeleteModal: false }">
            <div class="px-5 py-4">
                @if ($user->deletion_requested)
                    <p class="text-sm text-gray-500">
                        {{ __('account.deletion_pending') }}
                        &nbsp;·&nbsp;
                        <form method="POST" action="{{ route('account.cancel-deletion') }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="text-sm text-gray-500 hover:text-gray-700 underline underline-offset-2 transition-colors">
                                {{ __('account.cancel_deletion') }}
                            </button>
                        </form>
                    </p>
                @else
                    <p class="text-sm text-gray-500">
                        <span class="font-medium text-gray-700">{{ __('account.danger_zone') }}:</span>
                        {{ __('account.delete_account_description') }}
                        <button type="button" @click="showDeleteModal = true"
                            class="text-red-500 hover:text-red-600 underline underline-offset-2 transition-colors">
                            {{ __('account.delete_account_button') }}
                        </button>
                    </p>
                @endif
            </div>

            {{-- Confirmation modal --}}
            <div
                x-show="showDeleteModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">

                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-black/40" @click="showDeleteModal = false"></div>

                {{-- Dialog --}}
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 z-10"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">

                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>

                    <h3 class="text-base font-bold text-gray-900 text-center mb-1">{{ __('account.delete_account_confirm_title') }}</h3>
                    <p class="text-sm text-gray-500 text-center mb-6">{{ __('account.delete_account_confirm_body') }}</p>

                    <div class="flex gap-3">
                        <button type="button" @click="showDeleteModal = false"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                            {{ __('account.cancel') }}
                        </button>
                        <form method="POST" action="{{ route('account.request-deletion') }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors">
                                {{ __('account.confirm_delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
        </div>
        </div>
    </div>

    {{-- Accordion: Addresses --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <button type="button" @click="open = open === 'addresses' ? '' : 'addresses'"
                class="w-full flex items-center justify-between gap-3 px-5 py-4 text-start hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ __('account.addresses_tab') }}</span>
                @if ($addresses->count())
                    <span class="text-xs bg-gray-100 text-gray-600 rounded-full px-1.5 py-0.5 leading-none">
                        {{ $addresses->count() }}
                    </span>
                @endif
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="{ 'rotate-180': open === 'addresses' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open === 'addresses'" x-collapse x-cloak>
        <div class="border-t border-gray-100 py-4">
    <div x-data="{
             showAdd: {{ $openAddressAdd ? 'true' : 'false' }},
             editId: {{ $openEditId ?? 'null' }}
         }"
         class="space-y-4">

        {{-- Address cards --}}
        @forelse ($addresses as $address)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                {{-- Card body --}}
                <div class="flex items-start gap-4 px-5 py-4">
                    {{-- Pin icon --}}
                    <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4.5 h-4.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        {{-- Label + default badge --}}
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            @if ($address->label)
                                <span class="text-sm font-semibold text-gray-900">{{ $address->label }}</span>
                            @endif
                            @if ($address->is_default)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700">
                                    {{ __('account.default_badge') }}
                                </span>
                            @endif
                        </div>

                        @if ($address->recipient_name)
                            <p class="text-sm text-gray-700">{{ $address->recipient_name }}</p>
                        @endif
                        <p class="text-sm text-gray-600">{{ $address->address }}</p>
                        <p class="text-sm text-gray-500">{{ $address->city }}@if($address->city && $address->country), @endif{{ $address->country }}</p>
                        @if ($address->phone)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $address->phone }}</p>
                        @endif
                    </div>
                </div>

                {{-- Card actions --}}
                <div class="flex items-center gap-3 px-5 py-3 border-t border-gray-50 bg-gray-50/50">
                    {{-- Edit --}}
                    <button
                        @click="editId = (editId === {{ $address->id }}) ? null : {{ $address->id }}"
                        class="text-xs font-medium text-gray-600 hover:text-gray-900 transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        {{ __('account.edit_address') }}
                    </button>

                    <span class="text-gray-200">|</span>

                    {{-- Set as default --}}
                    @if (!$address->is_default)
                        <form method="POST" action="{{ route('account.addresses.default', $address) }}">
                            @csrf
                            <button type="submit"
                                class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                {{ __('account.set_default') }}
                            </button>
                        </form>
                        <span class="text-gray-200">|</span>
                    @endif

                    {{-- Delete --}}
                    <form method="POST" action="{{ route('account.addresses.destroy', $address) }}"
                          x-data
                          @submit.prevent="if(confirm('{{ __('account.confirm_delete_address') }}')) $el.submit()">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            {{ __('account.delete_address') }}
                        </button>
                    </form>
                </div>

                {{-- Inline edit form --}}
                <div x-show="editId === {{ $address->id }}"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="border-t border-gray-100">
                    <form method="POST" action="{{ route('account.addresses.update', $address) }}"
                          class="px-5 py-5 space-y-3 bg-gray-50/40">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_form" value="edit_address">
                        <input type="hidden" name="_address_id" value="{{ $address->id }}">

                        <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide">{{ __('account.edit_address') }}</p>

                        {{-- Label + Recipient --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.address_label') }}</label>
                                <input type="text" name="label"
                                    value="{{ $openEditId === $address->id ? old('label') : $address->label }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                    placeholder="{{ __('account.label_placeholder') }}">
                                @error('label', 'updateAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.recipient_name') }} <span class="text-red-400">*</span></label>
                                <input type="text" name="recipient_name" required
                                    value="{{ $openEditId === $address->id ? old('recipient_name') : $address->recipient_name }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                @error('recipient_name', 'updateAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Country + City --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    {{ __('account.country') }}
                                    <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                </label>
                                <input type="text" name="country"
                                    value="{{ $openEditId === $address->id ? old('country', $address->country) : $address->country }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    {{ __('account.city') }} <span class="text-red-400">*</span>
                                </label>
                                <input type="text" name="city" required
                                    value="{{ $openEditId === $address->id ? old('city') : $address->city }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                @error('city', 'updateAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Street + District (optional) --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    {{ __('account.street') }}
                                    <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                </label>
                                <input type="text" name="street"
                                    value="{{ $openEditId === $address->id ? old('street') : $address->street }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                @error('street', 'updateAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    {{ __('account.district') }}
                                    <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                </label>
                                <input type="text" name="district"
                                    value="{{ $openEditId === $address->id ? old('district') : $address->district }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                @error('district', 'updateAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Short address + Address details --}}
                        @php $waNum = preg_replace('/\D/', '', \App\Models\Setting::get('whatsapp', '966500000000')); @endphp
                        <div class="grid grid-cols-2 gap-3">
                            <div x-data="{ open: false }">
                                <label class="flex items-center gap-1 text-xs font-medium text-gray-600 mb-1">
                                    {{ __('account.short_address') }}
                                    <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                    <button type="button" @click="open = !open" class="text-blue-400 hover:text-blue-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                </label>
                                <input type="text" name="short_address" maxlength="20"
                                    value="{{ $openEditId === $address->id ? old('short_address') : $address->short_address }}"
                                    placeholder="{{ __('account.short_address_placeholder') }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <div x-show="open" x-collapse class="mt-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5 space-y-1.5">
                                    <p class="text-xs text-blue-700 leading-relaxed">
                                        <span class="font-medium">١.</span>
                                        {{ __('account.national_address_tip_whatsapp') }}
                                        &nbsp;<a href="https://wa.me/{{ __('account.whatsapp_number_wa') }}" target="_blank" rel="noopener"
                                            class="underline underline-offset-2 font-semibold hover:text-blue-900 transition" dir="ltr">{{ __('account.whatsapp_number') }}</a>
                                        ثم شارك موقعك الجغرافي وسيُرسَل إليك الرمز.
                                    </p>
                                    <p class="text-xs text-blue-700 leading-relaxed">
                                        <span class="font-medium">٢.</span>
                                        {{ __('account.national_address_tip_apps') }}
                                    </p>
                                    <p class="text-xs text-blue-700 leading-relaxed">
                                        <span class="font-medium">٣.</span>
                                        <a href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener"
                                            class="underline underline-offset-2 font-semibold hover:text-blue-900 transition">{{ __('account.national_address_tip_us') }}</a>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    {{ __('account.address') }}
                                    <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                </label>
                                <input type="text" name="address"
                                    value="{{ $openEditId === $address->id ? old('address') : $address->address }}"
                                    placeholder="{{ __('account.address_placeholder') }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                @error('address', 'updateAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Phone (required) + Default --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.mobile') }} <span class="text-red-400">*</span></label>
                                <input type="tel" name="phone" required
                                    value="{{ $openEditId === $address->id ? old('phone') : $address->phone }}"
                                    placeholder="{{ __('account.mobile_placeholder') }}"
                                    inputmode="numeric" pattern="[0-9]*"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                @error('phone', 'updateAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex items-end pb-1">
                                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                                    <input type="checkbox" name="is_default" value="1"
                                        {{ ($openEditId === $address->id ? old('is_default') : $address->is_default) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                                    {{ __('account.set_default') }}
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-1">
                            <button type="submit"
                                class="px-5 py-2 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors">
                                {{ __('account.save_address') }}
                            </button>
                            <button type="button" @click="editId = null"
                                class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                                {{ __('account.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            {{-- Empty state --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-12 text-center">
                <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ __('account.no_addresses') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('account.no_addresses_hint') }}</p>
            </div>
        @endforelse

        {{-- Add address button --}}
        <button
            @click="showAdd = !showAdd; editId = null"
            :class="showAdd ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'"
            class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl border-2 border-dashed text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('account.add_address') }}
        </button>

        {{-- Add address form --}}
        <div x-show="showAdd"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('account.add_address') }}</h3>
                <button @click="showAdd = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('account.addresses.store') }}" class="px-5 py-5 space-y-3">
                @csrf
                <input type="hidden" name="_form" value="add_address">

                {{-- Label + Recipient --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.address_label') }}</label>
                        <input type="text" name="label" value="{{ old('label') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                            placeholder="{{ __('account.label_placeholder') }}">
                        @error('label', 'storeAddress')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.recipient_name') }} <span class="text-red-400">*</span></label>
                        <input type="text" name="recipient_name" required value="{{ old('recipient_name') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        @error('recipient_name', 'storeAddress')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Country + City --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            {{ __('account.country') }}
                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                        </label>
                        <input type="text" name="country"
                            value="{{ old('country', 'السعودية') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            {{ __('account.city') }} <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="city" required value="{{ old('city') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        @error('city', 'storeAddress')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Street + District (optional) --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            {{ __('account.street') }}
                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                        </label>
                        <input type="text" name="street" value="{{ old('street') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        @error('street', 'storeAddress')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            {{ __('account.district') }}
                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                        </label>
                        <input type="text" name="district" value="{{ old('district') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        @error('district', 'storeAddress')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Short address (Saudi national address) + Address details --}}
                @php $waNum = preg_replace('/\D/', '', \App\Models\Setting::get('whatsapp', '966500000000')); @endphp
                <div class="grid grid-cols-2 gap-3">
                    <div x-data="{ open: false }">
                        <label class="flex items-center gap-1 text-xs font-medium text-gray-600 mb-1">
                            {{ __('account.short_address') }}
                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                            <button type="button" @click="open = !open" class="text-blue-400 hover:text-blue-600 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                        </label>
                        <input type="text" name="short_address" value="{{ old('short_address') }}"
                            maxlength="20"
                            placeholder="{{ __('account.short_address_placeholder') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        <div x-show="open" x-collapse class="mt-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5 space-y-1.5">
                            <p class="text-xs text-blue-700 leading-relaxed">
                                <span class="font-medium">١.</span>
                                {{ __('account.national_address_tip_whatsapp') }}
                                &nbsp;<a href="https://wa.me/{{ __('account.whatsapp_number_wa') }}" target="_blank" rel="noopener"
                                    class="underline underline-offset-2 font-semibold hover:text-blue-900 transition" dir="ltr">{{ __('account.whatsapp_number') }}</a>
                                ثم شارك موقعك الجغرافي وسيُرسَل إليك الرمز.
                            </p>
                            <p class="text-xs text-blue-700 leading-relaxed">
                                <span class="font-medium">٢.</span>
                                {{ __('account.national_address_tip_apps') }}
                            </p>
                            <p class="text-xs text-blue-700 leading-relaxed">
                                <span class="font-medium">٣.</span>
                                <a href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener"
                                    class="underline underline-offset-2 font-semibold hover:text-blue-900 transition">{{ __('account.national_address_tip_us') }}</a>
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            {{ __('account.address') }}
                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                        </label>
                        <input type="text" name="address" value="{{ old('address') }}"
                            placeholder="{{ __('account.address_placeholder') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        @error('address', 'storeAddress')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Phone (required, pre-fill from profile) + Default checkbox --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.mobile') }} <span class="text-red-400">*</span></label>
                        <input type="tel" name="phone" required
                            value="{{ old('phone', $user->phone) }}"
                            placeholder="{{ __('account.mobile_placeholder') }}"
                            inputmode="numeric" pattern="[0-9]*"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        @error('phone', 'storeAddress')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @if ($addresses->count())
                        <div class="flex items-end pb-1">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                                <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                                {{ __('account.set_default') }}
                            </label>
                        </div>
                    @else
                        <div></div>
                    @endif
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="px-5 py-2 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors">
                        {{ __('account.save_address') }}
                    </button>
                    <button type="button" @click="showAdd = false"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('account.cancel') }}
                    </button>
                </div>
            </form>
        </div>

    </div>
        </div>
        </div>
    </div>

    {{-- Accordion: Activity --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <button type="button" @click="open = open === 'activity' ? '' : 'activity'"
                class="w-full flex items-center justify-between gap-3 px-5 py-4 text-start hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ __('account.activity_tab') }}</span>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="{ 'rotate-180': open === 'activity' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open === 'activity'" x-collapse x-cloak>
        <div class="border-t border-gray-100 py-4">
    <div x-cloak>

        @if ($activityLogs->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-12 text-center">
                <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ __('account.no_activity') }}</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('account.activity_log') }}</h2>
                </div>

                <ul class="divide-y divide-gray-50">
                    @foreach ($activityLogs as $log)
                        @php
                            $color = $log->eventColor();
                            $bgMap = ['green' => 'bg-green-50', 'gray' => 'bg-gray-50', 'blue' => 'bg-blue-50', 'orange' => 'bg-orange-50', 'indigo' => 'bg-indigo-50', 'teal' => 'bg-teal-50', 'red' => 'bg-red-50'];
                            $iconMap = ['green' => 'text-green-500', 'gray' => 'text-gray-400', 'blue' => 'text-blue-500', 'orange' => 'text-orange-500', 'indigo' => 'text-indigo-500', 'teal' => 'text-teal-500', 'red' => 'text-red-500'];
                            $bg = $bgMap[$color] ?? 'bg-gray-50';
                            $ic = $iconMap[$color] ?? 'text-gray-400';
                        @endphp
                        <li class="flex items-start gap-3 px-5 py-3.5">
                            <div class="w-8 h-8 rounded-lg {{ $bg }} flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 {{ $ic }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $log->eventIcon() }}"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ $log->eventLabel() }}</p>
                                @if ($log->ip_address)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $log->ip_address }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($activityLogs->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">
                        {{ $activityLogs->links() }}
                    </div>
                @endif
            </div>
        @endif

    </div>
        </div>
        </div>
    </div>

    {{-- Accordion: Notifications --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <button type="button" @click="open = open === 'notifications' ? '' : 'notifications'"
                class="w-full flex items-center justify-between gap-3 px-5 py-4 text-start hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ __('account.notifications_tab') }}</span>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="{ 'rotate-180': open === 'notifications' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open === 'notifications'" x-collapse x-cloak>
        <div class="border-t border-gray-100 py-4">
    <div class="space-y-4">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('account.notifications_heading') }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('account.notifications_hint', ['site_name' => $site_name ?? config('app.name')]) }}</p>
            </div>

            <form method="POST" action="{{ route('account.notifications.update') }}" class="px-5 py-5 space-y-5">
                @csrf
                @method('PATCH')

                {{-- Order Updates — always on, not toggleable --}}
                <div class="flex items-start gap-3">
                    <div class="pt-0.5 shrink-0">
                        <div class="w-4 h-4 rounded bg-primary-500 flex items-center justify-center">
                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 12 12">
                                <path d="M10 3L5 8.5 2 5.5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-800">{{ __('account.notify_orders') }}</p>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary-50 text-primary-600 border border-primary-100">
                                {{ __('account.notify_required') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('account.notify_orders_hint') }}</p>
                    </div>
                </div>

                <div class="border-t border-gray-50"></div>

                {{-- Promotions --}}
                <label class="flex items-start gap-3 cursor-pointer group">
                    <div class="pt-0.5">
                        <input
                            type="checkbox"
                            name="notify_promotions"
                            value="1"
                            {{ old('notify_promotions', $user->notify_promotions ?? true) ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500 focus:ring-offset-0 transition">
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 group-hover:text-gray-900 transition-colors">
                            {{ __('account.notify_promotions') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('account.notify_promotions_hint') }}</p>
                    </div>
                </label>

                <div class="border-t border-gray-50"></div>

                {{-- WhatsApp --}}
                <label class="flex items-start gap-3 cursor-pointer group">
                    <div class="pt-0.5">
                        <input
                            type="checkbox"
                            name="notify_whatsapp"
                            value="1"
                            {{ old('notify_whatsapp', $user->notify_whatsapp ?? true) ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500 focus:ring-offset-0 transition">
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 group-hover:text-gray-900 transition-colors">
                            {{ __('account.notify_whatsapp') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('account.notify_whatsapp_hint') }}</p>
                    </div>
                </label>

                <div class="pt-1">
                    <button type="submit"
                        class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors">
                        {{ __('account.save_changes') }}
                    </button>
                </div>
            </form>
        </div>

    </div>
        </div>
        </div>
    </div>

    {{-- Accordion: Balance --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <button type="button" @click="open = open === 'balance' ? '' : 'balance'"
                class="w-full flex items-center justify-between gap-3 px-5 py-4 text-start hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ __('account.balance_tab') }}</span>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="{ 'rotate-180': open === 'balance' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open === 'balance'" x-collapse x-cloak>
        <div class="border-t border-gray-100 py-4">
    <div class="space-y-4">

        {{-- Summary cards --}}
        @if ($balanceTotals)
            <div class="grid gap-3 sm:grid-cols-{{ count($balanceTotals) > 1 ? count($balanceTotals) : '1' }}">
                @foreach ($balanceTotals as $currency => $totals)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4">
                        @php $currencyKey = 'order.currency_'.strtolower($currency); $currencyLabel = __($currencyKey) !== $currencyKey ? __($currencyKey) : $currency; @endphp
                        <p class="text-xs text-gray-400 mb-1">{{ __('account.balance_net') }} — {{ $currencyLabel }}</p>
                        <p class="text-2xl font-bold {{ $totals['net'] >= 0 ? 'text-green-600' : 'text-red-500' }}">
                            {{ number_format($totals['net'], 2) }}
                            <span class="text-base font-medium">{{ $currencyLabel }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Transaction list --}}
        @if ($balanceTransactions->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-12 text-center">
                <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ __('account.balance_no_transactions') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('account.balance_no_transactions_hint') }}</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('account.balance_current') }}</h2>
                </div>

                {{-- Mobile: stacked cards --}}
                <div class="sm:hidden divide-y divide-gray-50">
                    @foreach ($balanceTransactions as $tx)
                        <div class="px-5 py-4 flex items-start gap-3">
                            <div class="shrink-0 mt-0.5">
                                @if ($tx->type === 'credit')
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50">
                                        <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50">
                                        <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                        </svg>
                                    </span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold {{ $tx->type === 'credit' ? 'text-green-600' : 'text-red-500' }}">
                                        {{ $tx->type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount, 2) }} {{ $tx->currency }}
                                    </span>
                                    <span class="text-xs text-gray-400 shrink-0">{{ $tx->date->format('d M Y') }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 break-words">{{ $tx->note }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop: table --}}
                <table class="hidden sm:table w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-400 uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-start font-medium">{{ __('account.balance_date') }}</th>
                            <th class="px-5 py-3 text-start font-medium">{{ __('account.balance_type') }}</th>
                            <th class="px-5 py-3 text-start font-medium">{{ __('account.balance_amount') }}</th>
                            <th class="px-5 py-3 text-start font-medium">{{ __('account.balance_note') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($balanceTransactions as $tx)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3.5 text-gray-500 whitespace-nowrap">{{ $tx->date->format('d M Y') }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $tx->type === 'credit' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' }}">
                                        {{ $tx->type === 'credit' ? __('account.balance_credit') : __('account.balance_debit') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-semibold whitespace-nowrap
                                    {{ $tx->type === 'credit' ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $tx->type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount, 2) }} {{ $tx->currency }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-600 max-w-xs">{{ $tx->note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($balanceTransactions->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">
                        {{ $balanceTransactions->links() }}
                    </div>
                @endif
            </div>
        @endif

    </div>
        </div>
        </div>
    </div>

    </div>{{-- end space-y-2 --}}
</div>

</x-app-layout>
