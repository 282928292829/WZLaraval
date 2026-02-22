<nav x-data="{ open: false, infoOpen: false }"
     x-effect="document.body.style.overflow = open ? 'hidden' : ''"
     @keydown.escape.window="open = false; infoOpen = false"
     class="bg-white border-b border-gray-100 sticky top-0 z-40">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14">

            {{-- Logo + Desktop nav --}}
            <div class="flex items-center gap-6">
                <a href="{{ url('/') }}"
                   class="text-lg font-bold text-primary-600 tracking-tight shrink-0">
                    {{ __('app.name') }}
                </a>

                {{-- Desktop nav links --}}
                <div class="hidden sm:flex items-center gap-1">

                    <a href="{{ url('/new-order') }}"
                       class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('New Order') }}
                    </a>

                    @guest
                        <a href="{{ url('/orders') }}"
                           class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                            {{ __('My Orders') }}
                        </a>
                    @endguest

                    @auth
                        @if (auth()->user()->hasAnyRole(['customer']))
                            <a href="{{ url('/orders') }}"
                               class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                {{ __('My Orders') }}
                            </a>
                        @endif

                        @if (auth()->user()->hasAnyRole(['editor', 'admin', 'superadmin']))
                            <a href="{{ url('/orders') }}"
                               class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                {{ __('All Orders') }}
                            </a>
                            @can('view-all-orders')
                                @php $inboxUnread = \App\Models\Activity::whereNull('read_at')->count(); @endphp
                                <a href="{{ route('inbox.index') }}"
                                   class="relative px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                    {{ __('inbox.inbox') }}
                                    @if ($inboxUnread > 0)
                                        <span class="absolute top-1 end-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                    @endif
                                </a>
                            @endcan
                        @endif

                        @if (auth()->user()->hasAnyRole(['admin', 'superadmin']))
                            <a href="{{ url('/admin') }}"
                               class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                {{ __('Admin Panel') }}
                            </a>
                        @endif
                    @endauth

                    {{-- General Info dropdown --}}
                    <div class="relative" x-data="{ infoOpen: false }" @click.outside="infoOpen = false">
                        <button @click="infoOpen = !infoOpen"
                                :aria-expanded="infoOpen"
                                class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                            {{ __('nav.general_info') }}
                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="{ 'rotate-180': infoOpen }"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="infoOpen"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute start-0 top-full mt-1 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50"
                             style="display: none;">
                            <a href="{{ url('/pages/how-to-order') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                {{ __('nav.how_to_order') }}
                            </a>
                            <a href="{{ url('/pages/calculator') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                {{ __('nav.calculator') }}
                            </a>
                            <a href="{{ url('/pages/shipping-calculator') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                {{ __('nav.shipping_calculator') }}
                            </a>
                            <a href="{{ url('/pages/payment-methods') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                {{ __('nav.payment_methods') }}
                            </a>
                            <a href="{{ url('/pages/membership') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                {{ __('nav.membership') }}
                            </a>
                            <a href="{{ url('/pages/faq') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                {{ __('nav.faq') }}
                            </a>
                            <a href="{{ url('/pages/testimonials') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                {{ __('nav.testimonials') }}
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Right side: auth controls + hamburger --}}
            <div class="flex items-center gap-1.5">

                @guest
                    <div class="hidden sm:flex items-center gap-2 ms-1">
                        <a href="{{ route('login') }}"
                           class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            {{ __('Log in') }}
                        </a>
                        <a href="{{ route('register') }}"
                           class="px-3 py-1.5 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors">
                            {{ __('Register') }}
                        </a>
                    </div>
                @endguest

                @auth
                    <div class="hidden sm:block relative ms-1" x-data="{ accountOpen: false }" @click.outside="accountOpen = false">
                        <button @click="accountOpen = !accountOpen"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ __('nav.my_account') }}
                            <svg class="w-3 h-3 transition-transform" :class="accountOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="accountOpen"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             x-cloak
                             class="absolute end-0 mt-1.5 w-44 bg-white rounded-xl border border-gray-100 shadow-lg py-1 z-50">

                            <a href="{{ route('account.index') }}"
                               class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ __('nav.my_account') }}
                            </a>

                            <div class="border-t border-gray-100 my-1"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth

                {{-- Mobile hamburger --}}
                <button @click="open = true"
                        class="sm:hidden p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors"
                        aria-label="{{ __('nav.open_menu') }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile full-screen overlay --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-white overflow-y-auto sm:hidden"
         style="display: none;"
         role="dialog"
         aria-modal="true">

        {{-- Overlay header bar --}}
        <div class="flex items-center justify-between h-14 px-4 border-b border-gray-100">
            <span class="text-sm font-semibold text-gray-700">{{ __('nav.menu') }}</span>
            <button @click="open = false"
                    class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors"
                    aria-label="{{ __('nav.close_menu') }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Mobile nav links --}}
        <nav class="px-4 pt-3 pb-2 space-y-0.5">

            <a href="{{ url('/new-order') }}" @click="open = false"
               class="flex items-center px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                {{ __('New Order') }}
            </a>

            @guest
                <a href="{{ url('/orders') }}" @click="open = false"
                   class="flex items-center px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    {{ __('My Orders') }}
                </a>
            @endguest

            @auth
                @if (auth()->user()->hasAnyRole(['customer']))
                    <a href="{{ url('/orders') }}" @click="open = false"
                       class="flex items-center px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('My Orders') }}
                    </a>
                @endif

                @if (auth()->user()->hasAnyRole(['editor', 'admin', 'superadmin']))
                    <a href="{{ url('/orders') }}" @click="open = false"
                       class="flex items-center px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('All Orders') }}
                    </a>
                @endif

                @if (auth()->user()->hasAnyRole(['admin', 'superadmin']))
                    <a href="{{ url('/admin') }}" @click="open = false"
                       class="flex items-center px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('Admin Panel') }}
                    </a>
                @endif
            @endauth

            {{-- General Info accordion --}}
            <div x-data="{ subOpen: false }">
                <button @click="subOpen = !subOpen"
                        class="w-full flex items-center justify-between px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    <span>{{ __('nav.general_info') }}</span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': subOpen }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="subOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="ms-3 mt-1 mb-1 space-y-0 border-s-2 border-gray-100 ps-3"
                     style="display: none;">
                    <a href="{{ url('/pages/how-to-order') }}" @click="open = false"
                       class="block py-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('nav.how_to_order') }}
                    </a>
                    <a href="{{ url('/pages/calculator') }}" @click="open = false"
                       class="block py-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('nav.calculator') }}
                    </a>
                    <a href="{{ url('/pages/shipping-calculator') }}" @click="open = false"
                       class="block py-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('nav.shipping_calculator') }}
                    </a>
                    <a href="{{ url('/pages/payment-methods') }}" @click="open = false"
                       class="block py-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('nav.payment_methods') }}
                    </a>
                    <a href="{{ url('/pages/membership') }}" @click="open = false"
                       class="block py-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('nav.membership') }}
                    </a>
                    <a href="{{ url('/pages/faq') }}" @click="open = false"
                       class="block py-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('nav.faq') }}
                    </a>
                    <a href="{{ url('/pages/testimonials') }}" @click="open = false"
                       class="block py-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        {{ __('nav.testimonials') }}
                    </a>
                </div>
            </div>
        </nav>

        {{-- Mobile user section --}}
        <div class="border-t border-gray-100 px-4 pt-4 pb-8 mt-2">
            @auth
                <div class="mb-4 px-3">
                    <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                </div>

                @can('view-all-orders')
                    <a href="{{ route('inbox.index') }}" @click="open = false"
                       class="flex items-center justify-between px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        <span>{{ __('inbox.inbox') }}</span>
                        @php $inboxUnreadMobile = \App\Models\Activity::whereNull('read_at')->count(); @endphp
                        @if ($inboxUnreadMobile > 0)
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1 text-xs font-bold text-white bg-red-500 rounded-full">
                                {{ $inboxUnreadMobile > 9 ? '9+' : $inboxUnreadMobile }}
                            </span>
                        @endif
                    </a>
                @endcan

                <a href="{{ route('account.index') }}" @click="open = false"
                   class="flex items-center gap-2 px-3 py-3 text-sm font-semibold text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ __('nav.my_account') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" @click="open = false"
                            class="w-full flex items-center gap-2 px-3 py-3 text-sm font-medium text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        تسجيل الخروج
                    </button>
                </form>
            @endauth

            @guest
                <div class="flex items-center gap-3 px-3">
                    <a href="{{ route('login') }}"
                       class="flex-1 text-center py-2.5 text-sm font-medium text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        {{ __('Log in') }}
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex-1 text-center py-2.5 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors">
                        {{ __('Register') }}
                    </a>
                </div>
            @endguest
        </div>

    </div>
</nav>
