<?php

namespace App\Filament\Pages;

use App\Enums\AdminNavigationGroup;
use App\Jobs\CleanupOrderFilesJob;
use App\Models\ImageCleanupRun;
use App\Models\Order;
use App\Models\Setting;
use App\Services\ImageCleanupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ImageCleanupPage extends Page
{
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?AdminNavigationGroup
    {
        return AdminNavigationGroup::Settings;
    }

    public static function getNavigationLabel(): string
    {
        return __('image_cleanup.nav_label');
    }

    public function getTitle(): string
    {
        return __('image_cleanup.page_title');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-image-cleanup') ?? false;
    }

    /** @var list<string> */
    protected const IMAGE_CLEANUP_KEYS = [
        'image_cleanup_statuses',
        'image_cleanup_retention_days_customer_product',
        'image_cleanup_retention_days_staff_product',
        'image_cleanup_retention_days_customer_comment',
        'image_cleanup_retention_days_staff_comment',
        'image_cleanup_action',
        'image_cleanup_compression_quality',
        'image_cleanup_customer_product',
        'image_cleanup_staff_product',
        'image_cleanup_customer_comment',
        'image_cleanup_staff_comment',
        'image_cleanup_receipt',
        'image_cleanup_invoice',
        'image_cleanup_other',
        'image_cleanup_schedule_enabled',
        'image_cleanup_schedule_frequency',
        'image_cleanup_schedule_hour',
        'image_cleanup_schedule_day',
    ];

    public function mount(): void
    {
        $data = $this->defaults();
        $allSettings = Setting::all()->keyBy('key');

        foreach (static::IMAGE_CLEANUP_KEYS as $key) {
            $setting = $allSettings->get($key);
            if ($setting) {
                $value = $setting->type === 'json'
                    ? json_decode($setting->value, true)
                    : $setting->value;
                if ($setting->type === 'boolean') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if ($setting->type === 'integer') {
                    $value = (int) $value;
                }
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
            'image_cleanup_statuses' => ['cancelled'],
            'image_cleanup_retention_days_customer_product' => 14,
            'image_cleanup_retention_days_staff_product' => 90,
            'image_cleanup_retention_days_customer_comment' => 14,
            'image_cleanup_retention_days_staff_comment' => 90,
            'image_cleanup_action' => 'delete',
            'image_cleanup_compression_quality' => 55,
            'image_cleanup_customer_product' => true,
            'image_cleanup_staff_product' => false,
            'image_cleanup_customer_comment' => true,
            'image_cleanup_staff_comment' => false,
            'image_cleanup_receipt' => false,
            'image_cleanup_invoice' => false,
            'image_cleanup_other' => false,
            'image_cleanup_schedule_enabled' => false,
            'image_cleanup_schedule_frequency' => 'daily',
            'image_cleanup_schedule_hour' => 2,
            'image_cleanup_schedule_day' => 0,
        ];
    }

    public function form(Schema $schema): Schema
    {
        $statusOptions = collect(Order::getStatuses())->mapWithKeys(fn ($label, $key) => [$key => $label])->all();

        return $schema
            ->components([
                Section::make(__('image_cleanup.triggers_section'))
                    ->description(__('image_cleanup.triggers_section_help'))
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->schema([
                        CheckboxList::make('image_cleanup_statuses')
                            ->label(__('image_cleanup.statuses'))
                            ->options($statusOptions)
                            ->columns(3),

                        Select::make('image_cleanup_action')
                            ->label(__('image_cleanup.action'))
                            ->options([
                                'delete' => __('image_cleanup.action_delete'),
                                'compress' => __('image_cleanup.action_compress'),
                            ])
                            ->default('delete')
                            ->required(),

                        TextInput::make('image_cleanup_compression_quality')
                            ->label(__('image_cleanup.compression_quality'))
                            ->helperText(__('image_cleanup.compression_quality_help'))
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(95)
                            ->default(55)
                            ->visible(fn ($get) => $get('image_cleanup_action') === 'compress'),
                    ])
                    ->columns(2),

                Section::make(__('image_cleanup.file_types_section'))
                    ->description(__('image_cleanup.file_types_section_help'))
                    ->icon(Heroicon::OutlinedDocument)
                    ->schema([
                        TextInput::make('image_cleanup_retention_days_customer_product')
                            ->label(__('image_cleanup.retention_customer_product'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Toggle::make('image_cleanup_customer_product')
                            ->label(__('image_cleanup.toggle_customer_product'))
                            ->default(true),

                        TextInput::make('image_cleanup_retention_days_staff_product')
                            ->label(__('image_cleanup.retention_staff_product'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Toggle::make('image_cleanup_staff_product')
                            ->label(__('image_cleanup.toggle_staff_product'))
                            ->default(false),

                        TextInput::make('image_cleanup_retention_days_customer_comment')
                            ->label(__('image_cleanup.retention_customer_comment'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Toggle::make('image_cleanup_customer_comment')
                            ->label(__('image_cleanup.toggle_customer_comment'))
                            ->default(true),

                        TextInput::make('image_cleanup_retention_days_staff_comment')
                            ->label(__('image_cleanup.retention_staff_comment'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Toggle::make('image_cleanup_staff_comment')
                            ->label(__('image_cleanup.toggle_staff_comment'))
                            ->default(false),

                        Toggle::make('image_cleanup_receipt')
                            ->label(__('image_cleanup.toggle_receipt'))
                            ->default(false),
                        Toggle::make('image_cleanup_invoice')
                            ->label(__('image_cleanup.toggle_invoice'))
                            ->default(false),
                        Toggle::make('image_cleanup_other')
                            ->label(__('image_cleanup.toggle_other'))
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make(__('image_cleanup.schedule_section'))
                    ->description(__('image_cleanup.schedule_section_help'))
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Toggle::make('image_cleanup_schedule_enabled')
                            ->label(__('image_cleanup.schedule_enabled'))
                            ->default(false),

                        Select::make('image_cleanup_schedule_frequency')
                            ->label(__('image_cleanup.schedule_frequency'))
                            ->options([
                                'daily' => __('image_cleanup.frequency_daily'),
                                'weekly' => __('image_cleanup.frequency_weekly'),
                            ])
                            ->default('daily')
                            ->visible(fn ($get) => (bool) $get('image_cleanup_schedule_enabled')),

                        TextInput::make('image_cleanup_schedule_hour')
                            ->label(__('image_cleanup.schedule_hour'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(23)
                            ->default(2)
                            ->visible(fn ($get) => (bool) $get('image_cleanup_schedule_enabled')),

                        Select::make('image_cleanup_schedule_day')
                            ->label(__('image_cleanup.schedule_day'))
                            ->options([
                                0 => __('Sunday'),
                                1 => __('Monday'),
                                2 => __('Tuesday'),
                                3 => __('Wednesday'),
                                4 => __('Thursday'),
                                5 => __('Friday'),
                                6 => __('Saturday'),
                            ])
                            ->default(0)
                            ->visible(fn ($get) => (bool) $get('image_cleanup_schedule_enabled') && $get('image_cleanup_schedule_frequency') === 'weekly'),
                    ])
                    ->columns(2)
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
            ->id('image-cleanup-form')
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
        return [
            Action::make('runDryRun')
                ->label(__('image_cleanup.run_dry_run'))
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->action(fn () => $this->runCleanup(true)),
            Action::make('runNow')
                ->label(__('image_cleanup.run_now'))
                ->icon(Heroicon::OutlinedPlay)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('image_cleanup.run_confirm_heading'))
                ->modalDescription(__('image_cleanup.run_confirm_description'))
                ->action(fn () => $this->runCleanup(false)),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        if (empty($data) || ! is_array($data)) {
            $data = $this->data;
        }

        foreach ($data as $key => $value) {
            if (! in_array($key, static::IMAGE_CLEANUP_KEYS, true)) {
                continue;
            }
            $type = is_array($value) ? 'json' : (is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'));
            Setting::set($key, $value, $type, 'image_cleanup');
        }

        $this->data = array_merge($this->data, $data);

        Notification::make()
            ->title(__('Settings saved'))
            ->success()
            ->send();
    }

    public function runCleanup(bool $dryRun): void
    {
        $this->save();

        $service = app(ImageCleanupService::class);

        if ($service->isLocked()) {
            Notification::make()
                ->title(__('image_cleanup.already_running'))
                ->danger()
                ->send();

            return;
        }

        if ($dryRun) {
            $result = $service->run(true, 'manual');
            $msg = isset($result['details'][0]['error'])
                ? $result['details'][0]['error']
                : __('image_cleanup.result_summary', [
                    'orders' => $result['orders_processed'],
                    'deleted' => $result['files_deleted'],
                    'compressed' => $result['files_compressed'],
                    'bytes' => $this->formatBytes($result['bytes_freed']),
                ]);
            Notification::make()
                ->title(__('image_cleanup.dry_run_complete'))
                ->body($msg)
                ->success()
                ->send();
        } else {
            CleanupOrderFilesJob::dispatch(false, 'manual');
            Notification::make()
                ->title(__('image_cleanup.run_started'))
                ->success()
                ->send();
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function getLastRun(): ?ImageCleanupRun
    {
        return ImageCleanupRun::latest('started_at')->first();
    }
}
