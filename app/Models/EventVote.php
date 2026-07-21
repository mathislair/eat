<?php

namespace App\Models;

use Database\Factories\EventVoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_id', 'user_id', 'submitted_at'])]
class EventVote extends Model
{
    /** @use HasFactory<EventVoteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nationalities picked on this ballot.
     *
     * @return BelongsToMany<Nationality, $this>
     */
    public function nationalities(): BelongsToMany
    {
        return $this->belongsToMany(Nationality::class, 'event_vote_nationality')
            ->withPivot('preference');
    }

    /**
     * Criteria (price/diet/style values) picked on this ballot.
     *
     * @return HasMany<EventVoteAttribute, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(EventVoteAttribute::class);
    }
}
