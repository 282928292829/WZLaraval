<a
    href="{{ url('/') }}"
    target="_blank"
    rel="noopener noreferrer"
    class="fi-topbar-item-btn inline-flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 outline-none transition duration-75 hover:bg-gray-50 hover:text-gray-900 focus:bg-gray-50 focus:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-50 dark:focus:bg-white/5 dark:focus:text-gray-50"
>
    {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedHome, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['fi-topbar-item-icon size-5'])) }}
    <span class="fi-topbar-item-label">{{ __('Homepage') }}</span>
</a>
