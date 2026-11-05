<?php

namespace App\Http\Controllers;

use App\Services\PasswordResetCodes;
use App\Services\MailFailureMessage;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        $email = old('email');
        return view('auth.password', ['state' => 'request', 'formEmail' => is_string($email) ? $email : '']);
    }

    public function sent(Request $request)
    {
        if (! $request->session()->has('password_recovery.email')) {
            return redirect()->route('password.request');
        }
        return view('auth.password', [
            'state' => 'sent',
            'resendAt' => $request->session()->get('password_recovery.resend_at', 0),
            'localMailMode' => in_array(config('mail.default'), ['log', 'array'], true),
            'maskedEmail' => $request->session()->get('password_recovery.masked_email', $this->maskedEmail($request->session()->get('password_recovery.email'))),
        ]);
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);
        return $this->deliver($request, $request->input('email'));
    }

    public function resend(Request $request)
    {
        $email = $request->session()->get('password_recovery.email');
        if (! $email) return redirect()->route('password.request');
        return $this->deliver($request, $email);
    }

    private function deliver(Request $request, string $email)
    {
        $email = Str::lower(trim($email));
        $session = $request->session();
        if ($session->get('password_recovery.email') === $email && $session->get('password_recovery.resend_at', 0) > now()->timestamp) {
            return redirect()->route('password.sent');
        }
        try {
            // Failed delivery must not leave a new token that blocks an immediate retry.
            $challenge = app(PasswordResetCodes::class)->issue($email);
        } catch (Throwable $error) {
            report($error);
            return back()->withErrors(['email' => app(MailFailureMessage::class)->forUser($error)])
                ->withInput(['email' => $email]);
        }
        // Keep the same cooldown for known and unknown accounts, including on refresh.
        $session->put('password_recovery', $challenge);
        $message = 'If this account exists, a verification code has been requested.';
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $message .= ' Email delivery is not configured; no inbox email is sent in local development mode.';
        }
        return redirect()->route('password.sent')->with('status', $message)->withInput(['email' => $email]);
    }

    public function verify(Request $request)
    {
        if (! $request->session()->has('password_recovery.email')) return redirect()->route('password.request');
        $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/']]);
        try {
            $credentials = app(PasswordResetCodes::class)->verify($request->session()->get('password_recovery'), $request->input('code'));
        } catch (Throwable $error) {
            report($error);
            return redirect()->route('password.sent')->withErrors(['code'=>'Verification is temporarily unavailable. Please try again.']);
        }
        if (! $credentials) return redirect()->route('password.sent')->withErrors(['code'=>'The code is invalid or expired. After 5 unsuccessful attempts, request a new code.']);
        $request->session()->forget('password_recovery');
        $request->session()->regenerate();
        return redirect()->route('password.reset', $credentials);
    }

    private function maskedEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) return '';
        [$name, $domain] = explode('@', $email, 2);
        return substr($name, 0, 1) . '***@' . $domain;
    }

    public function resetForm(Request $request, string $token)
    {
        $email = $request->query('email');
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $email = '';
        }
        return view('auth.password', ['state' => 'reset', 'token' => $token, 'email' => $email]);
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        $resetUser = null;
        try {
            $status = DB::transaction(function () use ($request, &$resetUser) {
                return Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) use (&$resetUser) {
                    $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                    DB::table('password_reset_codes')->where('email', Str::lower($user->email))->delete();
                    $resetUser = $user;
                });
            });
        } catch (Throwable $error) {
            report($error);
            return back()->withErrors(['email' => 'The password could not be updated. Please try again.'])
                ->withInput($request->only('email'));
        }
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
        }
        try {
            event(new PasswordReset($resetUser));
        } catch (Throwable $error) {
            report($error);
        }
        $request->session()->forget('password_recovery');
        return redirect()->route('login')->with('status', 'Password reset successfully. Please sign in.');
    }
}
