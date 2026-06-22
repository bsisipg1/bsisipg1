<?php

use App\Enums\EventTone;
use App\Enums\EventType;
use App\Enums\LocationCategory;
use App\Models\Event;
use App\Models\Location;
use App\Models\LocationRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createPublicHomeLocation(array $overrides = []): Location
{
    return Location::query()->create(array_merge([
        'name' => 'Baao Lake View Park',
        'category' => LocationCategory::Nature,
        'description' => 'A stunning lakeside park offering panoramic views.',
        'latitude' => 13.4481,
        'longitude' => 123.3562,
        'image' => 'locations/test.jpg',
    ], $overrides));
}

function createPublicHomeEvent(array $overrides = []): Event
{
    return Event::query()->create(array_merge([
        'title' => 'Baao Town Fiesta',
        'type' => EventType::Festival,
        'description' => 'Annual town fiesta.',
        'event_date' => '2026-08-15',
        'time' => 'All Day',
        'venue' => 'Poblacion, Baao',
        'tone' => EventTone::Orange,
        'is_active' => true,
    ], $overrides));
}

test('public home page loads locations and review data from backend', function () {
    $location = createPublicHomeLocation();
    $appUser = User::factory()->appUser()->create(['name' => 'Maria Santos']);

    LocationRating::query()->create([
        'user_id' => $appUser->id,
        'location_id' => $location->id,
        'rating' => 5,
        'comment' => 'Very peaceful and serene.',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->has('locations', 1)
            ->where('locations.0.name', 'Baao Lake View Park')
            ->where('locations.0.category', 'nature')
            ->where('locations.0.latitude', 13.4481)
            ->has('categories', 2)
            ->where('categories.0.key', 'all')
            ->where('reviewSummary.total_reviews', 1)
            ->where('reviewSummary.average_rating', 5)
            ->has('recentReviews', 1)
            ->where('recentReviews.0.name', 'Maria Santos')
            ->where('recentReviews.0.text', 'Very peaceful and serene.'));
});

test('public home page renders with empty location list', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->has('locations', 0)
            ->where('categories.0.key', 'all')
            ->where('reviewSummary.total_reviews', 0)
            ->has('recentReviews', 0)
            ->has('events', 0));
});

test('public home page loads active events from backend', function () {
    createPublicHomeEvent(['title' => 'Published Event']);

    Event::query()->create([
        'title' => 'Draft Event',
        'type' => EventType::Culture,
        'description' => 'Hidden from public home page.',
        'event_date' => '2026-09-01',
        'time' => 'All Day',
        'venue' => 'Baao',
        'tone' => EventTone::Teal,
        'is_active' => false,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->has('events', 1)
            ->where('events.0.title', 'Published Event')
            ->where('events.0.type_label', 'Festival')
            ->where('events.0.tone_class', 'event-orange')
            ->where('events.0.date', 'August 15, 2026'));
});
