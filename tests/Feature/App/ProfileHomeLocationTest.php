<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

test('profile includes home location when set', function () {
    $user = User::factory()->appUser()->create([
        'home_location' => 'Nabua, Camarines Sur',
    ]);

    Sanctum::actingAs($user);

    getJson('/api/app/user')
        ->assertOk()
        ->assertJsonPath('user.home_location', 'Nabua, Camarines Sur');
});

test('guest cannot update home location', function () {
    putJson('/api/app/user/home-location', [
        'home_location' => 'Baao, Camarines Sur',
    ])->assertUnauthorized();
});

test('app user can set and clear home location', function () {
    $user = User::factory()->appUser()->create();
    Sanctum::actingAs($user);

    putJson('/api/app/user/home-location', [
        'home_location' => 'Iriga City, Camarines Sur',
    ])
        ->assertOk()
        ->assertJsonPath('user.home_location', 'Iriga City, Camarines Sur');

    expect($user->fresh()->home_location)->toBe('Iriga City, Camarines Sur');

    putJson('/api/app/user/home-location', [
        'home_location' => null,
    ])
        ->assertOk()
        ->assertJsonPath('user.home_location', null);

    expect($user->fresh()->home_location)->toBeNull();
});

test('home location validation rejects overly long values', function () {
    Sanctum::actingAs(User::factory()->appUser()->create());

    putJson('/api/app/user/home-location', [
        'home_location' => str_repeat('a', 256),
    ])->assertUnprocessable();
});
