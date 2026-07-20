<?php

namespace App\Models;

use Database\Factories\NationalityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Nationality extends Model
{
    /** @use HasFactory<NationalityFactory> */
    use HasFactory;

    /**
     * Groups this nationality has been sorted into (e.g. "Asian", "Spicy").
     *
     * @return BelongsToMany<NationalityGroup, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            NationalityGroup::class,
            'group_nationality',
            'nationality_id',
            'nationality_group_id',
        )->withTimestamps();
    }
}
