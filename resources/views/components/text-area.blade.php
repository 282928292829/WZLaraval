@props(['disabled' => false])

<textarea
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-lg shadow-sm text-sm px-3 py-2.5 w-full disabled:bg-gray-50 disabled:text-gray-500 transition-colors',
    ]) }}
>{{ $slot }}</textarea>
