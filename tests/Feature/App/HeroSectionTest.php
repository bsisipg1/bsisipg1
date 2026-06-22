<?php

use App\Enums\LocationGalleryType;
use App\Models\AppHeroSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('hero sections api returns only active items in display order', function () {
    AppHeroSection::query()->create([
        'title' => 'Hidden Slide',
        'subtitle' => null,
        'type' => LocationGalleryType::Image,
        'media_path' => 'app/hero/hidden.jpg',
        'sort_order' => 1,
        'is_active' => false,
    ]);

    AppHeroSection::query()->create([
        'title' => 'First Slide',
        'subtitle' => null,
        'type' => LocationGalleryType::Image,
        'media_path' => 'app/hero/welcome.jpg',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    AppHeroSection::query()->create([
        'title' => 'Second Slide',
        'subtitle' => 'Explore Baao',
        'type' => LocationGalleryType::Video,
        'media_path' => 'app/hero/promo.mp4',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $this->getJson('/api/app/hero-sections')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'First Slide')
        ->assertJsonPath('data.0.type', 'image')
        ->assertJsonPath('data.0.media_url', asset('storage/app/hero/welcome.jpg'))
        ->assertJsonPath('data.1.title', 'Second Slide')
        ->assertJsonPath('data.1.type', 'video')
        ->assertJsonPath('data.1.subtitle', 'Explore Baao');
});

test('hero sections api returns empty list when none are active', function () {
    AppHeroSection::query()->create([
        'title' => 'Draft',
        'subtitle' => null,
        'type' => LocationGalleryType::Image,
        'media_path' => 'app/hero/draft.jpg',
        'sort_order' => 1,
        'is_active' => false,
    ]);

    $this->getJson('/api/app/hero-sections')
        ->assertOk()
        ->assertJsonPath('data', []);
});

test('hero sections api is publicly accessible', function () {
    $this->getJson('/api/app/hero-sections')->assertOk();
});
