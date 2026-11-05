<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateInitialAdmin extends Command
{
    protected $signature = 'cfs:admin:create';

    protected $description = 'Create the first CFS administrator without public registration';

    public function handle()
    {
        if (! Schema::hasTable('users')) {
            $this->error('The users table is missing. Run php artisan migrate --force first.');
            return 1;
        }

        if (User::query()->exists()) {
            $this->error('An administrator account already exists. Public or repeat account creation is disabled.');
            return 1;
        }

        $name = trim((string) $this->ask('Admin name'));
        if ($name === '' || mb_strlen($name) > 255) {
            $this->error('Enter an admin name between 1 and 255 characters.');
            return 1;
        }

        $email = Str::lower(trim((string) $this->ask('Admin email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $this->error('Enter a valid email address no longer than 190 characters.');
            return 1;
        }

        $password = (string) $this->secret('Password (minimum 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');
        if (strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
            $this->error('The password must be at least 12 characters and both entries must match. No account was created.');
            return 1;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('Initial administrator created. Sign in, then complete the Business Profile and verify the recovery email.');
        return 0;
    }
}
