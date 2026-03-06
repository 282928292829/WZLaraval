<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\SettingsPersistService;
use App\Support\FontHelper;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class AppearanceSettingsPage extends Page
{
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Appearance');
    }

    public function getTitle(): string
    {
        return __('Appearance Settings');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    /** @var list<string> Appearance setting keys to load */
    protected const APPEARANCE_KEYS = [
        'primary_color', 'font_family',
        'logo_use_per_language', 'logo_text_use_per_language',
        'logo_image', 'logo_image_ar', 'logo_image_en',
        'logo_text', 'logo_text_ar', 'logo_text_en',
        'logo_alt', 'logo_alt_ar', 'logo_alt_en',
        'font_source', 'font_google_url', 'font_uploaded_path', 'font_upload_family_name',
    ];

    public function mount(): void
    {
        $data = $this->defaults();
        $allSettings = Setting::all()->keyBy('key');

        foreach (static::APPEARANCE_KEYS as $key) {
            $setting = $allSettings->get($key);
            if ($setting) {
                $data[$key] = $setting->type === 'json'
                    ? json_decode($setting->value, true)
                    : $setting->value;
            }
        }

        $this->data = $data;
        $this->form->fill($this->data);
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'primary_color' => '#f97316',
            'font_family' => 'IBM Plex Sans Arabic',
            'logo_use_per_language' => false,
            'logo_text_use_per_language' => true,
            'logo_image' => '',
            'logo_image_ar' => '',
            'logo_image_en' => '',
            'logo_text' => config('app.name'),
            'logo_text_ar' => '',
            'logo_text_en' => '',
            'logo_alt' => '',
            'logo_alt_ar' => '',
            'logo_alt_en' => '',
            'font_source' => '',
            'font_google_url' => '',
            'font_uploaded_path' => '',
            'font_upload_family_name' => '',
        ];
    }

    /** Build font preview HTML for the Font section. */
    protected function buildFontPreviewHtml(): \Illuminate\Contracts\Support\Htmlable
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

        return new HtmlString(view('filament.components.font-preview', [
            'family' => $family,
            'url' => $url,
            'source' => $source,
        ])->render());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Appearance'))
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->description(__('Logo, colors, and typography. Use per-language when you have different logos or text for Arabic and English.'))
                    ->schema([
                        TextInput::make('primary_color')
                            ->label(__('Primary Color (hex)'))
                            ->placeholder(__('settings.placeholder_hex_color'))
                            ->maxLength(20),

                        TextInput::make('font_family')
                            ->label(__('Font Family'))
                            ->placeholder(__('IBM Plex Sans Arabic')),

                        Toggle::make('logo_use_per_language')
                            ->label(__('Use per-language logo image'))
                            ->helperText(__('When ON, you can upload a different logo image for Arabic and English.'))
                            ->columnSpanFull()
                            ->onColor('success'),

                        Toggle::make('logo_text_use_per_language')
                            ->label(__('Use per-language logo text'))
                            ->helperText(__('When ON, text logo is localized (different text for Arabic and English). When OFF, one text is used for both. Recommended: ON.'))
                            ->default(true)
                            ->columnSpanFull()
                            ->onColor('success'),

                        FileUpload::make('logo_image')
                            ->label(__('Logo Image (all languages)'))
                            ->helperText(__('Shown in header. Recommended: PNG/SVG, max height 48px. Leave empty to show text logo.'))
                            ->image()
                            ->directory('logos')
                            ->maxSize(512)
                            ->nullable()
                            ->visible(fn ($get) => ! $get('logo_use_per_language')),

                        TextInput::make('logo_alt')
                            ->label(__('Logo Alt Text (SEO)'))
                            ->helperText(__('Used in img alt attribute for accessibility and SEO. Leave empty to use logo text.'))
                            ->placeholder(__('Site name and tagline'))
                            ->maxLength(120)
                            ->visible(fn ($get) => ! $get('logo_use_per_language')),

                        FileUpload::make('logo_image_ar')
                            ->label(__('Logo Image').' — '.__('Arabic'))
                            ->helperText(__('Arabic version. Recommended: PNG/SVG, max height 48px.'))
                            ->image()
                            ->directory('logos')
                            ->maxSize(512)
                            ->nullable()
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        FileUpload::make('logo_image_en')
                            ->label(__('Logo Image').' — '.__('English'))
                            ->helperText(__('English version. Recommended: PNG/SVG, max height 48px.'))
                            ->image()
                            ->directory('logos')
                            ->maxSize(512)
                            ->nullable()
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        TextInput::make('logo_alt_ar')
                            ->label(__('Logo Alt Text (SEO)').' — '.__('Arabic'))
                            ->placeholder(__('Site name and tagline'))
                            ->helperText(__('Used in img alt for Arabic pages. Leave empty to use logo text.'))
                            ->maxLength(120)
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        TextInput::make('logo_alt_en')
                            ->label(__('Logo Alt Text (SEO)').' — '.__('English'))
                            ->placeholder(__('Site name and tagline'))
                            ->helperText(__('Used in img alt for English pages. Leave empty to use logo text.'))
                            ->maxLength(120)
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        TextInput::make('logo_text')
                            ->label(__('Logo Text (all languages)'))
                            ->placeholder(config('app.name'))
                            ->helperText(__('Shown when no logo image is uploaded. Used for both Arabic and English when per-language text is OFF. Leave empty to use site name.'))
                            ->visible(fn ($get) => ! $get('logo_text_use_per_language')),

                        TextInput::make('logo_text_ar')
                            ->label(__('Logo Text').' — '.__('Arabic'))
                            ->placeholder(config('app.name'))
                            ->helperText(__('Shown when no logo image is uploaded. Leave empty to use site name.'))
                            ->visible(fn ($get) => (bool) $get('logo_text_use_per_language')),

                        TextInput::make('logo_text_en')
                            ->label(__('Logo Text').' — '.__('English'))
                            ->placeholder(config('app.name'))
                            ->helperText(__('Shown when no logo image is uploaded. Leave empty to use site name.'))
                            ->visible(fn ($get) => (bool) $get('logo_text_use_per_language')),
                    ])
                    ->columns(3)
                    ->collapsible(false),

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
                                            ->placeholder(__('font.google_url_placeholder'))
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
                            ->content(fn (): \Illuminate\Contracts\Support\Htmlable => $this->buildFontPreviewHtml())
                            ->visible(fn ($get) => in_array($get('font_source'), ['google', 'upload']))
                            ->columnSpanFull()
                            ->live(),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ])
            ->statePath('data');
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
            ->id('appearance-settings-form')
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
                ->label(__('Save Settings'))
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

        if (! $this->saveFontSettings($data)) {
            return;
        }

        $groupMap = [
            'primary_color' => 'appearance',
            'font_family' => 'appearance',
            'logo_use_per_language' => 'appearance',
            'logo_text_use_per_language' => 'appearance',
            'logo_image' => 'appearance',
            'logo_image_ar' => 'appearance',
            'logo_image_en' => 'appearance',
            'logo_text' => 'appearance',
            'logo_text_ar' => 'appearance',
            'logo_text_en' => 'appearance',
            'logo_alt' => 'appearance',
            'logo_alt_ar' => 'appearance',
            'logo_alt_en' => 'appearance',
        ];

        $booleanKeys = ['logo_use_per_language', 'logo_text_use_per_language'];
        $fileUploadKeys = ['logo_image', 'logo_image_ar', 'logo_image_en'];
        $skipKeys = ['font_source', 'font_google_url', 'font_uploaded_path', 'font_upload_family_name'];

        $service = app(SettingsPersistService::class);
        $service->persist(
            $data,
            $groupMap,
            $booleanKeys,
            [],
            [],
            [],
            $skipKeys,
            $fileUploadKeys
        );

        $this->data = array_merge($this->data, $data);

        Notification::make()
            ->title(__('Settings saved'))
            ->success()
            ->send();
    }

    /** Persist font settings (Google / upload source). Returns false if validation failed. */
    protected function saveFontSettings(array $data): bool
    {
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

                return false;
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

                return false;
            }
        } else {
            Setting::set('font_source', '', 'string', 'appearance');
            Setting::set('font_google_url', '', 'string', 'appearance');
            Setting::set('font_uploaded_path', '', 'string', 'appearance');
            Setting::set('font_upload_family_name', '', 'string', 'appearance');
        }

        Cache::flush();

        return true;
    }
}
