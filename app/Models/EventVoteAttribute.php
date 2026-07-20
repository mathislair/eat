<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'value'])]
class EventVoteAttribute extends Model
{
    protected $table = 'event_vote_attribute';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
        ];
    }

    /**
     * @return BelongsTo<EventVote, $this>
     */
    public function eventVote(): BelongsTo
    {
        return $this->belongsTo(EventVote::class);
    }
}
