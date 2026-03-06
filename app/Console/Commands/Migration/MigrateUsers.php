<?php

namespace App\Console\Commands\Migration;

use Database\Seeders\DeletedUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Migrate users from the legacy WordPress database into the Laravel users table.
 *
 * Source:  wp_users + wp_usermeta  (legacy connection)
 * Target:  users + model_has_roles (default connection)
 *
 * Role mapping (wp_capabilities → Spatie role):
 *   administrator → superadmin
 *   editor        → staff
 *   subscriber    → customer
 *   (empty)       → customer
 *
 * Password strategy:
 *   WordPress phpass hashes ($P$…) are imported as-is.
 *   WpCompatUserProvider verifies + upgrades to bcrypt on first login.
 *
 * Deleted User (deleted-user@deleted-user.local) must exist before this runs;
 * it is seeded by MigrateAll. This command skips migrating that email.
 */
class MigrateUsers extends Command
{
    protected $signature = 'migrate:users
                            {--chunk=500 : Number of records to process per batch}';

    protected $description = 'Migrate users from legacy WordPress database into Laravel users table';

    private int $inserted = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $this->info('=== MigrateUsers ===');

        $chunkSize = (int) $this->option('chunk');

        $this->ensureRolesExist();
        $roles = Role::all()->keyBy('name');

        $total = DB::connection('legacy')->table('users')->count();
        $this->line("Source: {$total} wp_users records");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('legacy')
            ->table('users')
            ->orderBy('ID')
            ->chunk($chunkSize, function ($wpUsers) use ($roles, $bar) {
                $userIds = $wpUsers->pluck('ID')->toArray();

                $metaRows = DB::connection('legacy')
                    ->table('usermeta')
                    ->whereIn('user_id', $userIds)
                    ->whereIn('meta_key', [
                        'wp_capabilities',
                        'first_name',
                        'last_name',
                        'billing_phone',
                        'phone',
                        'last_login_time',
                    ])
                    ->get();

                $meta = [];
                foreach ($metaRows as $row) {
                    $meta[$row->user_id][$row->meta_key] = $row->meta_value;
                }

                foreach ($wpUsers as $wpUser) {
                    $bar->advance();

                    if ($wpUser->user_email === DeletedUserSeeder::EMAIL) {
                        $this->skipped++;

                        continue;
                    }

                    $userMeta = $meta[$wpUser->ID] ?? [];
                    $wpRole = $this->extractWpRole($userMeta['wp_capabilities'] ?? '');

                    $laravelRole = $this->mapRole($wpRole);

                    if (DB::table('users')->where('email', $wpUser->user_email)->exists()) {
                        $this->skipped++;

                        continue;
                    }

                    $name = $this->buildName($wpUser, $userMeta);

                    try {
                        $userId = DB::table('users')->insertGetId([
                            'name' => $name,
                            'email' => $wpUser->user_email,
                            'password' => $wpUser->user_pass,
                            'email_verified_at' => $wpUser->user_registered,
                            'locale' => 'ar',
                            'created_at' => $wpUser->user_registered,
                            'updated_at' => $wpUser->user_registered,
                        ]);

                        if (isset($roles[$laravelRole])) {
                            DB::table('model_has_roles')->insert([
                                'role_id' => $roles[$laravelRole]->id,
                                'model_type' => 'App\\Models\\User',
                                'model_id' => $userId,
                            ]);
                        }

                        $this->inserted++;
                    } catch (\Exception $e) {
                        $this->errors++;
                        $this->newLine();
                        $this->error("User ID {$wpUser->ID} ({$wpUser->user_email}): {$e->getMessage()}");
                    }
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Inserted : {$this->inserted}");
        $this->line("Skipped  : {$this->skipped}");

        if ($this->errors > 0) {
            $this->error("Errors   : {$this->errors}");
        }

        return self::SUCCESS;
    }

    private function extractWpRole(string $raw): string
    {
        if (empty($raw)) {
            return 'subscriber';
        }

        try {
            $caps = @unserialize($raw);
        } catch (\Exception) {
            $caps = false;
        }

        if (! is_array($caps)) {
            return 'subscriber';
        }

        foreach ($caps as $role => $active) {
            if ($active) {
                return $role;
            }
        }

        return 'subscriber';
    }

    private function mapRole(string $wpRole): string
    {
        return match ($wpRole) {
            'administrator' => 'superadmin',
            'editor' => 'staff',
            default => 'customer',
        };
    }

    private function buildName(object $wpUser, array $meta): string
    {
        $first = trim($meta['first_name'] ?? '');
        $last = trim($meta['last_name'] ?? '');

        if ($first || $last) {
            return trim("{$first} {$last}");
        }

        if (! empty($wpUser->display_name)) {
            return $wpUser->display_name;
        }

        return $wpUser->user_login;
    }

    private function ensureRolesExist(): void
    {
        $required = ['customer', 'staff', 'superadmin', 'guest'];

        foreach ($required as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
