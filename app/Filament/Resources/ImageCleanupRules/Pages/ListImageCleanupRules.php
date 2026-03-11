<?php

namespace App\Filament\Resources\ImageCleanupRules\Pages;

use App\Filament\Resources\ImageCleanupRules\ImageCleanupRuleResource;
use App\Jobs\CleanupOrderFilesJob;
use App\Models\ImageCleanupRule;
use App\Services\ImageCleanupService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListImageCleanupRules extends ListRecords
{
    protected static string $resource = ImageCleanupRuleResource::class;

    public function getTabs(): array
    {
        return [
            ImageCleanupRule::TYPE_DELETE => Tab::make(__('image_cleanup.tab_delete'))
                ->icon(Heroicon::OutlinedTrash)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('rule_type', ImageCleanupRule::TYPE_DELETE)),

            ImageCleanupRule::TYPE_COMPRESS => Tab::make(__('image_cleanup.tab_compress'))
                ->icon(Heroicon::OutlinedArchiveBox)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('rule_type', ImageCleanupRule::TYPE_COMPRESS)),
        ];
    }

    public function getActiveTab(): string
    {
        return $this->activeTab ?? ImageCleanupRule::TYPE_DELETE;
    }

    protected function getHeaderActions(): array
    {
        $ruleType = $this->getActiveTab();

        return [
            CreateAction::make()
                ->url(fn (): string => ImageCleanupRuleResource::getUrl('create').'?rule_type='.$ruleType),

            Action::make('runPreview')
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

    public function runCleanup(bool $dryRun): void
    {
        $ruleType = $this->getActiveTab();
        $service = app(ImageCleanupService::class);

        if ($service->isLocked()) {
            Notification::make()
                ->title(__('image_cleanup.already_running'))
                ->danger()
                ->send();

            return;
        }

        if ($dryRun) {
            $result = $service->run($ruleType, true, 'manual');
            $msg = isset($result['details'][0]['error'])
                ? $result['details'][0]['error']
                : (isset($result['details'][0]['info'])
                    ? $result['details'][0]['info']
                    : __('image_cleanup.result_summary', [
                        'orders' => $result['orders_processed'],
                        'deleted' => $result['files_deleted'],
                        'compressed' => $result['files_compressed'],
                        'bytes' => $this->formatBytes($result['bytes_freed']),
                    ]));
            Notification::make()
                ->title(__('image_cleanup.dry_run_complete'))
                ->body($msg)
                ->success()
                ->send();
        } else {
            CleanupOrderFilesJob::dispatch($ruleType, false, 'manual');
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
}
