<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;

class TranslationsPage extends Page
{
    protected string $view = 'filament.pages.translations-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    public static function getNavigationLabel(): string
    {
        return __('Translations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    protected static ?int $navigationSort = 11;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('Translations Editor');
    }

    /** @var array<int, array{key: string, ar: string, en: string}> */
    public array $rows = [];

    /** New key/value inputs for adding a translation */
    public string $newKey = '';

    public string $newAr = '';

    public string $newEn = '';

    public string $search = '';

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    public function mount(): void
    {
        $this->loadRows();
    }

    protected function loadRows(): void
    {
        $arPath = lang_path('ar.json');
        $enPath = lang_path('en.json');

        $ar = File::exists($arPath) ? json_decode(File::get($arPath), true) : [];
        $en = File::exists($enPath) ? json_decode(File::get($enPath), true) : [];

        $keys = array_unique(array_merge(array_keys($ar), array_keys($en)));
        sort($keys);

        $this->rows = array_map(fn ($key) => [
            'key' => $key,
            'ar'  => $ar[$key] ?? '',
            'en'  => $en[$key] ?? '',
        ], $keys);
    }

    /** @return array<int, array{key: string, ar: string, en: string}> */
    public function filteredRows(): array
    {
        if (empty($this->search)) {
            return $this->rows;
        }

        $term = mb_strtolower($this->search);

        return array_values(array_filter($this->rows, function ($row) use ($term) {
            return str_contains(mb_strtolower($row['key']), $term)
                || str_contains(mb_strtolower($row['ar']), $term)
                || str_contains(mb_strtolower($row['en']), $term);
        }));
    }

    public function save(): void
    {
        $this->writeFiles($this->rows);

        Notification::make()
            ->title(__('Translations saved'))
            ->success()
            ->send();
    }

    public function addRow(): void
    {
        $key = trim($this->newKey);

        if (empty($key)) {
            Notification::make()->title(__('Key is required'))->warning()->send();

            return;
        }

        // Check for duplicate key
        foreach ($this->rows as $row) {
            if ($row['key'] === $key) {
                Notification::make()->title(__('Key already exists'))->warning()->send();

                return;
            }
        }

        $this->rows[] = [
            'key' => $key,
            'ar'  => trim($this->newAr),
            'en'  => trim($this->newEn),
        ];

        // Sort rows alphabetically by key
        usort($this->rows, fn ($a, $b) => strcmp($a['key'], $b['key']));

        $this->newKey = '';
        $this->newAr  = '';
        $this->newEn  = '';

        $this->writeFiles($this->rows);

        Notification::make()->title(__('Translation added'))->success()->send();
    }

    public function deleteRow(string $key): void
    {
        $this->rows = array_values(array_filter(
            $this->rows,
            fn ($row) => $row['key'] !== $key
        ));

        $this->writeFiles($this->rows);

        Notification::make()->title(__('Translation deleted'))->success()->send();
    }

    /** @param array<int, array{key: string, ar: string, en: string}> $rows */
    protected function writeFiles(array $rows): void
    {
        $ar = [];
        $en = [];

        foreach ($rows as $row) {
            $ar[$row['key']] = $row['ar'];
            $en[$row['key']] = $row['en'];
        }

        File::put(lang_path('ar.json'), json_encode($ar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        File::put(lang_path('en.json'), json_encode($en, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
