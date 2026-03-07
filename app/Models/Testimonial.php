<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Testimonial extends Model
{
    use HasFactory;
    protected $fillable = [
        'image_path',
        'name_ar',
        'name_en',
        'quote_ar',
        'quote_en',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected $attributes = [
        'sort_order' => 0,
        'is_published' => true,
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getName(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' ? ($this->name_ar ?: $this->name_en ?? '') : ($this->name_en ?: $this->name_ar ?? '');
    }

    public function getQuote(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' ? ($this->quote_ar ?: $this->quote_en ?? '') : ($this->quote_en ?: $this->quote_ar ?? '');
    }

    public function getImageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return url(Storage::disk('public')->url($this->image_path));
    }
}
