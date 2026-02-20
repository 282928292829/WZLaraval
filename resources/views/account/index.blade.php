@php
    $roleLabel = match(true) {
        $user->hasRole('superadmin') => ['label' => __('Super Admin'), 'class' => 'bg-purple-100 text-purple-700'],
        $user->hasRole('admin')      => ['label' => __('Admin'),       'class' => 'bg-indigo-100 text-indigo-700'],
        $user->hasRole('editor')     => ['label' => __('Editor'),      'class' => 'bg-teal-100 text-teal-700'],
        default                      => ['label' => __('Customer'),    'class' => 'bg-gray-100 text-gray-600'],
    };

    $hasErrors      = $errors->any();
    $openAddressAdd = $hasErrors && old('_form') === 'add_address';
    $openEditId     = $hasErrors && old('_form') === 'edit_address' ? (int) old('_address_id') : null;
@endphp

<x-app-layout>

{{-- ── User identity header ──────────────────────────────────────────────── --}}
<div class="bg-white border-b border-gray-100">
    <div class="max-w-3xl mx-auto px-4 py-5 sm:py-6 flex items-center gap-4">
        {{-- Avatar (initials) --}}
        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-primary-100 flex items-center justify-center shrink-0 select-none">
            <span class="text-xl sm:text-2xl font-bold text-primary-600 leading-none">
                {{ $user->initials() }}
            </span>
        </div>

        <div class="min-w-0 flex-1">
            <h1 class="text-lg font-bold text-gray-900 truncate">{{ $user->name }}</h1>
            <p class="text-sm text-gray-500 truncate mt-0.5">{{ $user->email }}</p>
            <span class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $roleLabel['class'] }}">
                {{ $roleLabel['label'] }}
            </span>
        </div>
    </div>
</div>

{{-- ── Tabs + content ──────────────────────────────────────────────────────── --}}
<div class="max-w-3xl mx-auto px-4 py-6 sm:py-8"
     x-data="{ tab: '{{ $tab }}' }">

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
    @endif

    @if ($hasErrors)
        <div class="mb-5 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Tab navigation --}}
    <div class="flex border-b border-gray-200 -mx-4 px-4 mb-6 overflow-x-auto scrollbar-hide">
        <button
            @click="tab = 'profile'"
            :class="tab === 'profile' ? 'border-primary-500 text-primary-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="flex items-center gap-2 px-4 py-3 text-sm border-b-2 transition-colors whitespace-nowrap shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            {{ __('account.profile_tab') }}
        </button>
        <button
            @click="tab = 'addresses'"
            :class="tab === 'addresses' ? 'border-primary-500 text-primary-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="flex items-center gap-2 px-4 py-3 text-sm border-b-2 transition-colors whitespace-nowrap shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ __('account.addresses_tab') }}
            @if ($addresses->count())
                <span class="text-xs bg-gray-100 text-gray-600 rounded-full px-1.5 py-0.5 leading-none">
                    {{ $addresses->count() }}
                </span>
            @endif
        </button>
        <button
            @click="tab = 'activity'"
            :class="tab === 'activity' ? 'border-primary-500 text-primary-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="flex items-center gap-2 px-4 py-3 text-sm border-b-2 transition-colors whitespace-nowrap shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('account.activity_tab') }}
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- PROFILE TAB                                                           --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'profile'" x-cloak class="space-y-6">

        {{-- Personal info --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('account.personal_info') }}</h2>
            </div>
            <form method="POST" action="{{ route('account.profile.update') }}" class="px-5 py-5 space-y-4">
                @csrf
                @method('PATCH')

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-xs font-medium text-gray-600 mb-1.5">
                        {{ __('Name') }}
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
                        class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-medium text-gray-600 mb-1.5">
                        {{ __('Email') }}
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="email"
                        class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @if ($user->email_verified_at === null)
                        <p class="mt-1.5 text-xs text-amber-600">{{ __('account.email_unverified') }}</p>
                    @endif
                </div>

                {{-- Phone --}}
                <div>
                    <label for="phone" class="block text-xs font-medium text-gray-600 mb-1.5">
                        {{ __('Phone') }}
                        <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                    </label>
                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        value="{{ old('phone', $user->phone) }}"
                        autocomplete="tel"
                        class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1">
                    <button type="submit"
                        class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors">
                        {{ __('account.save_changes') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Change password --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('account.change_password') }}</h2>
            </div>
            <form method="POST" action="{{ route('account.password.update') }}" class="px-5 py-5 space-y-4">
                @csrf
                @method('PATCH')

                {{-- Current password --}}
                <div>
                    <label for="current_password" class="block text-xs font-medium text-gray-600 mb-1.5">
                        {{ __('account.current_password') }}
                    </label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- New password --}}
                <div>
                    <label for="password" class="block text-xs font-medium text-gray-600 mb-1.5">
                        {{ __('account.new_password') }}
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm password --}}
                <div>
                    <label for="password_confirmation" class="block text-xs font-medium text-gray-600 mb-1.5">
                        {{ __('account.confirm_password') }}
                    </label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="block w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1">
                    <button type="submit"
                        class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-white bg-gray-800 hover:bg-gray-900 rounded-xl transition-colors">
                        {{ __('account.update_password') }}
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ADDRESSES TAB                                                         --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'addresses'" x-cloak
         x-data="{
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

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.address_label') }}</label>
                                <input type="text" name="label"
                                    value="{{ old('label', $openEditId === $address->id ? old('label') : $address->label) }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                    placeholder="{{ __('account.label_placeholder') }}">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.recipient_name') }}</label>
                                <input type="text" name="recipient_name"
                                    value="{{ $openEditId === $address->id ? old('recipient_name') : $address->recipient_name }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.country') }} <span class="text-red-400">*</span></label>
                                <input type="text" name="country" required
                                    value="{{ $openEditId === $address->id ? old('country') : $address->country }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.city') }} <span class="text-red-400">*</span></label>
                                <input type="text" name="city" required
                                    value="{{ $openEditId === $address->id ? old('city') : $address->city }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.address') }} <span class="text-red-400">*</span></label>
                            <textarea name="address" required rows="2"
                                class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition resize-none">{{ $openEditId === $address->id ? old('address') : $address->address }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Phone') }}</label>
                                <input type="tel" name="phone"
                                    value="{{ $openEditId === $address->id ? old('phone') : $address->phone }}"
                                    class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
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

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.address_label') }}</label>
                        <input type="text" name="label" value="{{ old('label') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                            placeholder="{{ __('account.label_placeholder') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.recipient_name') }}</label>
                        <input type="text" name="recipient_name" value="{{ old('recipient_name') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.country') }} <span class="text-red-400">*</span></label>
                        <input type="text" name="country" required value="{{ old('country') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.city') }} <span class="text-red-400">*</span></label>
                        <input type="text" name="city" required value="{{ old('city') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.address') }} <span class="text-red-400">*</span></label>
                    <textarea name="address" required rows="2"
                        class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition resize-none"
                        placeholder="{{ __('account.address_placeholder') }}">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Phone') }}</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}"
                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                            <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
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
                    <button type="button" @click="showAdd = false"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('account.cancel') }}
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ACTIVITY LOG TAB                                                      --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'activity'" x-cloak>

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

</x-app-layout>
