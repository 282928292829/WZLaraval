<div
    x-show="$store.sidebar.isOpen"
    x-cloak
    x-transition
    class="flex items-center"
>
    <a
        href="{{ url('/') }}"
        target="_blank"
        rel="noopener noreferrer"
        class="fi-sidebar-item-button ms-2 inline-flex items-center gap-x-2 rounded-lg px-2 py-1.5 text-xs font-medium text-gray-600 outline-none transition duration-75 hover:bg-gray-100 hover:text-gray-950 focus:bg-gray-100 focus:text-gray-950 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white dark:focus:bg-white/5 dark:focus:text-white"
    >
        <span class="fi-sidebar-item-label">{{ __('Homepage') }}</span>
    </a>
</div>
