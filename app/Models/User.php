<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Whether this user may reach the admin-only endpoints.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Events this user created (owns).
     *
     * @return HasMany<Event, $this>
     */
    public function createdEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'creator_id');
    }

    /**
     * Events this user is attending.
     *
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /**
     * Cuisines this user has a standing taste for, each carrying a 'want' or
     * 'avoid' preference on the pivot. Their persistent food profile.
     *
     * @return BelongsToMany<Nationality, $this>
     */
    public function nationalityPreferences(): BelongsToMany
    {
        return $this->belongsToMany(Nationality::class, 'user_nationality_preference')
            ->withPivot('preference');
    }

    /**
     * Criteria (price/diet/style values) this user has a standing taste for.
     *
     * @return HasMany<UserAttributePreference, $this>
     */
    public function attributePreferences(): HasMany
    {
        return $this->hasMany(UserAttributePreference::class);
    }
}
