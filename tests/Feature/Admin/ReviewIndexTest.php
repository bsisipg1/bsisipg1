<?php

use App\Enums\LocationCategory;
use App\Models\Location;
use App\Models\LocationRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createReviewLocation(array $overrides = []): Location
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

test('admin reviews page shows locations with app user ratings', function () {
    $admin = User::factory()->admin()->create();
    $appUser = User::factory()->appUser()->create([
        'name' => 'Mobile User',
    ]);

    $ratedLocation = createReviewLocation(['name' => 'Baao Lake']);
    $unratedLocation = createReviewLocation(['name' => 'Quiet Park']);

    LocationRating::query()->create([
        'user_id' => $appUser->id,
        'location_id' => $ratedLocation->id,
        'rating' => 5,
        'comment' => 'Beautiful scenery!',
    ]);

    actingAs($admin)
        ->get('/admin/reviews')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Reviews')
            ->has('locations', 1)
            ->where('locations.0.name', 'Baao Lake')
            ->where('locations.0.ratings_count', 1)
            ->where('locations.0.average_rating', 5)
            ->where('locations.0.reviews.0.comment', 'Beautiful scenery!')
            ->where('locations.0.reviews.0.user.name', 'Mobile User'));
});

test('admin reviews page ignores ratings from administrators', function () {
    $admin = User::factory()->admin()->create();
    $location = createReviewLocation();

    LocationRating::query()->create([
        'user_id' => $admin->id,
        'location_id' => $location->id,
        'rating' => 4,
        'comment' => 'Admin review',
    ]);

    actingAs($admin)
        ->get('/admin/reviews')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Reviews')
            ->has('locations', 0));
});

test('guest cannot access admin reviews page', function () {
    $this->get('/admin/reviews')->assertRedirect('/login');
});
