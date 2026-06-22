<?php

use App\Enums\LocationCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('location create page includes all location categories', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get('/admin/locations/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/locations/Create')
            ->has('categories', count(LocationCategory::cases()))
            ->where('categories', LocationCategory::options()));
});
