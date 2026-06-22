<?php

use App\Enums\LocationCategory;
use App\Models\Location;
use App\Models\LocationRating;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

function createStatsLocation(array $overrides = []): Location
{
    return Location::query()->create(array_merge([
        'name' => 'Stats Spot',
        'category' => LocationCategory::Nature,
        'description' => 'A scenic test location.',
        'latitude' => 13.4539,
        'longitude' => 123.3648,
        'image' => 'locations/test.jpg',
    ], $overrides));
}

test('guest cannot view profile stats', function () {
    getJson('/api/app/user/stats')->assertUnauthorized();
});

test('profile stats return zero for a fresh user', function () {
    Sanctum::actingAs(User::factory()->appUser()->create());

    getJson('/api/app/user/stats')
        ->assertOk()
        ->assertJsonPath('data.saved', 0)
        ->assertJsonPath('data.visited', 0)
        ->assertJsonPath('data.trips', 0);
});

test('profile stats reflect saved, visited and trips counts', function () {
    $user = User::factory()->appUser()->create();
    Sanctum::actingAs($user);

    $first = createStatsLocation(['name' => 'First']);
    $second = createStatsLocation(['name' => 'Second']);

    $user->savedLocations()->attach([$first->id, $second->id]);

    LocationRating::query()->create([
        'user_id' => $user->id,
        'location_id' => $first->id,
        'rating' => 5,
        'comment' => 'Loved it',
    ]);

    Trip::query()->create([
        'user_id' => $user->id,
        'name' => 'Weekend trip',
    ]);

    getJson('/api/app/user/stats')
        ->assertOk()
        ->assertJsonPath('data.saved', 2)
        ->assertJsonPath('data.visited', 1)
        ->assertJsonPath('data.trips', 1);
});
