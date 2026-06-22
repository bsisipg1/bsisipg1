<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.google.client_id' => 'test-google-client-id']);
});

test('google login saves profile photo from google account', function () {
    Storage::fake('public');

    Http::fake([
        'oauth2.googleapis.com/tokeninfo*' => Http::response([
            'aud' => 'test-google-client-id',
            'sub' => 'google-user-123',
            'email' => 'google@example.com',
            'name' => 'Google User',
            'picture' => 'https://lh3.googleusercontent.com/a/photo.jpg',
        ]),
        'lh3.googleusercontent.com/*' => Http::response('fake-image-bytes', 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    postJson('/api/app/auth/google', [
        'id_token' => 'valid-token',
        'device_name' => 'Test Device',
    ])
        ->assertOk()
        ->assertJsonPath('user.email', 'google@example.com')
        ->assertJsonPath('user.profile_photo_url', fn ($url) => $url !== null);

    $user = User::query()->where('email', 'google@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->profile_photo)->not->toBeNull();
    expect(Storage::disk('public')->exists($user->profile_photo))->toBeTrue();
});

test('google login does not replace an existing profile photo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('users/profile-photos/existing.jpg', 'existing');

    $user = User::factory()->appUser()->create([
        'email' => 'google@example.com',
        'google_id' => 'google-user-123',
        'profile_photo' => 'users/profile-photos/existing.jpg',
    ]);

    Http::fake([
        'oauth2.googleapis.com/tokeninfo*' => Http::response([
            'aud' => 'test-google-client-id',
            'sub' => 'google-user-123',
            'email' => 'google@example.com',
            'name' => 'Google User',
            'picture' => 'https://lh3.googleusercontent.com/a/photo.jpg',
        ]),
        'lh3.googleusercontent.com/*' => Http::response('fake-image-bytes', 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    postJson('/api/app/auth/google', [
        'id_token' => 'valid-token',
        'device_name' => 'Test Device',
    ])->assertOk();

    expect($user->fresh()->profile_photo)->toBe('users/profile-photos/existing.jpg');
    expect(Storage::disk('public')->exists('users/profile-photos/existing.jpg'))->toBeTrue();
});
