<?php

use App\Enums\LocationCategory;
use App\Models\Location;
use App\Models\LocationRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createDashboardLocation(array $overrides = []): Location
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

test('admin dashboard shows top rated locations chart data', function () {
    $admin = User::factory()->admin()->create();
    $firstAppUser = User::factory()->appUser()->create();
    $secondAppUser = User::factory()->appUser()->create();

    $topLocation = createDashboardLocation(['name' => 'Baao Lake']);
    $secondLocation = createDashboardLocation(['name' => 'Heritage Park']);

    LocationRating::query()->create([
        'user_id' => $firstAppUser->id,
        'location_id' => $topLocation->id,
        'rating' => 5,
        'comment' => 'Excellent',
    ]);

    LocationRating::query()->create([
        'user_id' => $secondAppUser->id,
        'location_id' => $topLocation->id,
        'rating' => 5,
        'comment' => 'Lovely',
    ]);

    LocationRating::query()->create([
        'user_id' => $firstAppUser->id,
        'location_id' => $secondLocation->id,
        'rating' => 3,
        'comment' => 'Okay',
    ]);

    actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('stats.locations_count', 2)
            ->where('stats.app_users_count', 2)
            ->where('stats.reviews_count', 3)
            ->has('topRatedLocations', 2)
            ->where('topRatedLocations.0.name', 'Baao Lake')
            ->where('topRatedLocations.0.average_rating', 5)
            ->where('topRatedLocations.0.ratings_count', 2)
            ->where('topRatedLocations.1.name', 'Heritage Park')
            ->where('topRatedLocations.1.average_rating', 3)
            ->where('topRatedLocations.1.ratings_count', 1));
});

test('guest cannot access admin dashboard', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');
});
