<x-guest-layout>
    <x-slot name="title">{{ __('layouts_demo.guest_layout') }}</x-slot>

    <h1 class="text-xl font-bold text-gray-900 mb-3">{{ __('layouts_demo.guest_layout') }}</h1>
    <p class="text-sm text-gray-500">{{ __('layouts_demo.this_is_layout', ['name' => __('layouts_demo.guest_layout')]) }}</p>
</x-guest-layout>
