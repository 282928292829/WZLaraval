<?php

namespace App\Filament\Resources\ImageCleanupRules\Pages;

use App\Filament\Resources\ImageCleanupRules\ImageCleanupRuleResource;
use App\Models\ImageCleanupRule;
use Filament\Resources\Pages\CreateRecord;

class CreateImageCleanupRule extends CreateRecord
{
    protected static string $resource = ImageCleanupRuleResource::class;

    public function mount(): void
    {
        parent::mount();

        $ruleType = request()->query('rule_type', ImageCleanupRule::TYPE_DELETE);
        if (in_array($ruleType, [ImageCleanupRule::TYPE_DELETE, ImageCleanupRule::TYPE_COMPRESS], true)) {
            $this->form->fill(['rule_type' => $ruleType]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $ruleType = request()->query('rule_type', $data['rule_type'] ?? ImageCleanupRule::TYPE_DELETE);
        $data['rule_type'] = in_array($ruleType, [ImageCleanupRule::TYPE_DELETE, ImageCleanupRule::TYPE_COMPRESS], true)
            ? $ruleType
            : ImageCleanupRule::TYPE_DELETE;

        return $data;
    }
}
