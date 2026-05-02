<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'timezone',
        'avatar',
        'email_notifications',
        'sms_notifications',
        'sms_gateway_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'sms_gateway_enabled' => 'boolean',
        ];
    }

    /**
     * Get all shows for the user.
     */
    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    /**
     * Get all reminders for the user.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get all notification logs for the user.
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Get the user's SMTP settings.
     */
    public function smtpSettings(): HasOne
    {
        return $this->hasOne(UserSmtpSetting::class);
    }

    /**
     * Get the user's SMS settings.
     */
    public function smsSettings(): HasOne
    {
        return $this->hasOne(UserSmsSetting::class);
    }

    /**
     * Get all watchlist notes for the user.
     */
    public function watchlistNotes(): HasMany
    {
        return $this->hasMany(WatchlistNote::class);
    }
}
