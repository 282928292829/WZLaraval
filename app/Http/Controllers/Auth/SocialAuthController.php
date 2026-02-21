<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // Shared helpers
    // ──────────────────────────────────────────────────────────

    /**
     * Abort with 404 if the requested provider's setting toggle is off.
     */
    private function requireEnabled(string $setting): void
    {
        if (! Setting::get($setting, false)) {
            abort(404);
        }
    }

    /**
     * Find or create a user from a social identity, then log them in.
     *
     * @param  string       $idField   Column name on users table (e.g. 'google_id')
     * @param  string       $socialId  The provider's unique user ID
     * @param  string|null  $email
     * @param  string|null  $name
     * @param  string|null  $avatar
     */
    private function loginOrCreate(
        string $idField,
        string $socialId,
        ?string $email,
        ?string $name,
        ?string $avatar
    ): RedirectResponse {
        $query = User::where($idField, $socialId);

        if ($email) {
            $query->orWhere('email', $email);
        }

        $user = $query->first();

        if ($user) {
            if (! $user->{$idField}) {
                $user->update(array_filter([
                    $idField => $socialId,
                    'avatar' => $avatar,
                ]));
            }
        } else {
            $derivedName = $name
                ?? ($email ? ucfirst(preg_replace('/[^a-zA-Z0-9]/', ' ', explode('@', $email)[0])) : 'User');

            $user = User::create(array_filter([
                'name'              => trim($derivedName) ?: ($email ?? 'User'),
                'email'             => $email,
                $idField            => $socialId,
                'avatar'            => $avatar,
                'email_verified_at' => $email ? now() : null,
                'password'          => null,
            ], fn ($v) => $v !== null));

            $user->assignRole('customer');
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    // ──────────────────────────────────────────────────────────
    // Google
    // ──────────────────────────────────────────────────────────

    public function redirectToGoogle(): RedirectResponse
    {
        $this->requireEnabled('google_login_enabled');

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $this->requireEnabled('google_login_enabled');

        try {
            $su = Socialite::driver('google')->user();
        } catch (\Exception) {
            return redirect()->route('login')->withErrors(['google' => __('auth.google_failed')]);
        }

        return $this->loginOrCreate('google_id', $su->getId(), $su->getEmail(), $su->getName(), $su->getAvatar());
    }

    // ──────────────────────────────────────────────────────────
    // X (Twitter OAuth 2.0)
    // ──────────────────────────────────────────────────────────

    public function redirectToTwitter(): RedirectResponse
    {
        $this->requireEnabled('twitter_login_enabled');

        return Socialite::driver('twitter')->redirect();
    }

    public function handleTwitterCallback(): RedirectResponse
    {
        $this->requireEnabled('twitter_login_enabled');

        try {
            $su = Socialite::driver('twitter')->user();
        } catch (\Exception) {
            return redirect()->route('login')->withErrors(['twitter' => __('auth.twitter_failed')]);
        }

        return $this->loginOrCreate('twitter_id', $su->getId(), $su->getEmail(), $su->getName(), $su->getAvatar());
    }

    // ──────────────────────────────────────────────────────────
    // Apple
    // ──────────────────────────────────────────────────────────

    public function redirectToApple(): RedirectResponse
    {
        $this->requireEnabled('apple_login_enabled');

        return Socialite::driver('apple')->redirect();
    }

    public function handleAppleCallback(): RedirectResponse
    {
        $this->requireEnabled('apple_login_enabled');

        try {
            $su = Socialite::driver('apple')->user();
        } catch (\Exception) {
            return redirect()->route('login')->withErrors(['apple' => __('auth.apple_failed')]);
        }

        // Apple only sends name on the very first login — use it if present
        $name = ($su->user['name']['firstName'] ?? '') . ' ' . ($su->user['name']['lastName'] ?? '');
        $name = trim($name) ?: null;

        return $this->loginOrCreate('apple_id', $su->getId(), $su->getEmail(), $name, null);
    }
}
