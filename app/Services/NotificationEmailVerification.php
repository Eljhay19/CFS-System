<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NotificationEmailVerificationCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NotificationEmailVerification
{
    public function issue(User $user, string $email): bool
    {
        $email = Str::lower(trim($email));

        return DB::transaction(function () use ($user, $email) {
            $row = DB::table('notification_email_verification_codes')->where('user_id', $user->id)->lockForUpdate()->first();
            if ($row && $row->email === $email && Carbon::parse($row->sent_at)->addSeconds(60)->isFuture()) {
                return false;
            }

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('notification_email_verification_codes')->updateOrInsert(['user_id' => $user->id], [
                'email' => $email,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(5),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Notification::route('mail', $email)->notify(new NotificationEmailVerificationCode($code));
            return true;
        });
    }

    public function verify(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            $row = DB::table('notification_email_verification_codes')->where('user_id', $user->id)->lockForUpdate()->first();
            $business = $user->business;
            if (! $row || ! $business || $row->email !== $business->pending_notification_email
                || Carbon::parse($row->expires_at)->isPast() || $row->attempts >= 5) {
                return false;
            }
            if (! Hash::check($code, $row->code_hash)) {
                DB::table('notification_email_verification_codes')->where('user_id', $user->id)->increment('attempts');
                return false;
            }

            $business->update([
                'notification_email' => $row->email,
                'notification_email_verified_at' => now(),
                'pending_notification_email' => null,
                'use_account_email_for_feedback' => false,
            ]);
            DB::table('notification_email_verification_codes')->where('user_id', $user->id)->delete();
            return true;
        });
    }
}
