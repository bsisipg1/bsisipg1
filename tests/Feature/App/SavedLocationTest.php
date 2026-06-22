<?php

use App\Enums\LocationCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createTestLocation(array $overrides = []): Location
{
    return Location::query()->create(array_merge([
        'name' => 'Test Beach',
        'category' => LocationCategory::Nature,
        'description' => 'A scenic test location.',
        'latitude' => 9.1234567,
        'longitude' => 123.7654321,
        'image' => 'locations/test.jpg',
    ], $overrides));
}

test('guest cannot access saved locations endpoints', function () {
    $location = createTestLocation();

    $this->getJson('/api/app/saved-locations')->assertUnauthorized();
    $this->postJson('/api/app/saved-locations', ['location_id' => $location->id])->assertUnauthorized();
    $this->putJson('/api/app/saved-locations', ['location_ids' => [$location->id]])->assertUnauthorized();
    $this->deleteJson("/api/app/saved-locations/{$location->id}")->assertUnauthorized();
});

test('admin token is rejected by appuser middleware', function () {
    $admin = User::factory()->admin()->create();
    $location = createTestLocation();

    Sanctum::actingAs($admin);

    $this->getJson('/api/app/saved-locations')->assertForbidden();
    $this->postJson('/api/app/saved-locations', ['location_id' => $location->id])->assertForbidden();
    $this->putJson('/api/app/saved-locations', ['location_ids' => [$location->id]])->assertForbidden();
    $this->deleteJson("/api/app/saved-locations/{$location->id}")->assertForbidden();
});

test('app user can save list and remove a location', function () {
    $user = User::factory()->appUser()->create();
    $location = createTestLocation(['name' => 'Saved Spot']);

    Sanctum::actingAs($user);

    $this->postJson('/api/app/saved-locations', ['location_id' => $location->id])
        ->assertCreated()
        ->assertJsonPath('data.id', $location->id)
        ->assertJsonPath('data.name', 'Saved Spot');

    $this->getJson('/api/app/saved-locations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $location->id);

    $this->deleteJson("/api/app/saved-locations/{$location->id}")
        ->assertOk()
        ->assertJson(['message' => 'Location removed.']);

    $this->getJson('/api/app/saved-locations')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('duplicate save is idempotent', function () {
    $user = User::factory()->appUser()->create();
    $location = createTestLocation();

    Sanctum::actingAs($user);

    $this->postJson('/api/app/saved-locations', ['location_id' => $location->id])->assertCreated();
    $this->postJson('/api/app/saved-locations', ['location_id' => $location->id])->assertCreated();

    $this->getJson('/api/app/saved-locations')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    expect($user->savedLocations()->count())->toBe(1);
});

test('delete returns 404 when location was not saved', function () {
    $user = User::factory()->appUser()->create();
    $location = createTestLocation();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/app/saved-locations/{$location->id}")
        ->assertNotFound();
});

test('sync replaces the users saved set', function () {
    $user = User::factory()->appUser()->create();
    $first = createTestLocation(['name' => 'First']);
    $second = createTestLocation(['name' => 'Second']);
    $third = createTestLocation(['name' => 'Third']);

    Sanctum::actingAs($user);

    $user->savedLocations()->attach([$first->id, $second->id]);

    $response = $this->putJson('/api/app/saved-locations', ['location_ids' => [$second->id, $third->id]])
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $savedIds = collect($response->json('data'))->pluck('id')->sort()->values()->all();
    expect($savedIds)->toBe([$second->id, $third->id]);

    expect($user->savedLocations()->pluck('locations.id')->sort()->values()->all())
        ->toBe([$second->id, $third->id]);
});

test('sync accepts an empty list', function () {
    $user = User::factory()->appUser()->create();
    $location = createTestLocation();

    Sanctum::actingAs($user);
    $user->savedLocations()->attach($location->id);

    $this->putJson('/api/app/saved-locations', ['location_ids' => []])
        ->assertOk()
        ->assertJsonCount(0, 'data');

    expect($user->savedLocations()->count())->toBe(0);
});
