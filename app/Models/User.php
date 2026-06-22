<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'profile_photo',
        'home_location',
        'google_id',
        'password',
        'role',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isAppUser(): bool
    {
        return $this->role === UserRole::AppUser;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo === null) {
            return null;
        }

        return asset('storage/'.$this->profile_photo);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAppApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'profile_photo_url' => $this->profile_photo_url,
            'home_location' => $this->home_location,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role === UserRole::Admin ? 'Administrator' : 'App User',
            'profile_photo_url' => $this->profile_photo_url,
            'uses_google' => $this->google_id !== null,
            'email_verified' => $this->email_verified_at !== null,
            'created_at' => $this->created_at?->format('M j, Y'),
        ];
    }

    public function savedLocations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'user_saved_locations')
            ->withTimestamps()
            ->orderByDesc('user_saved_locations.created_at');
    }

    public function locationRatings(): HasMany
    {
        return $this->hasMany(LocationRating::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class)->latest();
    }
}
