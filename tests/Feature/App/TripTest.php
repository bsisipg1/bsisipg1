<?php

use App\Enums\LocationCategory;
use App\Models\Location;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createTripLocation(array $overrides = []): Location
{
    return Location::query()->create(array_merge([
        'name' => 'Trip Stop',
        'category' => LocationCategory::Nature,
        'description' => 'A scenic test location.',
        'latitude' => 9.1234567,
        'longitude' => 123.7654321,
        'image' => 'locations/test.jpg',
    ], $overrides));
}

test('guest cannot access trip endpoints', function () {
    $trip = Trip::query()->create([
        'user_id' => User::factory()->appUser()->create()->id,
        'name' => 'Weekend Trip',
    ]);

    $this->getJson('/api/app/trips')->assertUnauthorized();
    $this->postJson('/api/app/trips', ['name' => 'New Trip'])->assertUnauthorized();
    $this->getJson("/api/app/trips/{$trip->id}")->assertUnauthorized();
});

test('admin token is rejected by appuser middleware for trips', function () {
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/app/trips')->assertForbidden();
    $this->postJson('/api/app/trips', ['name' => 'New Trip'])->assertForbidden();
});

test('app user can create list and view a saved trip', function () {
    $user = User::factory()->appUser()->create();
    $firstStop = createTripLocation(['name' => 'Baao Lake']);
    $secondStop = createTripLocation(['name' => 'Town Plaza']);

    Sanctum::actingAs($user);

    $this->postJson('/api/app/trips', [
        'name' => 'Baao Weekend',
        'description' => 'A two-day Baao itinerary.',
        'start_date' => '2026-08-15',
        'end_date' => '2026-08-16',
        'location_ids' => [$firstStop->id, $secondStop->id],
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Baao Weekend')
        ->assertJsonPath('data.locations_count', 2)
        ->assertJsonPath('data.locations.0.name', 'Baao Lake')
        ->assertJsonPath('data.locations.0.sort_order', 1)
        ->assertJsonPath('data.locations.1.name', 'Town Plaza')
        ->assertJsonPath('data.locations.1.sort_order', 2);

    $this->getJson('/api/app/trips')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Baao Weekend')
        ->assertJsonPath('data.0.locations_count', 2);

    $tripId = Trip::query()->value('id');

    $this->getJson("/api/app/trips/{$tripId}")
        ->assertOk()
        ->assertJsonPath('data.locations.1.sort_order', 2);
});

test('app user can update sync and delete their trip', function () {
    $user = User::factory()->appUser()->create();
    $firstStop = createTripLocation(['name' => 'First']);
    $secondStop = createTripLocation(['name' => 'Second']);
    $thirdStop = createTripLocation(['name' => 'Third']);

    Sanctum::actingAs($user);

    $trip = Trip::query()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
    ]);
    $trip->locations()->attach($firstStop->id, ['sort_order' => 1]);

    $this->putJson("/api/app/trips/{$trip->id}", [
        'name' => 'Updated Trip',
        'description' => 'Updated notes',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Trip')
        ->assertJsonPath('data.description', 'Updated notes');

    $this->putJson("/api/app/trips/{$trip->id}/locations", [
        'location_ids' => [$secondStop->id, $thirdStop->id],
    ])
        ->assertOk()
        ->assertJsonCount(2, 'data.locations')
        ->assertJsonPath('data.locations.0.name', 'Second')
        ->assertJsonPath('data.locations.1.name', 'Third');

    $this->deleteJson("/api/app/trips/{$trip->id}/locations/{$thirdStop->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.locations');

    $this->deleteJson("/api/app/trips/{$trip->id}")
        ->assertOk()
        ->assertJson(['message' => 'Trip deleted.']);

    expect(Trip::query()->count())->toBe(0);
});

test('app user cannot access another users trip', function () {
    $owner = User::factory()->appUser()->create();
    $otherUser = User::factory()->appUser()->create();

    $trip = Trip::query()->create([
        'user_id' => $owner->id,
        'name' => 'Private Trip',
    ]);

    Sanctum::actingAs($otherUser);

    $this->getJson("/api/app/trips/{$trip->id}")->assertNotFound();
    $this->putJson("/api/app/trips/{$trip->id}", ['name' => 'Hacked'])->assertNotFound();
    $this->deleteJson("/api/app/trips/{$trip->id}")->assertNotFound();
});
