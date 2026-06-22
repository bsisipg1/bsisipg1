<?php

namespace App\Models;

use App\Enums\LocationGalleryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationGallery extends Model
{
    protected $table = 'locations_gallery';

    protected $fillable = [
        'location_id',
        'type',
        'path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => LocationGalleryType::class,
            'sort_order' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'url' => $this->url,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
