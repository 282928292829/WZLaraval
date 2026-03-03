<?php

namespace App\Console\Commands\Migration;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Assign superadmin role to configured users by email.
 *
 * Set SUPERADMIN_EMAILS in .env (comma-separated):
 *   SUPERADMIN_EMAILS=abdulsgz@hotmail.com,ulgasan491@yahoo.com,aminoos@live.com
 */
class AssignSuperadmins extends Command
{
    protected $signature = 'migrate:assign-superadmins';

    protected $description = 'Assign superadmin role to users by email (SUPERADMIN_EMAILS env)';

    private const DEFAULT_EMAILS = [
        'abdulsgz@hotmail.com',
        'ulgasan491@yahoo.com',
        'aminoos@live.com',
    ];

    public function handle(): int
    {
        $this->info('=== AssignSuperadmins ===');

        $emails = $this->getEmails();

        if (empty($emails)) {
            $this->warn('No SUPERADMIN_EMAILS configured. Use comma-separated emails in .env');

            return self::SUCCESS;
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

            if ($user->hasRole('superadmin')) {
                $this->line("  <info>Already superadmin:</info> {$email}");

                continue;
            }

            $user->assignRole('superadmin');
            $this->line("  <info>Assigned superadmin:</info> {$email}");
            $assigned++;
        }

        $this->info("Assigned superadmin to {$assigned} user(s).");

        return self::SUCCESS;
    }

    private function getEmails(): array
    {
        $config = config('migration.superadmin_emails');

        if (is_array($config) && ! empty($config)) {
            return $config;
        }

        return self::DEFAULT_EMAILS;
    }
}
