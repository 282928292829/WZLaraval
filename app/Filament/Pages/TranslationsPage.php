<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;

class TranslationsPage extends Page implements HasTable
{
    use \Filament\Tables\Concerns\InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    /** @return array<string> */
    protected static function getSupportedLocales(): array
    {
        return config('app.available_locales', ['ar', 'en']);
    }

    public static function getNavigationLabel(): string
    {
        return __('Translations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    protected static ?int $navigationSort = 11;

    public function getTitle(): string|Htmlable
    {
        return __('Translations Editor');
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    protected function makeTable(): Table
    {
        $locales = static::getSupportedLocales();

        $table = Table::make($this)
            ->records(function (?string $search, ?string $sortColumn, ?string $sortDirection, int $page, int $recordsPerPage): LengthAwarePaginator {
                $rows = $this->loadRows();
                $collection = collect($rows)->keyBy('key');

                if (filled($search)) {
                    $term = mb_strtolower($search);
                    $collection = $collection->filter(function (array $row) use ($term): bool {
                        foreach ($row as $value) {
                            if (is_string($value) && str_contains(mb_strtolower($value), $term)) {
                                return true;
                            }
                        }

                        return false;
                    });
                }

                if (filled($sortColumn) && in_array($sortColumn, array_merge(['key'], $this->getLocaleColumns()))) {
                    $asc = $sortDirection !== 'desc';
                    $collection = $collection->sortBy($sortColumn, SORT_REGULAR, ! $asc);
                }

                $total = $collection->count();
                $items = $collection->forPage($page, $recordsPerPage);

                return new LengthAwarePaginator(
                    $items->all(),
                    $total,
                    $recordsPerPage,
                    $page,
                    ['path' => request()->url(), 'query' => request()->query()],
                );
            })
            ->columns($this->buildColumns($locales))
            ->headerActions([
                Action::make('add')
                    ->label(__('translations.add_key'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->form($this->buildAddForm($locales))
                    ->action(function (array $data): void {
                        $this->addTranslation($data);
                    }),
            ])
            ->recordActions([
                Action::make('delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('translations.delete_heading'))
                    ->modalDescription(fn (array $record): string => __('translations.delete_confirm', ['key' => $record['key']]))
                    ->action(function (array $record): void {
                        $this->deleteTranslation($record['key']);
                    }),
            ])
            ->paginated([10, 25, 50, 100, 250, 500])
            ->defaultSort('key', 'asc')
            ->searchable()
            ->striped();

        return $table;
    }

    /** @param array<string> $locales */
    protected function buildColumns(array $locales): array
    {
        $columns = [
            TextColumn::make('key')
                ->label(__('translations.key'))
                ->searchable(false)
                ->sortable()
                ->tooltip(fn (string $state): string => $state)
                ->html()
                ->formatStateUsing(fn (string $state): string => '<code class="text-xs text-gray-500 dark:text-gray-400">'.e(\Illuminate\Support\Str::limit($state, 40)).'</code>'),
        ];

        $labels = config('app.locale_labels', []);
        foreach ($locales as $locale) {
            $label = $labels[$locale] ?? $locale;
            $columns[] = TextInputColumn::make($locale)
                ->label("{$label} ({$locale})")
                ->placeholder(__('translations.placeholder', ['locale' => $label]))
                ->extraInputAttributes(['dir' => in_array($locale, ['ar', 'ar_SA', 'ar_EG']) ? 'rtl' : 'ltr'])
                ->updateStateUsing(function (array $record, $state) use ($locale): mixed {
                    $this->updateTranslation($record['key'], $locale, $state);

                    return $state;
                });
        }

        return $columns;
    }

    /** @param array<string> $locales */
    protected function buildAddForm(array $locales): array
    {
        $fields = [
            \Filament\Forms\Components\TextInput::make('key')
                ->label(__('translations.key'))
                ->required()
                ->rules(['regex:/^[a-zA-Z0-9_.\-]+$/'])
                ->placeholder(__('e.g. Order Submitted')),
        ];

        $labels = config('app.locale_labels', []);
        foreach ($locales as $locale) {
            $label = $labels[$locale] ?? $locale;
            $fields[] = \Filament\Forms\Components\TextInput::make($locale)
                ->label("{$label} ({$locale})")
                ->placeholder(__('translations.placeholder', ['locale' => $label]))
                ->extraInputAttributes(['dir' => in_array($locale, ['ar', 'ar_SA', 'ar_EG']) ? 'rtl' : 'ltr']);
        }

        return $fields;
    }

    /** @return array<string> */
    protected function getLocaleColumns(): array
    {
        return static::getSupportedLocales();
    }

    public function table(Table $table): Table
    {
        return $table;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::CONTENT_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::CONTENT_AFTER),
            ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function loadRows(): array
    {
        $locales = static::getSupportedLocales();
        $merged = [];

        foreach ($locales as $locale) {
            $path = lang_path($locale.'.json');
            if (! File::exists($path)) {
                continue;
            }
            $content = File::get($path);
            $data = json_decode($content, true);
            if (! is_array($data)) {
                continue;
            }
            foreach (array_keys($data) as $key) {
                $merged[$key] = $merged[$key] ?? ['key' => $key];
                $merged[$key][$locale] = $data[$key] ?? '';
            }
        }

        foreach ($merged as $key => $row) {
            foreach ($locales as $locale) {
                $merged[$key][$locale] = $merged[$key][$locale] ?? '';
            }
        }

        $keys = array_keys($merged);
        sort($keys);

        return array_values(array_map(fn (string $key) => $merged[$key], $keys));
    }

    protected function updateTranslation(string $key, string $locale, string $value): void
    {
        $path = lang_path($locale.'.json');

        $data = File::exists($path)
            ? json_decode(File::get($path), true)
            : [];

        if (! is_array($data)) {
            Notification::make()
                ->title(__('translations.error_invalid_json'))
                ->danger()
                ->send();

            return;
        }

        $data[$key] = $value;

        try {
            File::put($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('translations.error_write_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->flushCachedTableRecords();
    }

    /** @param array<string, mixed> $data */
    protected function addTranslation(array $data): void
    {
        $key = trim((string) ($data['key'] ?? ''));
        if ($key === '') {
            Notification::make()->title(__('Key is required'))->warning()->send();

            return;
        }

        if (! preg_match('/^[a-zA-Z0-9_.\-]+$/', $key)) {
            Notification::make()
                ->title(__('translations.error_invalid_key'))
                ->warning()
                ->send();

            return;
        }

        $rows = $this->loadRows();
        foreach ($rows as $row) {
            if (($row['key'] ?? '') === $key) {
                Notification::make()->title(__('Key already exists'))->warning()->send();

                return;
            }
        }

        $locales = static::getSupportedLocales();
        foreach ($locales as $locale) {
            $this->updateTranslation($key, $locale, trim((string) ($data[$locale] ?? '')));
        }

        $this->flushCachedTableRecords();

        Notification::make()->title(__('Translation added'))->success()->send();
    }

    protected function deleteTranslation(string $key): void
    {
        $locales = static::getSupportedLocales();

        foreach ($locales as $locale) {
            $path = lang_path($locale.'.json');
            if (! File::exists($path)) {
                continue;
            }
            $data = json_decode(File::get($path), true);
            if (! is_array($data)) {
                continue;
            }
            unset($data[$key]);
            File::put($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        $this->flushCachedTableRecords();

        Notification::make()->title(__('Translation deleted'))->success()->send();
    }
}
