<?php

namespace App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages;

use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource;
use App\Models\OrderStatusAutomationRule;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditOrderStatusAutomationRule extends EditRecord
{
    protected static string $resource = OrderStatusAutomationRuleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $triggerType = $data['trigger_type'] ?? 'status';
        $actionType = $data['action_type'] ?? 'comment';

        if ($triggerType === OrderStatusAutomationRule::TRIGGER_COMMENT) {
            $data['action_type'] = OrderStatusAutomationRule::ACTION_COMMENT;
            $data['action_status'] = null;
        }

        if ($triggerType === OrderStatusAutomationRule::TRIGGER_STATUS
            && in_array($actionType, [OrderStatusAutomationRule::ACTION_CHANGE_STATUS], true)
            && empty(trim((string) ($data['comment_template'] ?? '')))) {
            $data['comment_template'] = '-';
        }

        return $data;
    }

    protected function beforeValidate(): void
    {
        $data = $this->form->getRawState();
        $days = (int) ($data['days'] ?? 0);
        $hours = (int) ($data['hours'] ?? 0);
        if ($days <= 0 && $hours <= 0) {
            throw ValidationException::withMessages([
                'days' => [__('automation.days_or_hours_required')],
            ]);
        }
        $triggerType = $data['trigger_type'] ?? 'status';
        $actionType = $data['action_type'] ?? 'comment';
        if ($triggerType === OrderStatusAutomationRule::TRIGGER_STATUS
            && in_array($actionType, [OrderStatusAutomationRule::ACTION_CHANGE_STATUS, OrderStatusAutomationRule::ACTION_BOTH], true)
            && empty($data['action_status'] ?? null)) {
            throw ValidationException::withMessages([
                'action_status' => [__('automation.action_status_required')],
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
