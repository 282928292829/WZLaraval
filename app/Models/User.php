<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasPermissionTo('access-filament');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'phone',
        'phone_secondary',
        'google_id',
        'twitter_id',
        'apple_id',
        'avatar',
        'is_banned',
        'banned_at',
        'banned_reason',
        'notify_orders',
        'notify_promotions',
        'notify_whatsapp',
        'unsubscribed_all',
        'deletion_requested',
        'ad_campaign_id',
        'google_click_id',
        'email_change_pending',
        'email_change_code',
        'email_change_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
            'last_login_at' => 'datetime',
            'notify_orders' => 'boolean',
            'notify_promotions' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'unsubscribed_all' => 'boolean',
            'deletion_requested' => 'boolean',
            'email_change_expires_at' => 'datetime',
        ];
    }

    public function adCampaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(UserBalance::class);
    }

    public function canImpersonate(): bool
    {
        return $this->hasPermissionTo('manage-users');
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->is(auth()->user());
    }

    public function initials(): string
    {
        $parts = explode(' ', trim($this->name));
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($this->name, 0, 2));
    }
}
