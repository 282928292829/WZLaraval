<?php

namespace App\Console\Commands\Migration;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Overwrite roles and assign superadmin to all emails in SUPERADMIN_EMAILS.
 *
 * Set SUPERADMIN_EMAILS in .env (comma-separated).
 */
class AssignSuperadmins extends Command
{
    protected $signature = 'migrate:assign-superadmins';

    protected $description = 'Assign superadmin role to users by email (SUPERADMIN_EMAILS env), overwriting existing roles';

    public function handle(): int
    {
        $this->info('=== AssignSuperadmins ===');

        $emails = config('migration.superadmin_emails', []);

        if (empty($emails)) {
            $this->warn('No SUPERADMIN_EMAILS configured. Use comma-separated emails in .env');

            return self::SUCCESS;
        }

        $superadminRole = Role::where('name', 'superadmin')->first();

        if (! $superadminRole) {
            $this->error('Superadmin role not found.');

            return self::FAILURE;
        }

        $assigned = 0;

        foreach ($emails as $email) {
            $email = trim($email);
            if (empty($email)) {
                continue;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->line("  <comment>Skip:</comment> {$email} (user not found)");

                continue;
            }

            $user->syncRoles(['superadmin']);
            $this->line("  <info>Assigned superadmin:</info> {$email}");
            $assigned++;
        }

        $this->info("Assigned superadmin to {$assigned} user(s).");

        return self::SUCCESS;
    }
}
