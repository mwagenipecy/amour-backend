<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'age',
        'bio',
        'gender',
        'looking_for',
        'relationship_goal',
        'education',
        'occupation',
        'city',
        'country',
        'height',
        'religion',
        'smoking',
        'drinking',
        'has_children',
        'zodiac_sign',
        'latitude',
        'longitude',
        'is_online',
        'last_seen',
        'last_active',
        'user_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'has_children' => 'boolean',
        'is_online' => 'boolean',
        'last_seen' => 'datetime',
        'last_active' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user's photos.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(UserPhoto::class);
    }

    /**
     * Get the user's hobbies.
     */
    public function hobbies(): BelongsToMany
    {
        return $this->belongsToMany(Hobby::class, 'user_hobbies');
    }

    /**
     * Get the user's interests.
     */
    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'user_interests');
    }

    /**
     * Get the user's likes.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'liker_id');
    }

    /**
     * Get the user's received likes.
     */
    public function receivedLikes(): HasMany
    {
        return $this->hasMany(Like::class, 'liked_id');
    }

    /**
     * Get the user's matches.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(UserMatch::class, 'user1_id')
            ->orWhere('user2_id', $this->id);
    }

    /**
     * Get the user's conversations.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user1_id')
            ->orWhere('user2_id', $this->id);
    }

    /**
     * Check if user has liked another user.
     */
    public function hasLiked(User $user): bool
    {
        return $this->likes()->where('liked_id', $user->id)->exists();
    }

    /**
     * Check if user has been liked by another user.
     */
    public function hasBeenLikedBy(User $user): bool
    {
        return $this->receivedLikes()->where('liker_id', $user->id)->exists();
    }

    /**
     * Check if there's a mutual match.
     */
    public function hasMutualMatch(User $user): bool
    {
        return $this->hasLiked($user) && $this->hasBeenLikedBy($user);
    }

    /**
     * Get potential matches based on preferences.
     */
    public function getPotentialMatches()
    {
        return self::where('id', '!=', $this->id)
            ->where('gender', $this->looking_for)
            ->where('looking_for', $this->gender)
            ->where('age', '>=', $this->age - 5)
            ->where('age', '<=', $this->age + 5)
            ->whereNotIn('id', $this->likes()->pluck('liked_id'))
            ->whereNotIn('id', $this->receivedLikes()->pluck('liker_id'))
            ->get();
    }

    /**
     * Update user's online status.
     */
    public function updateOnlineStatus(bool $isOnline = true): void
    {
        $this->update([
            'is_online' => $isOnline,
            'last_seen' => now(),
            'last_active' => now(),
        ]);
    }

    /**
     * Get formatted distance from another user.
     */
    public function getDistanceFrom(User $user): float
    {
        if (!$this->latitude || !$this->longitude || !$user->latitude || !$user->longitude) {
            return 0;
        }

        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($user->latitude);
        $lon2 = deg2rad($user->longitude);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return 6371 * $c; // Earth's radius in kilometers
    }
}
