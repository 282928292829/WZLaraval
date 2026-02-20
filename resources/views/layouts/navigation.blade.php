<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14">

            {{-- Logo + desktop nav links --}}
            <div class="flex items-center gap-6">
                <a href="{{ url('/') }}"
                   class="text-lg font-bold text-primary-600 tracking-tight shrink-0">
                    {{ __('app.name') }}
                </a>

                {{-- Desktop nav links --}}
                <div class="hidden sm:flex items-center gap-1">
                    @guest
                        <a href="{{ url('/new-order') }}"
                           class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                            {{ __('New Order') }}
                        </a>
                        <a href="{{ route('blog.index') }}"
                           class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                            {{ __('blog.blog') }}
                        </a>
                    @endguest

                    @auth
                        @if (auth()->user()->hasAnyRole(['customer']))
                            <a href="{{ url('/new-order') }}"
                               class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                {{ __('New Order') }}
                            </a>
                            <a href="{{ url('/orders') }}"
                               class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                {{ __('My Orders') }}
                            </a>
                            <a href="{{ route('blog.index') }}"
                               class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                {{ __('blog.blog') }}
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
                </div>
            </div>

            {{-- Right side: language toggle + auth controls --}}
            <div class="flex items-center gap-2">

                {{-- Language toggle (desktop) --}}
                <div class="hidden sm:block">
                    @if (app()->getLocale() === 'ar')
                        <form method="POST" action="{{ route('language.switch', 'en') }}">
                            @csrf
                            <button type="submit"
                                    class="px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                                English
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('language.switch', 'ar') }}">
                            @csrf
                            <button type="submit"
                                    class="px-2.5 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                                    style="font-family: 'IBM Plex Sans Arabic', sans-serif;">
                                العربية
                            </button>
                        </form>
                    @endif
                </div>

                @guest
                    {{-- Guest: login + register buttons --}}
                    <div class="hidden sm:flex items-center gap-2">
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
                    {{-- Authenticated: user dropdown --}}
                    <div class="hidden sm:block relative" x-data="{ userOpen: false }" @click.outside="userOpen = false">
                        <button @click="userOpen = !userOpen"
                                class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                            <span class="max-w-[120px] truncate">{{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': userOpen }"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="userOpen"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute end-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50"
                             style="display: none;">

                            <a href="{{ route('account.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ __('account.my_account') }}
                            </a>

                            <div class="border-t border-gray-100 my-1"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors text-start">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth

                {{-- Mobile hamburger --}}
                <button @click="open = !open"
                        class="sm:hidden p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" :class="{ 'hidden': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="w-5 h-5" :class="{ 'hidden': !open }" class="hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="sm:hidden border-t border-gray-100 bg-white"
         style="display: none;">

        {{-- Mobile nav links --}}
        <div class="px-4 pt-3 pb-2 space-y-1">
            @guest
                <a href="{{ url('/new-order') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    {{ __('New Order') }}
                </a>
                <a href="{{ route('blog.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    {{ __('blog.blog') }}
                </a>
            @endguest

            @auth
                @if (auth()->user()->hasAnyRole(['customer']))
                    <a href="{{ url('/new-order') }}"
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('New Order') }}
                    </a>
                    <a href="{{ url('/orders') }}"
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('My Orders') }}
                    </a>
                    <a href="{{ route('blog.index') }}"
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('blog.blog') }}
                    </a>
                @endif

                @if (auth()->user()->hasAnyRole(['editor', 'admin', 'superadmin']))
                    <a href="{{ url('/orders') }}"
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('All Orders') }}
                    </a>
                @endif

                @if (auth()->user()->hasAnyRole(['admin', 'superadmin']))
                    <a href="{{ url('/admin') }}"
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('Admin Panel') }}
                    </a>
                @endif
            @endauth
        </div>

        {{-- Mobile: user info + auth actions --}}
        <div class="border-t border-gray-100 px-4 pt-3 pb-4">
            @auth
                <div class="mb-3 px-3">
                    <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                </div>

                <a href="{{ route('account.index') }}"
                   class="flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    {{ __('account.my_account') }}
                </a>

                @can('view-all-orders')
                    <a href="{{ route('inbox.index') }}"
                       class="flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        {{ __('inbox.inbox') }}
                        @php $inboxUnreadMobile = \App\Models\Activity::whereNull('read_at')->count(); @endphp
                        @if ($inboxUnreadMobile > 0)
                            <span class="ms-auto inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                                {{ $inboxUnreadMobile > 9 ? '9+' : $inboxUnreadMobile }}
                            </span>
                        @endif
                    </a>
                @endcan

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors text-start">
                        {{ __('Log Out') }}
                    </button>
                </form>
            @endauth

            @guest
                <div class="flex items-center gap-2 px-3">
                    <a href="{{ route('login') }}"
                       class="flex-1 text-center py-2 text-sm font-medium text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        {{ __('Log in') }}
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex-1 text-center py-2 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors">
                        {{ __('Register') }}
                    </a>
                </div>
            @endguest

            {{-- Mobile: language toggle --}}
            <div class="mt-3 px-3">
                @if (app()->getLocale() === 'ar')
                    <form method="POST" action="{{ route('language.switch', 'en') }}">
                        @csrf
                        <button type="submit"
                                class="text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                            Switch to English
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('language.switch', 'ar') }}">
                        @csrf
                        <button type="submit"
                                class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors"
                                style="font-family: 'IBM Plex Sans Arabic', sans-serif;">
                            التبديل إلى العربية
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</nav>
