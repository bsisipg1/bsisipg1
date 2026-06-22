<?php

namespace App\Enums;

enum LocationCategory: string
{
    case Nature = 'nature';
    case Culture = 'culture';
    case Food = 'food';
    case Resorts = 'resorts';
    case Religious = 'religious';
    case Historical = 'historical';
    case Parks = 'parks';
    case LakesWater = 'lakes_water';
    case Landmarks = 'landmarks';
    case Markets = 'markets';
    case Adventure = 'adventure';
    case Museums = 'museums';
    case Accommodation = 'accommodation';
    case Entertainment = 'entertainment';
    case Wellness = 'wellness';
    case Shopping = 'shopping';
    case Community = 'community';

    public function label(): string
    {
        return match ($this) {
            self::Nature => 'Nature',
            self::Culture => 'Culture',
            self::Food => 'Food & Dining',
            self::Resorts => 'Resort',
            self::Religious => 'Religious Site',
            self::Historical => 'Historical Site',
            self::Parks => 'Park & Recreation',
            self::LakesWater => 'Lake & Water',
            self::Landmarks => 'Landmark',
            self::Markets => 'Market',
            self::Adventure => 'Adventure & Sports',
            self::Museums => 'Museum & Gallery',
            self::Accommodation => 'Accommodation',
            self::Entertainment => 'Entertainment',
            self::Wellness => 'Wellness & Spa',
            self::Shopping => 'Shopping',
            self::Community => 'Community Space',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ],
            self::cases(),
        );
    }
}
