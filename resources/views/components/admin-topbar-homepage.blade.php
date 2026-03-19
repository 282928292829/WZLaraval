<div class="flex items-center gap-1">
    <a href="{{ url('/') }}"
       class="fi-topbar-nav-button-desktop flex items-center rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-500/10 focus-visible:bg-gray-500/10 dark:hover:bg-gray-400/10 dark:focus-visible:bg-gray-400/10"
       title="{{ __('Homepage') }}"
       aria-label="{{ __('Homepage') }}">
        {{ __('Homepage') }}
    </a>
    <a href="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
       class="fi-topbar-nav-button-desktop flex items-center rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-500/10 focus-visible:bg-gray-500/10 dark:hover:bg-gray-400/10 dark:focus-visible:bg-gray-400/10"
       title="{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}">
        {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
    </a>
</div>
