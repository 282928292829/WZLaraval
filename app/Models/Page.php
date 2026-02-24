<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function getTitle(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' ? ($this->title_ar ?: $this->title_en) : ($this->title_en ?: $this->title_ar);
    }
}
