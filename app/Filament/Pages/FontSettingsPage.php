<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\FontHelper;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class FontSettingsPage extends Page
{
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 11;

    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('Font');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Appearance');
    }

    public static function getNavigationSort(): int
    {
        return 11;
    }

    public function getTitle(): string
    {
        return __('Font Settings');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    public function mount(): void
    {
        $data = $this->defaults();

        foreach (['font_source', 'font_google_url', 'font_uploaded_path', 'font_upload_family_name', 'font_family'] as $key) {
            $value = Setting::get($key, null);
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        $this->data = $data;
        $this->form->fill($this->data);
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'font_source' => '',
            'font_google_url' => '',
            'font_uploaded_path' => '',
            'font_upload_family_name' => '',
            'font_family' => '',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('font.section_title'))
                    ->icon(Heroicon::OutlinedLanguage)
                    ->description(__('font.section_description'))
                    ->schema([
                        Radio::make('font_source')
                            ->label(__('font.source_label'))
                            ->options([
                                '' => __('font.source_default'),
                                'google' => __('font.source_google'),
                                'upload' => __('font.source_upload'),
                            ])
                            ->default('')
                            ->live()
                            ->descriptions([
                                '' => __('font.source_default_help'),
                                'google' => __('font.source_google_help'),
                                'upload' => __('font.source_upload_help'),
                            ])
                            ->columnSpanFull(),

                        Tabs::make(__('font.config_tabs'))
                            ->tabs([
                                Tab::make(__('font.tab_google'))
                                    ->icon(Heroicon::OutlinedGlobeAlt)
                                    ->schema([
                                        TextInput::make('font_google_url')
                                            ->label(__('font.google_url_label'))
                                            ->placeholder('https://fonts.googleapis.com/css2?family=Cairo')
                                            ->url()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (?string $state): void {
                                                $family = FontHelper::extractFontFamilyFromGoogleUrl($state ?? '');
                                                if ($family !== null) {
                                                    $this->data['font_family'] = $family;
                                                    $this->form->fill($this->data);
                                                }
                                            })
                                            ->helperText(__('font.google_url_help'))
                                            ->visible(fn ($get) => $get('font_source') === 'google')
                                            ->columnSpanFull(),
                                    ]),

                                Tab::make(__('font.tab_upload'))
                                    ->icon(Heroicon::OutlinedArrowUpTray)
                                    ->schema([
                                        FileUpload::make('font_uploaded_path')
                                            ->label(__('font.upload_label'))
                                            ->helperText(__('font.upload_help'))
                                            ->directory('fonts')
                                            ->acceptedFileTypes(['font/ttf', 'font/woff', 'font/woff2', 'application/font-woff', 'application/font-woff2', 'application/x-font-ttf', 'application/x-font-woff'])
                                            ->maxSize(2048)
                                            ->visibility('public')
                                            ->visible(fn ($get) => $get('font_source') === 'upload')
                                            ->columnSpanFull(),

                                        TextInput::make('font_upload_family_name')
                                            ->label(__('font.upload_family_label'))
                                            ->placeholder(__('font.upload_family_placeholder'))
                                            ->helperText(__('font.upload_family_help'))
                                            ->visible(fn ($get) => $get('font_source') === 'upload')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->visible(fn ($get) => in_array($get('font_source'), ['google', 'upload']))
                            ->columnSpanFull(),

                        TextInput::make('font_family')
                            ->label(__('font.active_family_label'))
                            ->helperText(__('font.active_family_help'))
                            ->placeholder(FontHelper::DEFAULT_AR)
                            ->visible(fn ($get) => in_array($get('font_source'), ['google', 'upload']))
                            ->columnSpanFull(),

                        Placeholder::make('font_preview')
                            ->label(__('font.preview_label'))
                            ->content(fn (): \Illuminate\Contracts\Support\Htmlable => $this->buildPreviewHtml())
                            ->visible(fn ($get) => in_array($get('font_source'), ['google', 'upload']))
                            ->columnSpanFull()
                            ->live(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function buildPreviewHtml(): \Illuminate\Contracts\Support\Htmlable
    {
        $state = $this->form->getState();
        $source = $state['font_source'] ?? '';
        $family = trim((string) ($state['font_family'] ?? ''));
        $url = trim((string) ($state['font_google_url'] ?? ''));

        if ($source === 'google' && $url !== '') {
            $family = $family ?: FontHelper::extractFontFamilyFromGoogleUrl($url) ?? FontHelper::DEFAULT_AR;
        } elseif ($source === 'upload') {
            $family = $family ?: trim((string) ($state['font_upload_family_name'] ?? '')) ?: FontHelper::DEFAULT_AR;
        }

        if ($family === '') {
            $family = FontHelper::DEFAULT_AR;
        }

        $html = view('filament.components.font-preview', [
            'family' => $family,
            'url' => $url,
            'source' => $source,
        ])->render();

        return new HtmlString($html);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    protected function getFormContentComponent(): \Filament\Schemas\Components\Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('font-settings-form')
            ->live()
            ->livewireSubmitHandler('save')
            ->footer([
                SchemaActions::make($this->getFormActions())
                    ->alignment('end')
                    ->key('form-actions'),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        if (empty($data) || ! is_array($data)) {
            $data = $this->data;
        }

        $source = $data['font_source'] ?? '';

        if ($source === 'google') {
            $url = trim((string) ($data['font_google_url'] ?? ''));
            $family = FontHelper::extractFontFamilyFromGoogleUrl($url) ?? trim((string) ($data['font_family'] ?? ''));

            Setting::set('font_source', 'google', 'string', 'appearance');
            Setting::set('font_google_url', $url, 'string', 'appearance');
            Setting::set('font_family', $family ?: FontHelper::DEFAULT_AR, 'string', 'appearance');
            Setting::set('font_uploaded_path', '', 'string', 'appearance');
            Setting::set('font_upload_family_name', '', 'string', 'appearance');
        } elseif ($source === 'upload') {
            $uploaded = $data['font_uploaded_path'] ?? null;
            $path = is_array($uploaded) ? ($uploaded[0] ?? '') : (string) $uploaded;
            $family = trim((string) ($data['font_upload_family_name'] ?? ($data['font_family'] ?? '')));

            if ($family === '') {
                Notification::make()
                    ->title(__('font.upload_family_required'))
                    ->body(__('font.upload_family_help'))
                    ->danger()
                    ->send();

                return;
            }

            if ($path !== '' && Storage::disk('public')->exists($path)) {
                Setting::set('font_source', 'upload', 'string', 'appearance');
                Setting::set('font_uploaded_path', $path, 'string', 'appearance');
                Setting::set('font_upload_family_name', $family, 'string', 'appearance');
                Setting::set('font_family', $family ?: FontHelper::DEFAULT_AR, 'string', 'appearance');
                Setting::set('font_google_url', '', 'string', 'appearance');
            } else {
                Notification::make()
                    ->title(__('font.upload_required'))
                    ->danger()
                    ->send();

                return;
            }
        } else {
            Setting::set('font_source', '', 'string', 'appearance');
            Setting::set('font_google_url', '', 'string', 'appearance');
            Setting::set('font_uploaded_path', '', 'string', 'appearance');
            Setting::set('font_upload_family_name', '', 'string', 'appearance');
            // Do not clear font_family — main Settings may use it for default fonts
        }

        Cache::flush();

        Notification::make()
            ->title(__('font.saved'))
            ->success()
            ->send();

        $this->mount();
    }
}
