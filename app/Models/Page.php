<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    protected $fillable = [
        'title_ar',
        'title_en',
        'slug',
        'body_ar',
        'body_en',
        'seo_title_ar',
        'seo_title_en',
        'seo_description_ar',
        'seo_description_en',
        'og_image',
        'canonical_url',
        'robots',
        'is_published',
        'show_in_header',
        'show_in_footer',
        'menu_order',
        'allow_comments',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_header' => 'boolean',
        'show_in_footer' => 'boolean',
        'menu_order' => 'integer',
        'allow_comments' => 'boolean',
    ];

    protected $attributes = [
        'menu_order' => 0,
    ];

    /** Header menu order matching new WordPress (wasetzon-modern): how-to-order, calculator, shipping-calculator, payment-methods, membership, faq, testimonials */
    public function scopeHeaderMenuOrder($query)
    {
        return $query->orderByRaw("CASE
            WHEN slug = 'how-to-order' THEN 1
            WHEN slug = 'calculator' THEN 2
            WHEN slug = 'shipping-calculator' THEN 3
            WHEN slug = 'payment-methods' THEN 4
            WHEN slug = 'membership' THEN 5
            WHEN slug = 'faq' THEN 6
            WHEN slug = 'testimonials' THEN 7
            ELSE 99
        END ASC");
    }

    protected function setMenuOrderAttribute(mixed $value): void
    {
        $this->attributes['menu_order'] = (is_numeric($value) && $value !== '') ? (int) $value : 0;
    }

    public function getTitle(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' ? ($this->title_ar ?: $this->title_en) : ($this->title_en ?: $this->title_ar);
    }

    public function getOgImageUrl(): ?string
    {
        $path = $this->og_image ?: Setting::get('seo_default_og_image', '');

        if (! $path) {
            return null;
        }

        return url(Storage::disk('public')->url($path));
    }

    public function getSeoTitle(): string
    {
        $locale = app()->getLocale();
        $seo = $locale === 'ar' ? ($this->seo_title_ar ?: $this->seo_title_en) : ($this->seo_title_en ?: $this->seo_title_ar);

        return $seo ?: $this->getTitle();
    }

    public function getSeoDescription(): string
    {
        $locale = app()->getLocale();
        $seo = $locale === 'ar' ? ($this->seo_description_ar ?? $this->seo_description_en ?? '') : ($this->seo_description_en ?? $this->seo_description_ar ?? '');

        if ($seo !== '') {
            return $seo;
        }

        $body = $locale === 'ar' ? ($this->body_ar ?: $this->body_en) : ($this->body_en ?: $this->body_ar);
        if ($body) {
            $plain = trim(strip_tags($body));

            return \Illuminate\Support\Str::limit($plain, 160);
        }

        $siteDefault = \App\Models\Setting::get('seo_default_meta_description', '');

        return $siteDefault !== '' ? $siteDefault : (string) __('app.description');
    }
}
