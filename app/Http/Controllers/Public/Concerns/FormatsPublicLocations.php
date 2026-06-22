<?php

namespace App\Http\Controllers\Public\Concerns;

use App\Models\Location;
use Illuminate\Support\Str;

trait FormatsPublicLocations
{
    /**
     * @return array<string, mixed>
     */
    protected function formatPublicLocation(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'map_label' => Str::words($location->name, 2, ''),
            'category' => $location->category->value,
            'category_label' => $location->category->label(),
            'description' => $location->description,
            'summary' => Str::limit($location->description, 140),
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'image_url' => $location->image_url,
            'gallery_images' => $location->gallery
                ->map(fn ($item) => $item->url)
                ->values()
                ->all(),
            'average_rating' => $location->ratings_avg_rating !== null
                ? round((float) $location->ratings_avg_rating, 1)
                : null,
            'ratings_count' => (int) $location->ratings_count,
        ];
    }
}
