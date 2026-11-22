<?php

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use Illuminate\Console\Command;

class PlatformAdminCommand extends Command
{
    protected $signature = 'platform:admin {email} {--name=Platform owner}';

    protected $description = 'Create a separate platform administrator interactively. Does not upgrade a nursery login.';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }
        if (PlatformAdmin::where('email', $email)->exists()) {
            $this->error('Administrator already exists; no changes made.');

            return self::FAILURE;
        }
        $password = $this->secret('Choose a password (minimum 12 characters)');
        if (strlen((string) $password) < 12 || $password !== $this->secret('Confirm password')) {
            $this->error('Passwords must match and contain at least 12 characters.');

            return self::FAILURE;
        }
        PlatformAdmin::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
        $this->info('Separate platform administrator created.');

        return self::SUCCESS;
    }
}
