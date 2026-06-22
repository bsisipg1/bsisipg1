<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('admin users page shows registered users', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Admin User',
        'email' => 'admin@baaotourism.test',
    ]);

    $appUser = User::factory()->appUser()->create([
        'name' => 'Mobile User',
        'email' => 'mobile@baaotourism.test',
        'google_id' => 'google-123',
        'email_verified_at' => now(),
    ]);

    actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Users')
            ->has('users', 2)
            ->where('users', function ($users): bool {
                $byEmail = collect($users)->keyBy('email');

                return $byEmail->has('mobile@baaotourism.test')
                    && $byEmail->has('admin@baaotourism.test')
                    && $byEmail['mobile@baaotourism.test']['role_label'] === 'App User'
                    && $byEmail['mobile@baaotourism.test']['uses_google'] === true
                    && $byEmail['admin@baaotourism.test']['role_label'] === 'Administrator';
            }));
});

test('guest cannot access admin users page', function () {
    $this->get('/admin/users')->assertRedirect('/login');
});
