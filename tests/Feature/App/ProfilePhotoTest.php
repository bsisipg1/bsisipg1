<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

test('profile includes profile photo url', function () {
    Storage::fake('public');

    $user = User::factory()->appUser()->create([
        'profile_photo' => 'users/profile-photos/test.jpg',
    ]);

    Storage::disk('public')->put('users/profile-photos/test.jpg', 'photo');

    Sanctum::actingAs($user);

    getJson('/api/app/user')
        ->assertOk()
        ->assertJsonPath('user.profile_photo_url', asset('storage/users/profile-photos/test.jpg'));
});

test('app user can upload a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->appUser()->create();
    Sanctum::actingAs($user);

    post('/api/app/user', [
        'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('user.profile_photo_url', fn ($url) => $url !== null);

    $user->refresh();

    expect($user->profile_photo)->not->toBeNull();
    expect(Storage::disk('public')->exists($user->profile_photo))->toBeTrue();
});

test('uploading a new profile photo replaces the old one', function () {
    Storage::fake('public');

    $user = User::factory()->appUser()->create([
        'profile_photo' => 'users/profile-photos/old.jpg',
    ]);

    Storage::disk('public')->put('users/profile-photos/old.jpg', 'old');

    Sanctum::actingAs($user);

    post('/api/app/user', [
        'profile_photo' => UploadedFile::fake()->image('new.jpg'),
    ], ['Accept' => 'application/json'])->assertOk();

    expect(Storage::disk('public')->exists('users/profile-photos/old.jpg'))->toBeFalse();
    expect($user->fresh()->profile_photo)->not->toBe('users/profile-photos/old.jpg');
});

test('app user can update name with profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->appUser()->create(['name' => 'Old Name']);
    Sanctum::actingAs($user);

    post('/api/app/user', [
        'name' => 'New Name',
        'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('user.name', 'New Name');

    expect($user->fresh()->name)->toBe('New Name');
});

test('app user can remove profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->appUser()->create([
        'profile_photo' => 'users/profile-photos/remove-me.jpg',
    ]);

    Storage::disk('public')->put('users/profile-photos/remove-me.jpg', 'photo');

    Sanctum::actingAs($user);

    deleteJson('/api/app/user/profile-photo')
        ->assertOk()
        ->assertJsonPath('user.profile_photo_url', null);

    expect($user->fresh()->profile_photo)->toBeNull();
    expect(Storage::disk('public')->exists('users/profile-photos/remove-me.jpg'))->toBeFalse();
});

test('guest cannot update profile photo', function () {
    putJson('/api/app/user', [
        'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertUnauthorized();
});
