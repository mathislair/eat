<?php

namespace App\Models;

use App\Enums\AttributeType;
use App\Enums\VotePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's standing feeling about a single criterion value (e.g. price €€,
 * diet vegan). The persistent, event-independent sibling of
 * {@see EventVoteAttribute}.
 */
#[Fillable(['type', 'value', 'preference'])]
class UserAttributePreference extends Model
{
    protected $table = 'user_attribute_preference';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'preference' => VotePreference::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
