<?php

namespace App\Models;

use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'nationality_id', 'description'])]
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    /**
     * The cuisine this restaurant serves.
     *
     * @return BelongsTo<Nationality, $this>
     */
    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    /**
     * Price/diet/style tags used to match against a group's votes.
     *
     * @return HasMany<RestaurantAttribute, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(RestaurantAttribute::class);
    }
}
