<x-filament-panels::page>
    {{-- Search + Save bar --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('Search keys or values…') }}"
                />
            </x-filament::input.wrapper>
        </div>

        <x-filament::button wire:click="save">
            Save All
        </x-filament::button>
    </div>

    {{-- Translations table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10 text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700 dark:text-gray-200 w-1/3">Key</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700 dark:text-gray-200 w-1/3">Arabic (ar)</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700 dark:text-gray-200 w-1/3">English (en)</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5 bg-white dark:bg-gray-900">
                @forelse ($this->filteredRows() as $index => $row)
                    @php
                        // Map filtered index back to real rows index for wire:model binding
                        $realIndex = array_search($row['key'], array_column($rows, 'key'));
                    @endphp
                    <tr wire:key="row-{{ $row['key'] }}" class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-4 py-2 align-top">
                            <code class="text-xs text-gray-500 dark:text-gray-400 break-all">{{ $row['key'] }}</code>
                        </td>
                        <td class="px-4 py-2 align-top">
                            <textarea
                                wire:model.lazy="rows.{{ $realIndex }}.ar"
                                rows="1"
                                dir="rtl"
                                class="block w-full rounded-lg border border-gray-300 dark:border-white/20 bg-transparent px-3 py-1.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none"
                                style="min-height:2rem;"
                            ></textarea>
                        </td>
                        <td class="px-4 py-2 align-top">
                            <textarea
                                wire:model.lazy="rows.{{ $realIndex }}.en"
                                rows="1"
                                dir="ltr"
                                class="block w-full rounded-lg border border-gray-300 dark:border-white/20 bg-transparent px-3 py-1.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none"
                                style="min-height:2rem;"
                            ></textarea>
                        </td>
                        <td class="px-4 py-2 align-top text-center">
                            <button
                                type="button"
                                wire:click="deleteRow('{{ addslashes($row['key']) }}')"
                                wire:confirm="Delete '{{ addslashes($row['key']) }}'?"
                                class="text-danger-500 hover:text-danger-700 transition-colors"
                                title="{{ __('Delete') }}"
                            >
                                <x-filament::icon icon="heroicon-o-trash" class="w-4 h-4" />
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                            No translations found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add new translation row --}}
    <div class="mt-6 rounded-xl border border-dashed border-gray-300 dark:border-white/20 p-4">
        <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Add Translation Key') }}</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Key</label>
                <input
                    wire:model="newKey"
                    type="text"
                    placeholder="{{ __('e.g. Order Submitted') }}"
                    class="block w-full rounded-lg border border-gray-300 dark:border-white/20 bg-transparent px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Arabic') }}</label>
                <input
                    wire:model="newAr"
                    type="text"
                    dir="rtl"
                    placeholder="النص العربي"
                    class="block w-full rounded-lg border border-gray-300 dark:border-white/20 bg-transparent px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('English') }}</label>
                <input
                    wire:model="newEn"
                    type="text"
                    dir="ltr"
                    placeholder="{{ __('English text') }}"
                    class="block w-full rounded-lg border border-gray-300 dark:border-white/20 bg-transparent px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <x-filament::button wire:click="addRow" color="gray" size="sm">
                Add Key
            </x-filament::button>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
