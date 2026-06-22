<?php

use App\Enums\LocationCategory;
use App\Models\Location;
use App\Models\LocationRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createRatingLocation(array $overrides = []): Location
{
    return Location::query()->create(array_merge([
        'name' => 'Rated Beach',
        'category' => LocationCategory::Nature,
        'description' => 'A scenic test location.',
        'latitude' => 9.1234567,
        'longitude' => 123.7654321,
        'image' => 'locations/test.jpg',
    ], $overrides));
}

test('guest can list location ratings', function () {
    $location = createRatingLocation();
    $user = User::factory()->appUser()->create([
        'profile_photo' => 'users/profile-photos/test.jpg',
    ]);

    LocationRating::query()->create([
        'user_id' => $user->id,
        'location_id' => $location->id,
        'rating' => 5,
        'comment' => 'Amazing place!',
    ]);

    $this->getJson("/api/app/locations/{$location->id}/ratings")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rating', 5)
        ->assertJsonPath('data.0.comment', 'Amazing place!')
        ->assertJsonPath('data.0.user.name', $user->name)
        ->assertJsonPath('data.0.user.profile_photo_url', $user->profile_photo_url);
});

test('location api includes rating summary', function () {
    $location = createRatingLocation();
    $firstUser = User::factory()->appUser()->create();
    $secondUser = User::factory()->appUser()->create();

    LocationRating::query()->create([
        'user_id' => $firstUser->id,
        'location_id' => $location->id,
        'rating' => 4,
        'comment' => 'Nice',
    ]);

    LocationRating::query()->create([
        'user_id' => $secondUser->id,
        'location_id' => $location->id,
        'rating' => 5,
        'comment' => 'Great',
    ]);

    $this->getJson("/api/app/locations/{$location->id}")
        ->assertOk()
        ->assertJsonPath('data.ratings_count', 2)
        ->assertJsonPath('data.average_rating', 4.5);
});

test('guest cannot submit or manage ratings', function () {
    $location = createRatingLocation();

    $this->postJson("/api/app/locations/{$location->id}/ratings", [
        'rating' => 5,
        'comment' => 'Love it',
    ])->assertUnauthorized();

    $this->getJson("/api/app/locations/{$location->id}/my-rating")->assertUnauthorized();
    $this->deleteJson("/api/app/locations/{$location->id}/ratings")->assertUnauthorized();
});

test('admin token is rejected for rating actions', function () {
    $location = createRatingLocation();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson("/api/app/locations/{$location->id}/ratings", [
        'rating' => 5,
    ])->assertForbidden();
});

test('app user can rate update and delete a location', function () {
    $location = createRatingLocation();
    $user = User::factory()->appUser()->create();

    Sanctum::actingAs($user);

    $this->postJson("/api/app/locations/{$location->id}/ratings", [
        'rating' => 4,
        'comment' => 'Very good spot.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.rating', 4)
        ->assertJsonPath('data.comment', 'Very good spot.');

    $this->getJson("/api/app/locations/{$location->id}/my-rating")
        ->assertOk()
        ->assertJsonPath('data.rating', 4);

    $this->postJson("/api/app/locations/{$location->id}/ratings", [
        'rating' => 5,
        'comment' => 'Changed my mind, perfect!',
    ])
        ->assertOk()
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.comment', 'Changed my mind, perfect!');

    expect(LocationRating::query()->count())->toBe(1);

    $this->deleteJson("/api/app/locations/{$location->id}/ratings")
        ->assertOk()
        ->assertJson(['message' => 'Rating removed.']);

    expect(LocationRating::query()->count())->toBe(0);
});

test('rating validation rejects invalid star values', function () {
    $location = createRatingLocation();
    Sanctum::actingAs(User::factory()->appUser()->create());

    $this->postJson("/api/app/locations/{$location->id}/ratings", [
        'rating' => 0,
        'comment' => 'Too low',
    ])->assertUnprocessable();

    $this->postJson("/api/app/locations/{$location->id}/ratings", [
        'rating' => 6,
        'comment' => 'Too high',
    ])->assertUnprocessable();

    $this->postJson("/api/app/locations/{$location->id}/ratings", [
        'rating' => 3,
    ])->assertCreated();
});

test('my rating returns 404 when user has not rated', function () {
    $location = createRatingLocation();
    Sanctum::actingAs(User::factory()->appUser()->create());

    $this->getJson("/api/app/locations/{$location->id}/my-rating")
        ->assertNotFound();
});

test('delete rating returns 404 when user has not rated', function () {
    $location = createRatingLocation();
    Sanctum::actingAs(User::factory()->appUser()->create());

    $this->deleteJson("/api/app/locations/{$location->id}/ratings")
        ->assertNotFound();
});
