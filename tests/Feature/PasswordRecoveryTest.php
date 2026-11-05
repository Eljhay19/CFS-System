<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use Illuminate\Auth\Events\PasswordReset;
use App\Notifications\PasswordResetCode as ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['mail.default' => 'smtp']);
        $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => Hash::make('old-password')]);
    }

    private function credentials(string $token): array
    {
        return ['email' => $this->owner->email, 'token' => $token, 'password' => 'new-password-2026', 'password_confirmation' => 'new-password-2026'];
    }

    public function test_request_is_generic_and_only_sends_to_the_matching_login_account(): void
    {
        $this->from(route('password.request'))->post(route('password.email'), ['email' => $this->owner->email])->assertRedirect(route('password.sent'));
        $message = session('status');
        Notification::assertSentTo($this->owner, ResetPassword::class);
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])->assertSessionHas('status', $message);
        $this->assertCount(1, Notification::sent($this->owner, ResetPassword::class));
        $this->assertSame('unknown@example.test', session('_old_input.email'));
    }

    public function test_recovery_uses_only_the_admin_account_email_not_the_business_profile_email(): void
    {
        $business = Business::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Owner Cafe',
            'owner_name' => 'Owner',
            'business_type' => 'Cafe',
            'address' => 'Quezon City',
            'contact_number' => '09123456789',
            'email' => 'profile@example.test',
            'notification_email' => 'alerts@example.test',
        ]);

        $this->post(route('password.email'), ['email' => $business->email])->assertRedirect(route('password.sent'));
        Notification::assertNothingSent();
        $this->assertSame('owner@example.test', $this->owner->fresh()->routeNotificationForMail());

        $this->post(route('password.email'), ['email' => $this->owner->email])->assertRedirect(route('password.sent'));
        Notification::assertSentTo($this->owner, ResetPassword::class);
        $this->get(route('password.sent'))->assertOk()->assertSee('o***@example.test');
        $this->assertSame($this->owner->email, session('password_recovery.email'));
    }

    public function test_password_reset_email_is_english_and_matches_the_five_minute_code_expiry(): void
    {
        $this->post(route('password.email'), ['email' => $this->owner->email])->assertRedirect(route('password.sent'));

        $row = DB::table('password_reset_codes')->sole();
        $this->assertTrue(now()->addMinutes(4)->lessThanOrEqualTo(\Illuminate\Support\Carbon::parse($row->expires_at)));
        $this->assertTrue(now()->addMinutes(6)->greaterThan(\Illuminate\Support\Carbon::parse($row->expires_at)));

        $mail = (new ResetPassword('123456'))->toMail($this->owner);
        $this->assertSame('CFS Admin Password Reset Code', $mail->subject);
        $this->assertContains('This code expires in 5 minutes and can only be used once.', $mail->introLines);
        $this->assertContains('If you did not request this password reset, you can safely ignore this email.', $mail->introLines);
    }

    public function test_resend_throttle_does_not_replace_the_existing_token(): void
    {
        $this->post(route('password.email'), ['email' => $this->owner->email]);
        $hash = DB::table('password_reset_codes')->value('code_hash');
        $this->post(route('password.email'), ['email' => $this->owner->email])->assertSessionHasNoErrors();
        $this->assertSame($hash, DB::table('password_reset_codes')->value('code_hash'));
        $this->assertCount(1, Notification::sent($this->owner, ResetPassword::class));
    }

    public function test_confirmation_requires_a_request_and_refresh_does_not_send_again(): void
    {
        $this->get(route('password.sent'))->assertRedirect(route('password.request'));
        $this->post(route('password.resend'))->assertRedirect(route('password.request'));
        $this->post(route('password.email'), ['email' => $this->owner->email])->assertRedirect(route('password.sent'));
        foreach (range(1, 2) as $refresh) {
            $this->get(route('password.sent'))->assertOk()->assertSee('Verify Your Email')->assertSee('verification-code')->assertSee('data-otp-digit', false)->assertDontSee('<dialog', false);
        }
        $this->assertCount(1, Notification::sent($this->owner, ResetPassword::class));
        $this->assertSame($this->owner->email, session('password_recovery.email'));
    }

    public function test_resend_uses_session_account_and_enforces_cooldown_for_every_account(): void
    {
        $this->post(route('password.email'), ['email' => $this->owner->email]);
        $hash = DB::table('password_reset_codes')->value('code_hash');
        $this->post(route('password.resend'), ['email' => 'tampered@example.test'])->assertRedirect(route('password.sent'));
        $this->assertSame($hash, DB::table('password_reset_codes')->value('code_hash'));
        $this->assertCount(1, Notification::sent($this->owner, ResetPassword::class));
        $this->travel(61)->seconds();
        $this->post(route('password.resend'), ['email' => 'tampered@example.test'])->assertRedirect(route('password.sent'));
        $this->assertCount(2, Notification::sent($this->owner, ResetPassword::class));
        $this->assertNotSame($hash, DB::table('password_reset_codes')->value('code_hash'));
        $this->post(route('password.email'), ['email' => 'unknown@example.test']);
        $this->assertSame(now()->addSeconds(60)->timestamp, session('password_recovery.resend_at'));
        $this->post(route('password.resend'))->assertRedirect(route('password.sent'));
        $this->assertSame('unknown@example.test', session('password_recovery.email'));
    }

    public function test_failed_resend_keeps_session_and_can_be_retried_without_waiting(): void
    {
        $this->post(route('password.email'), ['email' => $this->owner->email]);
        $hash = DB::table('password_reset_codes')->value('code_hash');
        $this->travel(61)->seconds();
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Private transport details'));
        $this->from(route('password.sent'))->post(route('password.resend'))->assertRedirect(route('password.sent'))->assertSessionHasErrors('email');
        $this->assertSame($hash, DB::table('password_reset_codes')->value('code_hash'));
        $this->assertSame($this->owner->email, session('password_recovery.email'));
        Notification::fake();
        $this->post(route('password.resend'))->assertRedirect(route('password.sent'))->assertSessionHasNoErrors();
        Notification::assertSentTo($this->owner, ResetPassword::class);
    }

    public function test_recovery_routes_share_scene_and_handle_malformed_reset_email(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Forgot Password?')->assertSee('login-showcase')->assertSee('data-recovery-panel')
            ->assertDontSee('<dialog', false)->assertDontSee('data-admin-login-panel')->assertDontSee('background-login');
        $this->get(route('password.reset', ['token' => 'example', 'email' => ['invalid']]))
            ->assertOk()->assertSee('This reset link is incomplete.');
        $this->get(route('password.reset', ['token' => 'example', 'email' => $this->owner->email]))
            ->assertOk()->assertSee('Set New Password')->assertSee('data-password-toggle');
    }

    public function test_malformed_request_email_returns_validation_in_the_popup_without_render_errors(): void
    {
        $this->from(route('password.request'))->post(route('password.email'), ['email' => ['invalid']])
            ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');
        $this->get(route('password.request'))->assertOk()->assertSee('role="alert"', false)->assertSee('Forgot Password?');
        Notification::assertNothingSent();
    }

    public function test_local_mail_mode_does_not_claim_inbox_delivery(): void
    {
        config(['mail.default' => 'log']);
        $this->post(route('password.email'), ['email' => $this->owner->email])->assertSessionHas('status', function ($message) {
            return str_contains($message, 'no inbox email is sent');
        });
    }

    public function test_delivery_failure_keeps_email_and_allows_immediate_retry(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Private transport details'));
        $this->from(route('password.request'))->post(route('password.email'), ['email' => $this->owner->email])
            ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('password_resets', 0);
        $this->assertSame($this->owner->email, session('_old_input.email'));
        $this->assertStringNotContainsString('Private transport details', session('errors')->first('email'));
        Notification::fake();
        $this->post(route('password.email'), ['email' => $this->owner->email])->assertSessionHasNoErrors();
        Notification::assertSentTo($this->owner, ResetPassword::class);
    }

    public function test_invalid_expired_and_wrong_account_tokens_cannot_change_the_password(): void
    {
        $token = Password::createToken($this->owner);
        $this->post(route('password.update'), $this->credentials('invalid-token'))->assertSessionHasErrors('email');
        $data = $this->credentials($token);
        $data['email'] = 'unknown@example.test';
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        DB::table('password_resets')->update(['created_at' => now()->subMinutes(61)]);
        $this->post(route('password.update'), $this->credentials($token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $this->owner->fresh()->password));
    }

    public function test_validation_preserves_email_but_never_flashes_passwords_or_reset_tokens(): void
    {
        $token = Password::createToken($this->owner);
        $data = $this->credentials($token);
        $data['password_confirmation'] = 'different-password';
        $this->post(route('password.update'), $data)->assertSessionHasErrors('password');
        $this->assertSame($this->owner->email, session('_old_input.email'));
        foreach (['password', 'password_confirmation', 'token'] as $field) $this->assertArrayNotHasKey($field, session('_old_input', []));
        $this->assertTrue(Password::tokenExists($this->owner, $token));
        $data['password'] = $data['password_confirmation'] = 'short';
        $this->post(route('password.update'), $data)->assertSessionHasErrors('password');
    }

    public function test_success_consumes_the_token_and_only_the_new_password_can_sign_in(): void
    {
        Event::fake([PasswordReset::class]);
        $token = Password::createToken($this->owner);
        $stored = DB::table('password_resets')->value('token');
        $this->assertNotSame($token, $stored);
        $this->assertTrue(Hash::check($token, $stored));
        $this->post(route('password.update'), $this->credentials($token))->assertRedirect(route('login'));
        $this->assertDatabaseCount('password_resets', 0);
        Event::assertDispatched(PasswordReset::class);
        $this->post(route('password.update'), $this->credentials($token))->assertSessionHasErrors('email');
        $this->post(route('login.post'), ['login' => $this->owner->email, 'password' => 'old-password'])->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->post(route('login.post'), ['login' => $this->owner->email, 'password' => 'new-password-2026'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->owner);
    }

    public function test_failed_token_consumption_rolls_back_the_password_change(): void
    {
        $token = Password::createToken($this->owner);
        Password::shouldReceive('reset')->once()->andReturnUsing(function ($credentials, $callback) {
            $callback($this->owner, $credentials['password']);
            throw new RuntimeException('Simulated token deletion failure');
        });
        $this->post(route('password.update'), $this->credentials($token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $this->owner->fresh()->password));
        $this->assertDatabaseCount('password_resets', 1);
    }

    private function latestCode(): string
    {
        return Notification::sent($this->owner, ResetPassword::class)->last()->code;
    }

    public function test_six_digit_code_is_hashed_session_bound_expiring_and_single_use(): void
    {
        $this->post(route('password.email'), ['email'=>$this->owner->email])->assertRedirect(route('password.sent'));
        $challenge = session('password_recovery');
        $code = $this->latestCode();
        $row = DB::table('password_reset_codes')->sole();
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
        $this->assertNotSame($code, $row->code_hash);
        $this->assertTrue(Hash::check($code, $row->code_hash));
        $this->assertDatabaseCount('password_resets', 0);
        $this->withSession(['password_recovery'=>array_merge($challenge, ['challenge_id'=>'different'])])
            ->post(route('password.verify'), ['code'=>$code])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('password_resets', 0);
        $response = $this->withSession(['password_recovery'=>$challenge])->post(route('password.verify'), ['code'=>$code, 'email'=>'tampered@example.test'])->assertRedirect();
        $token = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));
        $this->assertTrue(Password::tokenExists($this->owner, $token));
        $this->assertDatabaseCount('password_reset_codes', 0);
        $this->post(route('password.verify'), ['code'=>$code])->assertRedirect(route('password.request'));
        $this->post(route('password.update'), $this->credentials($token))->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password-2026', $this->owner->fresh()->password));
    }

    public function test_expired_codes_and_five_failed_attempts_cannot_reset_a_password_but_resend_recovers(): void
    {
        $this->post(route('password.email'), ['email'=>$this->owner->email]);
        $first = $this->latestCode();
        DB::table('password_reset_codes')->update(['expires_at'=>now()->subSecond()]);
        $this->post(route('password.verify'), ['code'=>$first])->assertSessionHasErrors('code');
        $this->travel(61)->seconds();
        $this->post(route('password.resend'))->assertRedirect(route('password.sent'));
        $code = $this->latestCode();
        $wrong = $code === '000000' ? '111111' : '000000';
        foreach (range(1, 5) as $attempt) $this->post(route('password.verify'), ['code'=>$wrong])->assertSessionHasErrors('code');
        $this->assertSame(5, DB::table('password_reset_codes')->value('attempts'));
        $this->travel(61)->seconds();
        $this->post(route('password.verify'), ['code'=>$code])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('password_resets', 0);
        $this->post(route('password.resend'));
        $new = $this->latestCode();
        $this->assertNotSame($code, $new);
        $this->post(route('password.verify'), ['code'=>$code])->assertSessionHasErrors('code');
        $this->post(route('password.verify'), ['code'=>$new])->assertRedirect();
        $this->assertDatabaseCount('password_resets', 1);
    }

    public function test_code_is_never_flashed_and_unknown_accounts_cannot_verify(): void
    {
        $this->post(route('password.verify'), ['code'=>'123456'])->assertRedirect(route('password.request'));
        $this->post(route('password.email'), ['email'=>'unknown@example.test'])->assertRedirect(route('password.sent'));
        $this->assertDatabaseCount('password_reset_codes', 0);
        $this->post(route('password.verify'), ['code'=>'123456'])->assertSessionHasErrors('code');
        $this->post(route('password.verify'), ['code'=>['123456']])->assertSessionHasErrors('code');
        $this->assertArrayNotHasKey('code', session('_old_input', []));
        $this->assertDatabaseCount('password_resets', 0);
        Notification::assertNothingSent();
    }
}
