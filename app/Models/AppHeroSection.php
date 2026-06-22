<?php

namespace App\Models;

use App\Enums\LocationGalleryType;
use Illuminate\Database\Eloquent\Model;

class AppHeroSection extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'type',
        'media_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => LocationGalleryType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getMediaUrlAttribute(): string
    {
        return asset('storage/'.$this->media_path);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'type' => $this->type->value,
            'media_url' => $this->media_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
