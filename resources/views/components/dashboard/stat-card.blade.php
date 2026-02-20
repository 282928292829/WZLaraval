@props([
    'label'  => '',
    'value'  => 0,
    'color'  => 'gray',
    'icon'   => '',
])

@php
$colorMap = [
    'gray'    => ['bg' => 'bg-gray-50',    'icon' => 'text-gray-400',   'text' => 'text-gray-900'],
    'blue'    => ['bg' => 'bg-blue-50',    'icon' => 'text-blue-500',   'text' => 'text-blue-900'],
    'green'   => ['bg' => 'bg-green-50',   'icon' => 'text-green-500',  'text' => 'text-green-900'],
    'red'     => ['bg' => 'bg-red-50',     'icon' => 'text-red-500',    'text' => 'text-red-900'],
    'orange'  => ['bg' => 'bg-orange-50',  'icon' => 'text-orange-500', 'text' => 'text-orange-900'],
    'indigo'  => ['bg' => 'bg-indigo-50',  'icon' => 'text-indigo-500', 'text' => 'text-indigo-900'],
    'teal'    => ['bg' => 'bg-teal-50',    'icon' => 'text-teal-500',   'text' => 'text-teal-900'],
    'purple'  => ['bg' => 'bg-purple-50',  'icon' => 'text-purple-500', 'text' => 'text-purple-900'],
    'primary' => ['bg' => 'bg-orange-50',  'icon' => 'text-orange-500', 'text' => 'text-orange-900'],
];
$c = $colorMap[$color] ?? $colorMap['gray'];
@endphp

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
    <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-medium text-gray-500 leading-tight">{{ $label }}</span>
        <div class="w-8 h-8 rounded-lg {{ $c['bg'] }} flex items-center justify-center shrink-0">
            <svg class="w-[17px] h-[17px] {{ $c['icon'] }}" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
            </svg>
        </div>
    </div>
    <p class="text-2xl sm:text-3xl font-bold {{ $c['text'] }} tabular-nums">
        {{ number_format($value) }}
    </p>
</div>
