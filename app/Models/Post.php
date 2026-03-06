<?php

namespace App\Models;

use App\Support\ColorHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'post_category_id',
        'title_ar',
        'title_en',
        'slug',
        'excerpt_ar',
        'excerpt_en',
        'body_ar',
        'body_en',
        'featured_image',
        'seo_title_ar',
        'seo_title_en',
        'seo_description_ar',
        'seo_description_en',
        'status',
        'published_at',
        'allow_comments',
        'is_pinned',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'allow_comments' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function getTitle(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' ? ($this->title_ar ?: $this->title_en) : ($this->title_en ?: $this->title_ar);
    }

    public function getExcerpt(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' ? ($this->excerpt_ar ?: $this->excerpt_en ?? '') : ($this->excerpt_en ?: $this->excerpt_ar ?? '');
    }

    /** Deterministic placeholder color for posts without featured image. Uses primary color from settings. Same post = same color. */
    public function getPlaceholderColors(): array
    {
        $primary = trim((string) Setting::get('primary_color', '#f97316')) ?: '#f97316';
        $vary = $this->id % 6;
        $lightenPct = 78 + ($vary * 2);
        $darkenPct = 35 + ($vary * 3);

        return [
            'bg' => ColorHelper::lighten($primary, $lightenPct),
            'text' => ColorHelper::darken($primary, $darkenPct),
        ];
    }
}
