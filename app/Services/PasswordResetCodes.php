<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetCodes
{
    public function issue(string $email): array
    {
        $email = Str::lower($email);
        $challenge = (string) Str::uuid();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = Hash::make($code);
        return DB::transaction(function () use ($email, $challenge, $code, $hash) {
            // Password recovery is always tied to the admin's account email.
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            $accountEmail = $user ? Str::lower($user->email) : $email;
            $deliveryEmail = $user ? Str::lower(trim((string) $user->email)) : $email;
            $existing = DB::table('password_reset_codes')->where('email', $accountEmail)->lockForUpdate()->first();
            $resendAt = now()->addSeconds(60)->timestamp;
            if ($user && $existing && Carbon::parse($existing->sent_at)->addSeconds(60)->isFuture()) {
                $resendAt = Carbon::parse($existing->sent_at)->addSeconds(60)->timestamp;
                return [
                    'email' => $accountEmail,
                    'masked_email' => $this->maskedEmail($deliveryEmail),
                    'challenge_id' => $existing->challenge_id,
                    'resend_at' => $resendAt,
                ];
            }
            if ($user) {
                while ($existing && Hash::check($code, $existing->code_hash)) {
                    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $hash = Hash::make($code);
                }
                DB::table('password_reset_codes')->updateOrInsert(['email'=>$accountEmail], [
                    'challenge_id'=>$challenge, 'code_hash'=>$hash, 'attempts'=>0,
                    'expires_at'=>now()->addMinutes(5), 'sent_at'=>now(),
                ]);
                // A transport failure rolls back the code, preserving a previous usable code.
                Notification::send($user, new PasswordResetCode($code));
            }
            return [
                'email' => $accountEmail,
                'masked_email' => $this->maskedEmail($deliveryEmail),
                'challenge_id' => $challenge,
                'resend_at' => $resendAt,
            ];
        });
    }

    private function maskedEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);
        return substr($name, 0, 1) . '***@' . $domain;
    }

    public function verify(array $challenge, string $code): ?array
    {
        return DB::transaction(function () use ($challenge, $code) {
            $email = $challenge['email'] ?? '';
            $row = DB::table('password_reset_codes')->where('email', $email)->lockForUpdate()->first();
            if (! $row || ! hash_equals($row->challenge_id, $challenge['challenge_id'] ?? '')
                || Carbon::parse($row->expires_at)->isPast() || $row->attempts >= 5) return null;
            if (! Hash::check($code, $row->code_hash)) {
                DB::table('password_reset_codes')->where('email', $email)->increment('attempts');
                return null;
            }
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if (! $user) return null;
            $token = Password::createToken($user);
            DB::table('password_reset_codes')->where('email', $email)->delete();
            return ['token'=>$token, 'email'=>$user->email];
        });
    }
}
