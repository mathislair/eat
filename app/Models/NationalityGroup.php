<?php

namespace App\Models;

use Database\Factories\NationalityGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class NationalityGroup extends Model
{
    /** @use HasFactory<NationalityGroupFactory> */
    use HasFactory;

    /**
     * Nationalities that belong to this group.
     *
     * @return BelongsToMany<Nationality, $this>
     */
    public function nationalities(): BelongsToMany
    {
        return $this->belongsToMany(
            Nationality::class,
            'group_nationality',
            'nationality_group_id',
            'nationality_id',
        )->withTimestamps();
    }
}
