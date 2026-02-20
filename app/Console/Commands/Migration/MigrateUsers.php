<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Migrate users from the legacy WordPress database into the Laravel users table.
 *
 * Source:  wp_users + wp_usermeta (legacy connection)
 * Target:  users + model_has_roles (default connection)
 *
 * Role mapping (wp_capabilities → Spatie role):
 *   administrator → admin
 *   editor        → editor
 *   subscriber    → customer   (default for all registered users)
 *   (empty)       → customer
 *
 * Password strategy:
 *   WordPress phpass hashes ($P$…) are imported as-is.
 *   The WpCompatUserProvider will verify + upgrade them to bcrypt on first login.
 */
class MigrateUsers extends Command
{
    protected $signature = 'migrate:users
                            {--fresh : Truncate the users table before migrating}
                            {--chunk=500 : Number of records to process per batch}
                            {--skip-staff : Skip administrator/editor accounts (migrate customers only)}';

    protected $description = 'Migrate users from legacy WordPress database into Laravel users table';

    private int $inserted = 0;
    private int $skipped  = 0;
    private int $errors   = 0;

    public function handle(): int
    {
        $this->info('=== MigrateUsers ===');

        if ($this->option('fresh')) {
            $this->warn('Truncating users, sessions, model_has_roles, model_has_permissions …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('order_comment_reads')->truncate();
            DB::table('order_comment_edits')->truncate();
            DB::table('order_comments')->truncate();
            DB::table('order_files')->truncate();
            DB::table('order_items')->truncate();
            DB::table('order_timeline')->truncate();
            DB::table('orders')->truncate();
            DB::table('model_has_permissions')->truncate();
            DB::table('model_has_roles')->truncate();
            DB::table('sessions')->truncate();
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $chunkSize = (int) $this->option('chunk');
        $skipStaff = (bool) $this->option('skip-staff');

        // Pre-load Spatie roles indexed by name for fast lookup.
        $roles = Role::all()->keyBy('name');

        $this->ensureRolesExist($roles);
        $roles = Role::all()->keyBy('name');

        $total = DB::connection('legacy')->table('wp_users')->count();
        $this->line("Source: {$total} wp_users records");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('legacy')
            ->table('wp_users')
            ->orderBy('ID')
            ->chunk($chunkSize, function ($wpUsers) use ($roles, $skipStaff, $bar) {
                $userIds = $wpUsers->pluck('ID')->toArray();

                // Fetch all meta for this chunk in one query.
                $metaRows = DB::connection('legacy')
                    ->table('wp_usermeta')
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

                // Index meta by user_id → [key => value].
                $meta = [];
                foreach ($metaRows as $row) {
                    $meta[$row->user_id][$row->meta_key] = $row->meta_value;
                }

                foreach ($wpUsers as $wpUser) {
                    $bar->advance();

                    $userMeta  = $meta[$wpUser->ID] ?? [];
                    $wpRole    = $this->extractWpRole($userMeta['wp_capabilities'] ?? '');

                    if ($skipStaff && in_array($wpRole, ['administrator', 'editor'], true)) {
                        $this->skipped++;
                        continue;
                    }

                    $laravelRole = $this->mapRole($wpRole);

                    // Skip if email already exists.
                    if (DB::table('users')->where('email', $wpUser->user_email)->exists()) {
                        $this->skipped++;
                        continue;
                    }

                    $name = $this->buildName($wpUser, $userMeta);

                    try {
                        $userId = DB::table('users')->insertGetId([
                            'name'              => $name,
                            'email'             => $wpUser->user_email,
                            'password'          => $wpUser->user_pass,
                            'email_verified_at' => $wpUser->user_registered,
                            'locale'            => 'ar',
                            'created_at'        => $wpUser->user_registered,
                            'updated_at'        => $wpUser->user_registered,
                        ]);

                        // Assign role via Spatie pivot table directly (faster than model).
                        if (isset($roles[$laravelRole])) {
                            DB::table('model_has_roles')->insert([
                                'role_id'    => $roles[$laravelRole]->id,
                                'model_type' => 'App\\Models\\User',
                                'model_id'   => $userId,
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

    /**
     * Parse WordPress serialized capabilities string and return the primary role name.
     *
     * Example: 'a:1:{s:13:"administrator";b:1;}' → 'administrator'
     */
    private function extractWpRole(string $raw): string
    {
        if (empty($raw)) {
            return 'subscriber';
        }

        // Unserialize safely.
        try {
            $caps = @unserialize($raw);
        } catch (\Exception) {
            $caps = false;
        }

        if (! is_array($caps)) {
            return 'subscriber';
        }

        // Return the first role that is set to true.
        foreach ($caps as $role => $active) {
            if ($active) {
                return $role;
            }
        }

        return 'subscriber';
    }

    /**
     * Map a WordPress role to a Laravel/Spatie role name.
     */
    private function mapRole(string $wpRole): string
    {
        return match ($wpRole) {
            'administrator' => 'admin',
            'editor'        => 'editor',
            default         => 'customer',
        };
    }

    /**
     * Build a display name from WP user data and meta.
     */
    private function buildName(object $wpUser, array $meta): string
    {
        $first = trim($meta['first_name'] ?? '');
        $last  = trim($meta['last_name'] ?? '');

        if ($first || $last) {
            return trim("{$first} {$last}");
        }

        if (! empty($wpUser->display_name)) {
            return $wpUser->display_name;
        }

        return $wpUser->user_login;
    }

    /**
     * Create any Spatie roles that don't exist yet.
     */
    private function ensureRolesExist(\Illuminate\Database\Eloquent\Collection $roles): void
    {
        $required = ['customer', 'editor', 'admin', 'superadmin', 'guest'];

        foreach ($required as $name) {
            if (! $roles->has($name)) {
                Role::create(['name' => $name, 'guard_name' => 'web']);
                $this->line("Created missing role: {$name}");
            }
        }
    }
}
