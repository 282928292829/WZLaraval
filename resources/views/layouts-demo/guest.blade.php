<x-guest-layout>
    <x-slot name="title">{{ __('layouts_demo.guest_layout') }}</x-slot>

    <h1 class="text-xl font-bold text-gray-900 mb-3">
        <span lang="en">{{ __('layouts_demo.guest_layout', [], 'en') }}</span>
        <span class="text-gray-500 font-normal"> / </span>
        <span lang="ar" dir="rtl">{{ __('layouts_demo.guest_layout', [], 'ar') }}</span>
    </h1>
    <p class="text-sm text-gray-500">
        <span lang="en">{{ __('layouts_demo.this_is_layout', ['name' => __('layouts_demo.guest_layout', [], 'en')], 'en') }}</span>
        <span class="text-gray-500"> / </span>
        <span lang="ar" dir="rtl">{{ __('layouts_demo.this_is_layout', ['name' => __('layouts_demo.guest_layout', [], 'ar')], 'ar') }}</span>
    </p>
</x-guest-layout>
