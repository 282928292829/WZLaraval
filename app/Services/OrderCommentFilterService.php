<?php

namespace App\Services;

use App\Models\OrderComment;
use Illuminate\Support\Collection;

class OrderCommentFilterService
{
    public const PRESETS = [
        '4h' => ['value' => 4, 'unit' => 'hours'],
        '8h' => ['value' => 8, 'unit' => 'hours'],
        '24h' => ['value' => 24, 'unit' => 'hours'],
        '1d' => ['value' => 1, 'unit' => 'days'],
        '2d' => ['value' => 2, 'unit' => 'days'],
        '7d' => ['value' => 7, 'unit' => 'days'],
    ];

    /** Valid values for last-reply filter. */
    public const LAST_REPLY_VALUES = ['customer', 'staff', 'staff_public', 'staff_internal'];

    /**
     * Subquery of order IDs where the last comment (by created_at) matches criteria.
     * Use with whereIn() to avoid MySQL placeholder limit with large result sets.
     *
     * @param  string  $lastReply  customer=from customer, staff=from team any, staff_public=from team public only, staff_internal=from team internal only
     * @param  string|null  $preset  Preset key: 4h, 8h, 24h, 1d, 2d, 7d
     * @param  int|null  $customValue  Custom number (1-99) when preset is "custom"
     * @param  'hours'|'days'|null  $customUnit  Unit when preset is "custom"
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\OrderComment>
     */
    public static function orderIdsAwaitingResponseSubquery(
        string $lastReply,
        ?string $preset = null,
        ?int $customValue = null,
        ?string $customUnit = null
    ): \Illuminate\Database\Eloquent\Builder {
        $threshold = static::parseThreshold($preset, $customValue, $customUnit);

        $sub = OrderComment::query()
            ->selectRaw('order_id, MAX(created_at) as max_created')
            ->whereNull('deleted_at')
            ->groupBy('order_id');

        $query = OrderComment::query()
            ->select('order_comments.order_id')
            ->joinSub($sub, 'last', function ($join) {
                $join->on('order_comments.order_id', '=', 'last.order_id')
                    ->on('order_comments.created_at', '=', 'last.max_created');
            })
            ->whereNull('order_comments.deleted_at');

        if ($lastReply === 'customer') {
            $query->whereHas('user', fn ($q) => $q->nonStaff());
        } else {
            $query->whereHas('user', fn ($q) => $q->staff());
            if ($lastReply === 'staff_public') {
                $query->where('order_comments.is_internal', false);
            } elseif ($lastReply === 'staff_internal') {
                $query->where('order_comments.is_internal', true);
            }
        }

        if ($threshold !== null) {
            $query->where('order_comments.created_at', '<=', $threshold);
        }

        return $query;
    }

    /**
     * Get order IDs where the last comment (by created_at) matches criteria.
     *
     * @param  string  $lastReply  customer|staff|staff_public|staff_internal
     * @param  string|null  $preset  Preset key: 4h, 8h, 24h, 1d, 2d, 7d
     * @param  int|null  $customValue  Custom number (1-99) when preset is "custom"
     * @param  'hours'|'days'|null  $customUnit  Unit when preset is "custom"
     */
    public static function orderIdsAwaitingResponse(
        string $awaiting,
        ?string $preset = null,
        ?int $customValue = null,
        ?string $customUnit = null
    ): Collection {
        return static::orderIdsAwaitingResponseSubquery($awaiting, $preset, $customValue, $customUnit)
            ->pluck('order_id')
            ->unique()
            ->values();
    }

    private static function parseThreshold(?string $preset, ?int $customValue, ?string $customUnit): ?\Carbon\Carbon
    {
        if ($preset === 'custom' && $customValue !== null && $customValue >= 1 && in_array($customUnit, ['hours', 'days'], true)) {
            return match ($customUnit) {
                'hours' => now()->subHours(min(8760, $customValue)), // cap 1 year
                'days' => now()->subDays(min(365, $customValue)),
                default => null,
            };
        }

        if ($preset !== null && isset(static::PRESETS[$preset])) {
            $cfg = static::PRESETS[$preset];

            return match ($cfg['unit']) {
                'hours' => now()->subHours($cfg['value']),
                'days' => now()->subDays($cfg['value']),
                default => null,
            };
        }

        return null;
    }
}
