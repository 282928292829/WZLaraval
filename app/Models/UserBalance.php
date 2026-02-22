<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBalance extends Model
{
    protected $fillable = [
        'user_id',
        'created_by',
        'type',
        'amount',
        'currency',
        'note',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Returns grouped balance totals keyed by currency code.
     * Each entry: ['credit' => x, 'debit' => y, 'net' => z]
     *
     * @return array<string, array{credit: float, debit: float, net: float}>
     */
    public static function totalsForUser(int $userId): array
    {
        $rows = static::query()
            ->where('user_id', $userId)
            ->selectRaw('currency, type, SUM(amount) as total')
            ->groupBy('currency', 'type')
            ->get();

        $totals = [];
        foreach ($rows as $row) {
            $totals[$row->currency] ??= ['credit' => 0.0, 'debit' => 0.0, 'net' => 0.0];
            $totals[$row->currency][$row->type] += (float) $row->total;
        }

        foreach ($totals as $currency => &$t) {
            $t['net'] = $t['credit'] - $t['debit'];
        }

        return $totals;
    }
}
