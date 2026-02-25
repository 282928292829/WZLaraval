<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetTestUserPassword extends Command
{
    protected $signature = 'user:reset-password {email=customer@wasetzon.test} {password=password}';

    protected $description = 'Reset password for a test user (e.g. customer@wasetzon.test)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("User {$email} not found.");

            return 1;
        }

        $user->update(['password' => Hash::make($password)]);
        $this->info("Password reset for {$email}.");

        return 0;
    }
}
