<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'user_id',
        'body',
        'is_internal',
        'is_system',
        'is_edited',
        'edited_at',
        'deleted_by',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_system' => 'boolean',
        'is_edited' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function edits(): HasMany
    {
        return $this->hasMany(OrderCommentEdit::class, 'comment_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(OrderCommentRead::class, 'comment_id');
    }

    public function isVisibleTo(\App\Models\User $user): bool
    {
        if ($this->is_internal && ! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            return false;
        }

        return true;
    }

    public function canBeDeletedBy(\App\Models\User $user): bool
    {
        if ($this->is_system) {
            return false;
        }

        if ($user->can('delete-any-comment')) {
            return true;
        }

        return $this->user_id === $user->id;
    }

    public function canBeEditedBy(\App\Models\User $user): bool
    {
        if ($this->is_system) {
            return false;
        }

        if ($user->hasAnyRole(['editor', 'admin', 'superadmin']) && $user->can('reply-to-comments')) {
            return true;
        }

        return $this->user_id === $user->id;
    }
}
