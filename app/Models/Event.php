<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\Meal;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'date', 'meal', 'creator_id', 'status', 'validated_at'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => EventStatus::Voting->value,
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event): void {
            if (empty($event->invite_code)) {
                $event->invite_code = static::generateUniqueInviteCode();
            }
        });
    }

    /**
     * Generate an 8-character uppercase alphanumeric code not already in use.
     */
    public static function generateUniqueInviteCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'meal' => Meal::class,
            'status' => EventStatus::class,
            'validated_at' => 'datetime',
        ];
    }

    public function isVoting(): bool
    {
        return $this->status === EventStatus::Voting;
    }

    public function isClosed(): bool
    {
        return $this->status === EventStatus::Closed;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return HasMany<EventVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(EventVote::class);
    }

    /**
     * The ranked restaurant shortlist computed once voting closes.
     *
     * @return BelongsToMany<Restaurant, $this>
     */
    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class)
            ->withPivot('match_score', 'position')
            ->orderBy('event_restaurant.position');
    }

    /**
     * Attendees' accept/reject swipes on the shortlist.
     *
     * @return HasMany<RestaurantSwipe, $this>
     */
    public function swipes(): HasMany
    {
        return $this->hasMany(RestaurantSwipe::class);
    }
}
