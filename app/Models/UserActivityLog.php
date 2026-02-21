<?php

namespace App\Models;

use App\Jobs\FetchActivityGeoLocation;
use App\Services\UserAgentParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class UserActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'user_activity_logs';

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'event',
        'properties',
        'ip_address',
        'user_agent',
        'browser',
        'browser_version',
        'device',
        'device_model',
        'os',
        'os_version',
        'country',
        'city',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create an activity log entry enriched with device metadata from the request.
     * Dispatches a background job to fetch geo data (country/city) asynchronously.
     *
     * @param  array<string,mixed>  $data  Base fields (user_id, event, etc.)
     */
    public static function fromRequest(Request $request, array $data): static
    {
        $ua     = $request->userAgent() ?? '';
        $parser = new UserAgentParser($ua);
        $parsed = $parser->parse();

        $log = static::create(array_merge($data, [
            'ip_address'      => $request->ip(),
            'user_agent'      => $ua,
            'browser'         => $parsed['browser'],
            'browser_version' => $parsed['browser_version'],
            'device'          => $parsed['device'],
            'device_model'    => $parsed['device_model'],
            'os'              => $parsed['os'],
            'os_version'      => $parsed['os_version'],
        ]));

        // Fetch geo data in the background to avoid blocking the request
        FetchActivityGeoLocation::dispatch($log->id, $request->ip());

        return $log;
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'login'            => __('account.event_login'),
            'logout'           => __('account.event_logout'),
            'profile_updated'  => __('account.event_profile_updated'),
            'password_changed' => __('account.event_password_changed'),
            'order_created'    => __('account.event_order_created'),
            'address_added'    => __('account.event_address_added'),
            'address_removed'  => __('account.event_address_removed'),
            default            => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }

    public function eventIcon(): string
    {
        return match ($this->event) {
            'login'            => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
            'logout'           => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
            'profile_updated'  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'password_changed' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
            'order_created'    => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'address_added',
            'address_removed'  => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
            default            => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    public function eventColor(): string
    {
        return match ($this->event) {
            'login'            => 'green',
            'logout'           => 'gray',
            'profile_updated'  => 'blue',
            'password_changed' => 'orange',
            'order_created'    => 'indigo',
            'address_added'    => 'teal',
            'address_removed'  => 'red',
            default            => 'gray',
        };
    }
}
