<?php

namespace App\Auth;

use App\Services\Migration\WpPhpassHasher;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * Extends the default Eloquent provider to support WordPress phpass hashes.
 *
 * When a stored password starts with `$P$` (phpass), it is verified using the
 * phpass algorithm. On success the password is immediately re-hashed with
 * bcrypt and persisted so that subsequent logins use native Laravel hashing.
 */
class WpCompatUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'];
        $hashed = $user->getAuthPassword();

        if (WpPhpassHasher::isWpHash($hashed)) {
            if (! WpPhpassHasher::check($plain, $hashed)) {
                return false;
            }

            // Re-hash with bcrypt and save — one-time upgrade per user.
            $user->forceFill(['password' => Hash::make($plain)])->save();

            return true;
        }

        return $this->hasher->check($plain, $hashed);
    }
}
