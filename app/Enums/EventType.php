<?php

namespace App\Enums;

enum EventType: string
{
    case Festival = 'festival';
    case Culture = 'culture';
    case Community = 'community';
    case Sports = 'sports';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Festival => 'Festival',
            self::Culture => 'Culture',
            self::Community => 'Community',
            self::Sports => 'Sports',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }
}
