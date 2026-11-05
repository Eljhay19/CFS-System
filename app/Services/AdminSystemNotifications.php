<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

class AdminSystemNotifications
{
    public function record(User $user, string $title, string $message): void
    {
        if ($user->business && ! $user->business->dashboard_notifications) {
            return;
        }

        try {
            $user->notifications()->create([
                'title' => Str::limit($title, 255),
                'message' => Str::limit($message, 2000),
                'type' => 'system',
                'is_read' => false,
            ]);
        } catch (Throwable $error) {
            report($error);
        }
    }
}
