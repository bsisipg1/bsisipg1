<?php

use App\Enums\EventTone;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('admin events page lists registered events', function () {
    $admin = User::factory()->admin()->create();

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

    actingAs($admin)
        ->get('/admin/events')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/Index')
            ->has('events', 1)
            ->where('events.0.title', 'Baao Town Fiesta')
            ->where('events.0.type_label', 'Festival')
            ->where('events.0.is_active', true));
});

test('guest cannot access admin events page', function () {
    $this->get('/admin/events')->assertRedirect('/login');
});
