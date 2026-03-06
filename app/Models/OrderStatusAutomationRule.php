<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatusAutomationRule extends Model
{
    public const TRIGGER_STATUS = 'status';

    public const TRIGGER_COMMENT = 'comment';

    public const LAST_COMMENT_CUSTOMER = 'customer';

    public const LAST_COMMENT_STAFF = 'staff';

    public const LAST_COMMENT_ANY = 'any';

    public const ACTION_COMMENT = 'comment';

    public const ACTION_CHANGE_STATUS = 'change_status';

    public const ACTION_BOTH = 'both';

    protected $fillable = [
        'trigger_type',
        'status',
        'last_comment_from',
        'days',
        'hours',
        'pause_if_no_reply_days',
        'pause_if_no_reply_hours',
        'comment_template',
        'comment_is_internal',
        'action_type',
        'action_status',
        'is_active',
        'notify_customer_email',
    ];

    protected $casts = [
        'days' => 'integer',
        'hours' => 'integer',
        'pause_if_no_reply_days' => 'integer',
        'pause_if_no_reply_hours' => 'integer',
        'comment_is_internal' => 'boolean',
        'is_active' => 'boolean',
        'notify_customer_email' => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(OrderStatusAutomationLog::class, 'order_status_automation_rule_id');
    }

    /**
     * Total threshold in hours (days * 24 + hours).
     */
    public function getThresholdHours(): int
    {
        return ($this->days * 24) + $this->hours;
    }

    /**
     * Total pause threshold in hours when latest comment has no reply (days * 24 + hours).
     * Returns 0 if pause is disabled (both days and hours are 0).
     */
    public function getPauseIfNoReplyThresholdHours(): int
    {
        return ($this->pause_if_no_reply_days * 24) + $this->pause_if_no_reply_hours;
    }

    public function hasPauseIfNoReply(): bool
    {
        return $this->getPauseIfNoReplyThresholdHours() > 0;
    }

    public function isStatusTrigger(): bool
    {
        return $this->trigger_type === self::TRIGGER_STATUS;
    }

    public function isCommentTrigger(): bool
    {
        return $this->trigger_type === self::TRIGGER_COMMENT;
    }

    public function shouldPostComment(): bool
    {
        return in_array($this->action_type, [self::ACTION_COMMENT, self::ACTION_BOTH], true);
    }

    public function shouldChangeStatus(): bool
    {
        return in_array($this->action_type, [self::ACTION_CHANGE_STATUS, self::ACTION_BOTH], true);
    }

    /**
     * @param  array{days: int, hours: int}  $duration
     * @param  string|null  $statusOverride  For comment rules, pass order status or null for "Comment"
     */
    public function renderComment(array $duration, ?string $statusOverride = null): string
    {
        $statusLabel = $statusOverride !== null
            ? __(ucfirst(str_replace('_', ' ', $statusOverride)))
            : __(ucfirst(str_replace('_', ' ', $this->status ?? 'comment')));

        $replacements = [
            '{status}' => $statusLabel,
            '{days}' => (string) $duration['days'],
            '{hours}' => (string) $duration['hours'],
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $this->comment_template);
    }
}
