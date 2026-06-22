<?php

namespace App\Enums;

enum EventTone: string
{
    case Orange = 'orange';
    case Teal = 'teal';
    case Gold = 'gold';
    case Blue = 'blue';

    public function label(): string
    {
        return match ($this) {
            self::Orange => 'Orange',
            self::Teal => 'Teal',
            self::Gold => 'Gold',
            self::Blue => 'Blue',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Orange => 'event-orange',
            self::Teal => 'event-teal',
            self::Gold => 'event-gold',
            self::Blue => 'event-blue',
        };
    }

    /**
     * @return list<array{value: string, label: string, css_class: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $tone) => [
                'value' => $tone->value,
                'label' => $tone->label(),
                'css_class' => $tone->cssClass(),
            ],
            self::cases(),
        );
    }
}
