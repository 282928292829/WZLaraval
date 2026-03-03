<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate user addresses from legacy WordPress (wp_usermeta saved_addresses).
 *
 * Source:  wp_usermeta  (legacy connection)
 * Target:  user_addresses  (default connection)
 *
 * Must run AFTER migrate:users (maps wp user_id to Laravel user_id by email).
 */
class MigrateAddresses extends Command
{
    protected $signature = 'migrate:addresses
                            {--fresh : Truncate user_addresses before migrating}';

    protected $description = 'Migrate user addresses from legacy WordPress into user_addresses';

    public function handle(): int
    {
        $this->info('=== MigrateAddresses ===');

        $legacy = DB::connection('legacy');
        if (! $legacy->getSchemaBuilder()->hasTable('wp_usermeta')) {
            $this->error('Legacy wp_usermeta table not found.');

            return self::FAILURE;
        }

        $meta = $legacy->table('wp_usermeta')
            ->where('meta_key', 'saved_addresses')
            ->get();

        if ($meta->isEmpty()) {
            $this->warn('No saved_addresses in legacy DB.');

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->warn('Truncating user_addresses …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('user_addresses')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Build wp_user_id → Laravel user_id map (by email).
        $wpUserEmails = $legacy->table('wp_users')
            ->whereIn('ID', $meta->pluck('user_id')->unique())
            ->pluck('user_email', 'ID')
            ->toArray();

        $userMap = DB::table('users')
            ->whereIn('email', array_values($wpUserEmails))
            ->pluck('id', 'email')
            ->toArray();

        $rows = [];
        $now = now();

        foreach ($meta as $m) {
            $wpEmail = $wpUserEmails[$m->user_id] ?? null;
            if (! $wpEmail) {
                continue;
            }
            $laravelUserId = $userMap[$wpEmail] ?? null;
            if (! $laravelUserId) {
                continue;
            }

            $addrs = @unserialize($m->meta_value);
            if (! is_array($addrs)) {
                $addrs = json_decode($m->meta_value, true) ?: [];
            }

            foreach ($addrs as $addr) {
                if (! is_array($addr)) {
                    continue;
                }
                $rows[] = [
                    'user_id' => $laravelUserId,
                    'label' => $addr['label'] ?? null,
                    'recipient_name' => $addr['recipient_name'] ?? $addr['name'] ?? null,
                    'phone' => $addr['phone'] ?? null,
                    'country' => $addr['country'] ?? 'SA',
                    'city' => $addr['city'] ?? '',
                    'address' => $addr['address'] ?? $addr['street'] ?? '',
                    'is_default' => ! empty($addr['is_default']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('user_addresses')->insert($chunk);
            }
        }

        $this->info('User addresses: '.count($rows));

        return self::SUCCESS;
    }
}
