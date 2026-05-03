<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'show_id',
        'season_no',
        'episode_no',
        'title',
        'description',
        'air_datetime',
        'duration_minutes',
        'thumbnail_url',
        'youtube_link',
        'is_aired',
        'notified',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'season_no' => 'integer',
            'episode_no' => 'integer',
            'air_datetime' => 'datetime',
            'duration_minutes' => 'integer',
            'is_aired' => 'boolean',
            'notified' => 'boolean',
        ];
    }

    /**
     * Get the show that owns the episode.
     */
    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    /**
     * Get all reminders for the episode.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get all notification logs for the episode.
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Scope a query to only include unaired episodes.
     */
    public function scopeUnaired($query)
    {
        return $query->where('is_aired', false);
    }

    /**
     * Scope a query to only include episodes that haven't been notified.
     */
    public function scopeNotNotified($query)
    {
        return $query->where('notified', false);
    }

    /**
     * Scope a query to only include upcoming episodes within a time window.
     */
    public function scopeUpcoming($query, $minutes = 60)
    {
        return $query->where('air_datetime', '<=', now()->addMinutes($minutes))
            ->where('air_datetime', '>', now());
    }

    /**
     * Get the reminder associated with this episode (via show).
     */
    public function reminder()
    {
        return $this->hasOne(Reminder::class, 'show_id', 'show_id');
    }

    /**
     * Get air_date attribute from air_datetime.
     */
    public function getAirDateAttribute()
    {
        return $this->air_datetime?->toDateString();
    }
}
