<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function business()
    {
        return $this->hasOne(Business::class);
    }

    public function routeNotificationForMail($notification = null): string
    {
        return $this->email;
    }

    public function notifications()
    {
        return $this->hasMany(AdminNotification::class);
    }

    public function notificationCenterItems()
    {
        $items = $this->notifications();
        $business = $this->business;

        if ($business && ! $business->dashboard_notifications) {
            return $items->whereRaw('1 = 0');
        }

        if ($business && ! $business->contact_message_alerts) {
            $items->whereNull('contact_message_id');
        }

        return $items;
    }
}
