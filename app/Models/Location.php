<?php

namespace App\Models;

use App\Enums\LocationCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'latitude',
        'longitude',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'category' => LocationCategory::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/'.$this->image);
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_saved_locations')
            ->withTimestamps();
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(LocationGallery::class)->orderBy('sort_order');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(LocationRating::class)->latest();
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'description' => $this->description,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->relationLoaded('gallery')) {
            $data['gallery'] = $this->gallery
                ->map(fn (LocationGallery $item) => $item->toApiArray())
                ->values()
                ->all();
        }

        if (isset($this->ratings_count)) {
            $data['ratings_count'] = (int) $this->ratings_count;
            $data['average_rating'] = $this->ratings_avg_rating !== null
                ? round((float) $this->ratings_avg_rating, 1)
                : null;
        }

        return $data;
    }

    /**
     * Saved-location payload including pivot timestamp when loaded via [savedByUsers].
     *
     * @return array<string, mixed>
     */
    public function toSavedApiArray(): array
    {
        $data = $this->toApiArray();

        if ($this->pivot !== null) {
            $data['saved_at'] = $this->pivot->created_at?->toIso8601String();
        }

        return $data;
    }
}
