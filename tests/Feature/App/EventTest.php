<?php

use App\Enums\EventTone;
use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('events api returns only active events ordered by date', function () {
    Event::query()->create([
        'title' => 'Draft Event',
        'type' => EventType::Culture,
        'description' => 'Hidden from app users.',
        'event_date' => '2026-09-01',
        'time' => 'All Day',
        'venue' => 'Baao',
        'tone' => EventTone::Teal,
        'is_active' => false,
    ]);

    Event::query()->create([
        'title' => 'Baao Town Fiesta',
        'type' => EventType::Festival,
        'description' => 'Annual town fiesta.',
        'event_date' => '2026-08-15',
        'time' => 'All Day',
        'venue' => 'Poblacion, Baao',
        'tone' => EventTone::Orange,
        'is_active' => true,
    ]);

    Event::query()->create([
        'title' => 'Bicol Heritage Night',
        'type' => EventType::Culture,
        'description' => 'Cultural showcase.',
        'event_date' => '2026-08-08',
        'time' => '6:00 PM - 10:00 PM',
        'venue' => 'Old Heritage Hall, Baao',
        'tone' => EventTone::Teal,
        'is_active' => true,
    ]);

    $this->getJson('/api/app/events')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Bicol Heritage Night')
        ->assertJsonPath('data.0.type_label', 'Culture')
        ->assertJsonPath('data.0.date', 'August 8, 2026')
        ->assertJsonPath('data.0.tone_class', 'event-teal')
        ->assertJsonPath('data.1.title', 'Baao Town Fiesta');
});

test('events api show returns a published event', function () {
    $event = Event::query()->create([
        'title' => 'Lakeview Summer Fair',
        'type' => EventType::Community,
        'description' => 'Outdoor fair with local vendors.',
        'event_date' => '2026-08-22',
        'time' => '8:00 AM - 5:00 PM',
        'venue' => 'Baao Lake View Park',
        'tone' => EventTone::Gold,
        'is_active' => true,
    ]);

    $this->getJson("/api/app/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Lakeview Summer Fair')
        ->assertJsonPath('data.venue', 'Baao Lake View Park')
        ->assertJsonPath('data.tone', 'gold');
});

test('events api show hides inactive events', function () {
    $event = Event::query()->create([
        'title' => 'Private Planning Session',
        'type' => EventType::Other,
        'description' => 'Internal only.',
        'event_date' => '2026-08-01',
        'time' => null,
        'venue' => 'Municipal Hall',
        'tone' => EventTone::Blue,
        'is_active' => false,
    ]);

    $this->getJson("/api/app/events/{$event->id}")->assertNotFound();
});

test('events api is publicly accessible', function () {
    $this->getJson('/api/app/events')->assertOk();
});
