{{-- Login modal for new-order (shared by Option 1, 3, 4) — x-show with $wire guard prevents false display during Livewire morph. Default hidden so overlay never blocks clicks before Alpine runs. --}}
<div class="order-login-modal-overlay fixed inset-0 bg-black/70 z-[9999] flex items-center justify-center p-5"
     x-show="($wire && $wire.showLoginModal) === true"
     x-cloak
     style="display: none"
     style="display: none"
     style="display: none !important"
     :style="($wire && $wire.showLoginModal) ? { display: 'flex' } : {}"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click.self="$wire && $wire.closeModal()"
     wire:key="order-login-modal-overlay">
    <div class="order-login-modal">
        <div class="py-8 px-8 pb-5 border-b border-primary-100 relative">
            <button type="button" class="absolute top-5 start-5 w-8 h-8 flex items-center justify-center rounded-full text-slate-400 text-3xl border-none bg-transparent cursor-pointer hover:bg-black/5 hover:text-slate-800 transition-colors" @click="$wire.closeModal()">&times;</button>
            <h2 class="text-2xl font-bold text-slate-800 mb-2.5 text-center">
                <span x-show="$wire.loginModalReason === 'submit'">{{ __('order_form.modal_title') }}</span>
                <span x-show="$wire.loginModalReason === 'attach'" x-cloak>{{ __('order_form.modal_title_attach') }}</span>
            </h2>
            <p class="text-sm text-slate-500 text-center m-0" x-show="$wire.loginModalReason === 'submit'">✅ {{ __('order_form.data_saved') }} {{ __('order_form.modal_email_hint') }}</p>
            <p class="text-sm text-slate-500 text-center m-0" x-show="$wire.loginModalReason === 'attach'" x-cloak>✅ {{ __('order_form.modal_subtitle_attach') }}</p>
        </div>
        <div class="p-8" x-data="{ showPassword: false }">
            <div class="py-3 px-4 rounded-lg mb-5 font-medium text-sm hidden bg-red-100 text-red-900 border border-red-200"
                 :class="{ '!block': $wire.modalError }"
                 x-show="$wire.modalError">
                ❌ <span x-text="$wire.modalError"></span>
            </div>

            <form class="order-modal-form" :class="{ 'active': $wire.modalStep === 'email' }"
                  x-show="$wire.modalStep === 'email'"
                  @submit.prevent="$wire.checkModalEmail()">
                <div class="mb-5">
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('order_form.modal_enter_email') }}</label>
                    <input type="email" wire:model="modalEmail" required autocomplete="email"
                           class="order-form-input w-full px-4 py-3 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10"
                           placeholder="{{ __('Email') }}">
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors">
                    {{ __('Continue') }}
                </button>
            </form>

            <form class="order-modal-form" :class="{ 'active': $wire.modalStep === 'login' }"
                  x-show="$wire.modalStep === 'login'" x-cloak
                  @submit.prevent="$wire.loginFromModal()">
                <div class="mb-5">
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('order_form.welcome_back') }}</label>
                    <div class="text-sm text-slate-500 mb-2.5">
                        <strong x-text="$wire.modalEmail"></strong>
                        <a href="#" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', '')"
                           class="ms-2.5 text-primary-500 font-medium no-underline hover:text-primary-600 hover:underline">
                            {{ __('Change') }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="modalPassword" required autocomplete="current-password"
                               class="order-form-input flex-1 w-full px-4 py-3 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10">
                        <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? '{{ __('order_form.hide_password') }}' : '{{ __('order_form.show_password') }}'"
                                class="shrink-0 py-2 px-3 text-xs text-slate-500 bg-slate-100 border-none rounded-lg cursor-pointer">
                            <span x-text="showPassword ? '{{ __('order_form.hide_password') }}' : '{{ __('order_form.show_password') }}'"></span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors">
                    {{ __('Log in') }}
                </button>
                <div class="text-center mt-4">
                    <a href="#" class="text-primary-500 font-medium text-sm no-underline hover:text-primary-600 hover:underline" @click.prevent="$wire.set('modalStep', 'reset')">{{ __('Forgot password?') }}</a>
                </div>
            </form>

            <form class="order-modal-form" :class="{ 'active': $wire.modalStep === 'register' }"
                  x-show="$wire.modalStep === 'register'" x-cloak
                  @submit.prevent="$wire.registerFromModal()">
                <div class="mb-5">
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('order_form.no_account') }}</label>
                    <div class="text-sm text-slate-500 mb-2.5">
                        <strong x-text="$wire.modalEmail"></strong>
                        <a href="#" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', '')"
                           class="ms-2.5 text-primary-500 font-medium no-underline hover:text-primary-600 hover:underline">
                            {{ __('Change') }}
                        </a>
                    </div>
                    <p class="text-sm text-slate-500 my-2.5">{{ __('order_form.password_create_hint') }}</p>
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('Password') }}</label>
                    <div class="flex items-center gap-2">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="modalPassword" required autocomplete="new-password" minlength="4"
                               class="order-form-input flex-1 w-full px-4 py-3 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10">
                        <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? '{{ __('order_form.hide_password') }}' : '{{ __('order_form.show_password') }}'"
                                class="shrink-0 py-2 px-3 text-xs text-slate-500 bg-slate-100 border-none rounded-lg cursor-pointer">
                            <span x-text="showPassword ? '{{ __('order_form.hide_password') }}' : '{{ __('order_form.show_password') }}'"></span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors">
                    {{ __('Create account and continue') }}
                </button>
            </form>

            <form class="order-modal-form" :class="{ 'active': $wire.modalStep === 'reset' }"
                  x-show="$wire.modalStep === 'reset'" x-cloak
                  @submit.prevent="$wire.sendModalResetLink()">
                <div class="mb-5">
                    <p class="text-sm text-slate-600 mb-3">{{ __('order_form.reset_desc') }}</p>
                    <input type="email" wire:model="modalEmail" required autocomplete="email"
                           class="order-form-input w-full px-4 py-3 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10"
                           placeholder="{{ __('Email') }}">
                    @if ($modalError && $modalStep === 'reset')
                        <p class="text-red-500 text-xs mt-1">{{ $modalError }}</p>
                    @endif
                    @if ($modalSuccess)
                        <p class="text-green-600 text-xs mt-1">{{ $modalSuccess }}</p>
                    @endif
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-60" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="sendModalResetLink">{{ __('order_form.reset_send_link') }}</span>
                    <span wire:loading wire:target="sendModalResetLink">...</span>
                </button>
                <div class="text-center mt-4">
                    <a href="#" class="text-primary-500 font-medium text-sm no-underline hover:text-primary-600 hover:underline" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', ''); $wire.set('modalSuccess', '')">{{ __('Return') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
