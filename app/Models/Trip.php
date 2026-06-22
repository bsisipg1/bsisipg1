<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Trip extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'trip_locations')
            ->withPivot(['sort_order', 'notes'])
            ->withTimestamps()
            ->orderBy('trip_locations.sort_order');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'locations_count' => $this->locations_count ?? $this->locations()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->relationLoaded('locations')) {
            $data['locations'] = $this->locations
                ->map(fn (Location $location) => $this->formatTripLocation($location))
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTripLocation(Location $location): array
    {
        $data = $location->toApiArray();

        if ($location->pivot !== null) {
            $data['sort_order'] = (int) $location->pivot->sort_order;
            $data['notes'] = $location->pivot->notes;
            $data['added_at'] = $location->pivot->created_at?->toIso8601String();
        }

        return $data;
    }
}
