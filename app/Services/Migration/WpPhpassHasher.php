<?php

namespace App\Services\Migration;

/**
 * WordPress phpass hasher compatibility layer.
 *
 * Verifies passwords hashed by WordPress (phpass algorithm, `$P$` prefix).
 * After successful verification, the caller should re-hash with bcrypt and
 * save the new hash so subsequent logins use native Laravel hashing.
 *
 * Ported from the original phpass library by Solar Designer.
 */
class WpPhpassHasher
{
    private const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Returns true when $hash was created by WordPress (phpass).
     */
    public static function isWpHash(string $hash): bool
    {
        return str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$');
    }

    /**
     * Verify a plain-text password against a WordPress phpass hash.
     */
    public static function check(string $password, string $hash): bool
    {
        if (! self::isWpHash($hash)) {
            return false;
        }

        return self::cryptPrivate($password, $hash) === $hash;
    }

    private static function cryptPrivate(string $password, string $setting): string
    {
        $itoa64 = self::ITOA64;
        $output = '*0';

        if (substr($setting, 0, 2) === $output) {
            $output = '*1';
        }

        $id = substr($setting, 0, 3);

        if ($id !== '$P$' && $id !== '$H$') {
            return $output;
        }

        $countLog2 = strpos($itoa64, $setting[3]);

        if ($countLog2 < 7 || $countLog2 > 30) {
            return $output;
        }

        $count = 1 << $countLog2;
        $salt = substr($setting, 4, 8);

        if (strlen($salt) !== 8) {
            return $output;
        }

        $hash = md5($salt.$password, true);

        do {
            $hash = md5($hash.$password, true);
        } while (--$count);

        $output = substr($setting, 0, 12);
        $output .= self::encode64($hash, 16);

        return $output;
    }

    private static function encode64(string $input, int $count): string
    {
        $itoa64 = self::ITOA64;
        $output = '';
        $i = 0;

        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];

            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }

            $output .= $itoa64[($value >> 6) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }

            $output .= $itoa64[($value >> 12) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
}
