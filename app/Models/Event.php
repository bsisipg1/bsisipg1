<?php

namespace App\Models;

use App\Enums\EventTone;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'description',
        'event_date',
        'time',
        'venue',
        'tone',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'tone' => EventTone::class,
            'event_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image === null) {
            return null;
        }

        return asset('storage/'.$this->image);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->event_date?->format('F j, Y'),
            'event_date' => $this->event_date?->toDateString(),
            'time' => $this->time,
            'venue' => $this->venue,
            'tone' => $this->tone->value,
            'tone_class' => $this->tone->cssClass(),
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
