<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@baaotourism.test',
            'password' => 'password',
        ]);

        User::factory()->appUser()->create([
            'name' => 'App User',
            'email' => 'appuser@baaotourism.test',
            'password' => 'password',
        ]);
    }
}
