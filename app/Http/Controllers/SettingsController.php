<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Services\FeedbackNotifications;
use App\Services\AdminSystemNotifications;
use App\Services\NotificationEmailVerification;
use App\Services\MailFailureMessage;
use App\Notifications\AccountEmailChangeCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('settings.index', ['business' => $request->user()->business]);
    }

    public function update(Request $request, NotificationEmailVerification $verification)
    {
        $business = $request->user()->business;
        abort_unless($business, 404);
        $section = $request->input('section', 'legacy');
        $request->validate(['section' => ['nullable', Rule::in(['notifications', 'access', 'legacy'])]]);
        if ($section === 'access') {
            $data = $request->validate(['survey_enabled' => ['required', 'boolean'], 'closed_survey_message' => ['nullable', 'string', 'max:500']]);
            $before = $business->only(['survey_enabled', 'closed_survey_message']);
            $business->update($data);
            $this->notifyIfChanged($request->user(), $before, $business->fresh()->only(array_keys($before)), 'Survey settings updated', 'Survey access settings were updated.');
            return back()->with('status', 'Survey access settings saved.');
        } elseif ($section === 'notifications') {
            $data = $request->validate([
                'notification_email' => ['nullable', 'email', 'max:255'],
                'use_account_email_for_feedback' => ['required', 'boolean'],
                'dashboard_notifications' => ['required', 'boolean'],
                'feedback_email_alerts' => ['required', 'boolean'],
                'contact_message_alerts' => ['required', 'boolean'],
            ]);
            $before = $business->only(['dashboard_notifications', 'feedback_email_alerts', 'contact_message_alerts', 'notification_email', 'pending_notification_email', 'use_account_email_for_feedback']);
            $email = Str::lower(trim((string) ($data['notification_email'] ?? '')));
            $accountEmail = Str::lower((string) $request->user()->email);
            // A distinct address means the admin intends to use it, even if an old
            // browser state still submits the account-email toggle as checked.
            $useAccountEmail = $request->boolean('use_account_email_for_feedback') && ($email === '' || $email === $accountEmail);
            $business->update(collect($data)->only(['dashboard_notifications', 'feedback_email_alerts', 'contact_message_alerts'])->all());

            if ($useAccountEmail || $email === '' || $email === $accountEmail) {
                $business->update(['use_account_email_for_feedback' => true, 'pending_notification_email' => null]);
                DB::table('notification_email_verification_codes')->where('user_id', $request->user()->id)->delete();
                $this->notifyIfChanged($request->user(), $before, $business->fresh()->only(array_keys($before)), 'Notification settings updated', 'Dashboard, email, or contact alert preferences were updated.');
                return back()->with('status', 'Notification settings saved. Feedback alerts will use the account email.');
            }

            if ($business->notification_email_verified_at && Str::lower((string) $business->notification_email) === $email) {
                $business->update(['use_account_email_for_feedback' => false, 'pending_notification_email' => null]);
                $this->notifyIfChanged($request->user(), $before, $business->fresh()->only(array_keys($before)), 'Notification settings updated', 'Dashboard, email, or contact alert preferences were updated.');
                return back()->with('status', 'Notification settings saved. The verified notification email is active.');
            }

            try {
                $sent = $verification->issue($request->user(), $email);
            } catch (Throwable $error) {
                report($error);
                $this->notifyIfChanged($request->user(), $before, $business->fresh()->only(array_keys($before)), 'Notification settings updated', 'Alert preferences changed; notification email verification still needs attention.');
                return back()->withErrors(['notification_email' => app(MailFailureMessage::class)->forUser($error)])->withInput();
            }
            // Keep the proposed address pending only when its verification message
            // was sent already or successfully sent during this request. A failed
            // SMTP attempt must not leave an unverified address looking saved.
            $business->update(['pending_notification_email' => $email]);
            $this->notifyIfChanged($request->user(), $before, $business->fresh()->only(array_keys($before)), 'Notification email verification requested', 'A new notification email address is waiting for verification.');
            return back()->with('status', $sent
                ? 'A verification code was sent to the new notification email. Enter it below to activate that address.'
                : 'A verification code was sent recently. Wait for the cooldown before requesting another code.');
        } else {
            $rules = ['notification_email' => ['required', 'email', 'max:255']];
            $data = $request->validate($rules);
            $data['email'] = $data['notification_email'];
            $data['survey_enabled'] = $request->boolean('survey_enabled');
        }
        $business->update($data);
        return back()->with('status', 'Notification settings saved.');
    }

    public function verifyNotificationEmail(Request $request, NotificationEmailVerification $verification)
    {
        $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/']]);
        if (! $verification->verify($request->user(), $request->input('code'))) {
            return back()->withErrors(['notification_code' => 'The verification code is invalid or expired.'])->withInput();
        }

        $this->systemNotification($request->user(), 'Notification email verified', 'The feedback notification email address was verified and activated.');
        return back()->with('status', 'Notification email verified and activated.');
    }

    public function account(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'current_password' => ['nullable', 'string'], 'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);
        if (! empty($data['password']) && (empty($data['current_password']) || ! Hash::check($data['current_password'], $request->user()->password))) {
            return back()->withErrors(['current_password' => 'Enter your current password to change your password.'])->withInput($request->only('name', 'email'));
        }
        $user = $request->user();
        $before = $user->only(['name', 'email', 'password', 'pending_email']);
        $changes = ['name' => $data['name']];
        if (! empty($data['password'])) $changes['password'] = Hash::make($data['password']);
        $emailChanged = Str::lower($data['email']) !== Str::lower($user->email);
        if (! $emailChanged) {
            $user->update($changes);
            $request->session()->regenerate();
            $this->notifyIfChanged($user, $before, $user->fresh()->only(array_keys($before)), 'Account settings updated', 'Admin account or security settings were updated.');
            return back()->with('status', 'Account details updated.');
        }

        $email = Str::lower($data['email']);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        try {
            DB::transaction(function () use ($user, $email, $code, $changes) {
                $user->update($changes);
                $user->forceFill([
                    'pending_email' => $email,
                    'email_change_code_hash' => Hash::make($code),
                    'email_change_code_expires_at' => now()->addMinutes(5),
                    'email_change_code_sent_at' => now(),
                    'email_change_code_attempts' => 0,
                ])->save();
                Notification::route('mail', $email)->notify(new AccountEmailChangeCode($code));
            });
        } catch (Throwable $error) {
            report($error);
            return back()->withErrors(['email' => app(MailFailureMessage::class)->forUser($error) . ' Your account email was not changed.'])->withInput($request->only('name', 'email'));
        }
        $request->session()->regenerate();
        $this->notifyIfChanged($user, $before, $user->fresh()->only(array_keys($before)), 'Account email change requested', 'A new account email is waiting for verification.');
        return back()->with('status', 'A verification code was sent to the new account email. The current email remains active until verification.');
    }

    public function verifyAccountEmail(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/']]);
        $user = $request->user();
        if (! $user->pending_email || ! $user->email_change_code_hash || ! $user->email_change_code_expires_at
            || now()->greaterThan($user->email_change_code_expires_at) || $user->email_change_code_attempts >= 5) {
            return back()->withErrors(['account_email_code' => 'The verification code is invalid or expired.']);
        }
        if (! Hash::check($request->input('code'), $user->email_change_code_hash)) {
            $user->increment('email_change_code_attempts');
            return back()->withErrors(['account_email_code' => 'The verification code is incorrect.']);
        }

        $user->forceFill([
            'email' => $user->pending_email,
            'email_verified_at' => now(),
            'pending_email' => null,
            'email_change_code_hash' => null,
            'email_change_code_expires_at' => null,
            'email_change_code_sent_at' => null,
            'email_change_code_attempts' => 0,
        ])->save();
        $request->session()->regenerate();
        $this->systemNotification($user, 'Account email updated', 'The admin account email was verified and updated.');
        return back()->with('status', 'Account email verified and updated.');
    }

    public function testEmail(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business, 404);
        $recipient = $business->notificationRecipient();
        if (! $recipient || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['email_test' => 'No valid active notification email is configured. Save and verify a notification email, or use the account email.']);
        }
        try {
            Mail::raw('This is a CFS notification test for ' . $business->business_name . '.', function ($message) use ($recipient) {
                $message->to($recipient)->subject('CFS Test Notification');
            });
            $transport = config('mail.mailers.' . config('mail.default') . '.transport');
            return back()->with('status', in_array($transport, ['log', 'array'], true)
                ? "Test email recorded in development mail output for {$recipient}. Inbox delivery requires SMTP configuration."
                : "Test email accepted by the mail provider for {$recipient}. Check that inbox and its spam folder.");
        } catch (Throwable $error) {
            report($error);
            return back()->withErrors(['email_test' => app(MailFailureMessage::class)->forUser($error) . " Recipient: {$recipient}."]);
        }
    }

    public function retryEmail(Request $request, Feedback $feedback)
    {
        $feedback->load('business.user', 'answers.question');
        abort_unless($feedback->business->user_id === $request->user()->id, 403);
        if (! in_array($feedback->email_status, ['sent', 'logged', 'disabled', 'queued'], true)) {
            try {
                $feedback->update(['email_status' => 'queued', 'email_error' => null]);
                \App\Jobs\SendFeedbackNotification::dispatch($feedback->id)->afterCommit();
            } catch (Throwable $error) {
                report($error);
                $feedback->update(['email_status' => 'failed', 'email_error' => 'Email delivery failed. Check the active notification address and SMTP mail settings.']);
            }
        }
        return back()->with('status', 'Email notification status: ' . $feedback->fresh()->email_status . '.');
    }

    private function notifyIfChanged($user, array $before, array $after, string $title, string $message): void
    {
        foreach ($before as $key => $value) {
            if ((string) ($value ?? '') !== (string) ($after[$key] ?? '')) {
                $this->systemNotification($user, $title, $message);
                return;
            }
        }
    }

    private function systemNotification($user, string $title, string $message): void
    {
        app(AdminSystemNotifications::class)->record($user, $title, $message);
    }
}
