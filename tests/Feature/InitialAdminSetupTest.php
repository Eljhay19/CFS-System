<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialAdminSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_admin_can_only_be_provisioned_through_the_private_cli_setup(): void
    {
        $password = 'CFS-Secure-Admin-2026';

        $this->artisan('cfs:admin:create')
            ->expectsQuestion('Admin name', 'CFS Administrator')
            ->expectsQuestion('Admin email', 'ADMIN@EXAMPLE.COM')
            ->expectsQuestion('Password (minimum 12 characters)', $password)
            ->expectsQuestion('Confirm password', $password)
            ->assertExitCode(0);

        $admin = User::query()->firstOrFail();
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertNull($admin->email_verified_at);

        $this->artisan('cfs:admin:create')
            ->expectsOutput('An administrator account already exists. Public or repeat account creation is disabled.')
            ->assertExitCode(1);
        $this->assertSame(1, User::query()->count());
    }

    public function test_weak_initial_admin_password_does_not_create_an_account(): void
    {
        $this->artisan('cfs:admin:create')
            ->expectsQuestion('Admin name', 'CFS Administrator')
            ->expectsQuestion('Admin email', 'admin@example.com')
            ->expectsQuestion('Password (minimum 12 characters)', 'short-pass')
            ->expectsQuestion('Confirm password', 'short-pass')
            ->expectsOutput('The password must be at least 12 characters and both entries must match. No account was created.')
            ->assertExitCode(1);

        $this->assertSame(0, User::query()->count());
    }
}
