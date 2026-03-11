<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageCleanupRule extends Model
{
    public const TYPE_DELETE = 'delete';

    public const TYPE_COMPRESS = 'compress';

    protected $fillable = [
        'rule_type',
        'statuses',
        'retention_days_customer_product',
        'retention_days_staff_product',
        'retention_days_customer_comment',
        'retention_days_staff_comment',
        'customer_product',
        'staff_product',
        'customer_comment',
        'staff_comment',
        'receipt',
        'invoice',
        'other',
        'compression_quality',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'statuses' => 'array',
            'retention_days_customer_product' => 'integer',
            'retention_days_staff_product' => 'integer',
            'retention_days_customer_comment' => 'integer',
            'retention_days_staff_comment' => 'integer',
            'customer_product' => 'boolean',
            'staff_product' => 'boolean',
            'customer_comment' => 'boolean',
            'staff_comment' => 'boolean',
            'receipt' => 'boolean',
            'invoice' => 'boolean',
            'other' => 'boolean',
            'compression_quality' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ImageCleanupRun::class);
    }

    public function isDelete(): bool
    {
        return $this->rule_type === self::TYPE_DELETE;
    }

    public function isCompress(): bool
    {
        return $this->rule_type === self::TYPE_COMPRESS;
    }

    /**
     * Check if this rule has any file type toggles enabled.
     */
    public function hasAnyFileTypeEnabled(): bool
    {
        return $this->customer_product
            || $this->staff_product
            || $this->customer_comment
            || $this->staff_comment
            || $this->receipt
            || $this->invoice
            || $this->other;
    }

    /**
     * Get retention days for a file type based on uploader (staff vs customer).
     */
    public function getRetentionDaysForFile(OrderFile $file): ?int
    {
        $user = $file->user;
        if (! $user) {
            return null;
        }
        $isStaff = $user->isStaffOrAbove();

        if ($file->type === 'product_image') {
            if ($isStaff && $this->staff_product) {
                return $this->retention_days_staff_product;
            }
            if (! $isStaff && $this->customer_product) {
                return $this->retention_days_customer_product;
            }
        }

        if ($file->type === 'comment') {
            if ($isStaff && $this->staff_comment) {
                return $this->retention_days_staff_comment;
            }
            if (! $isStaff && $this->customer_comment) {
                return $this->retention_days_customer_comment;
            }
        }

        if ($file->type === 'receipt' && $this->receipt) {
            return $isStaff ? $this->retention_days_staff_product : $this->retention_days_customer_product;
        }

        if ($file->type === 'invoice' && $this->invoice) {
            return $isStaff ? $this->retention_days_staff_product : $this->retention_days_customer_product;
        }

        if ($file->type === 'other' && $this->other) {
            return $isStaff ? $this->retention_days_staff_product : $this->retention_days_customer_product;
        }

        return null;
    }

    /**
     * Check if a file should be processed by this rule.
     */
    public function shouldProcessFile(Order $order, OrderFile $file): bool
    {
        $retentionDays = $this->getRetentionDaysForFile($file);
        if ($retentionDays === null) {
            return false;
        }

        $statusChangedAt = $order->status_changed_at;
        if (! $statusChangedAt) {
            return false;
        }

        return $statusChangedAt->lte(now()->subDays($retentionDays));
    }
}
