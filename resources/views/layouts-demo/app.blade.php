<x-app-layout>
    <x-slot name="title">{{ __('layouts_demo.app_layout') }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ __('layouts_demo.app_layout') }}</h1>
        <p class="text-gray-600">{{ __('layouts_demo.this_is_layout', ['name' => __('layouts_demo.app_layout')]) }}</p>
    </div>
</x-app-layout>
