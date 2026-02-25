<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    /** @var array<string,string> */
    protected $casts = [
        'value' => 'string',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string', ?string $group = null): void
    {
        $stored = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type, 'group' => $group ?? 'general'],
        );

        Cache::forget("setting:{$key}");
    }

    public static function grouped(): \Illuminate\Support\Collection
    {
        return static::orderBy('group')->orderBy('key')->get()->groupBy('group');
    }

    /**
     * Get the public URL for a favicon.
     * Site: custom or default icon. Admin: custom, else site favicon, else solid orange circle.
     */
    public static function faviconUrl(string $type = 'site'): string
    {
        $path = $type === 'admin'
            ? (static::get('favicon_admin') ?: static::get('favicon_site'))
            : static::get('favicon_site');

        if (filled($path) && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return $type === 'admin'
            ? asset('images/admin-favicon.png')
            : asset('icons/icon-96x96.png');
    }
}
