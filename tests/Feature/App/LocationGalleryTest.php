<?php

use App\Enums\LocationCategory;
use App\Enums\LocationGalleryType;
use App\Models\Location;
use App\Models\LocationGallery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createGalleryLocation(): Location
{
    return Location::query()->create([
        'name' => 'Gallery Spot',
        'category' => LocationCategory::Nature,
        'description' => 'A location with gallery items.',
        'latitude' => 13.4481000,
        'longitude' => 123.3562000,
        'image' => 'locations/cover.jpg',
    ]);
}

test('location index api includes gallery items', function () {
    $location = createGalleryLocation();

    LocationGallery::query()->create([
        'location_id' => $location->id,
        'type' => LocationGalleryType::Image,
        'path' => 'locations/gallery/photo.jpg',
        'sort_order' => 1,
    ]);

    LocationGallery::query()->create([
        'location_id' => $location->id,
        'type' => LocationGalleryType::Video,
        'path' => 'locations/gallery/clip.mp4',
        'sort_order' => 2,
    ]);

    $response = $this->getJson('/api/app/locations');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.id', $location->id)
        ->assertJsonCount(2, 'data.0.gallery')
        ->assertJsonPath('data.0.gallery.0.type', 'image')
        ->assertJsonPath('data.0.gallery.0.url', asset('storage/locations/gallery/photo.jpg'))
        ->assertJsonPath('data.0.gallery.1.type', 'video');
});

test('location show api includes gallery items', function () {
    $location = createGalleryLocation();

    LocationGallery::query()->create([
        'location_id' => $location->id,
        'type' => LocationGalleryType::Image,
        'path' => 'locations/gallery/show-photo.jpg',
        'sort_order' => 1,
    ]);

    $this->getJson("/api/app/locations/{$location->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $location->id)
        ->assertJsonCount(1, 'data.gallery')
        ->assertJsonPath('data.gallery.0.type', 'image')
        ->assertJsonPath('data.gallery.0.sort_order', 1);
});

test('location api returns empty gallery array when none exist', function () {
    $location = createGalleryLocation();

    $this->getJson("/api/app/locations/{$location->id}")
        ->assertOk()
        ->assertJsonPath('data.gallery', []);
});
