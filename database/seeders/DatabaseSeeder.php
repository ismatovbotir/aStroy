<?php

namespace Database\Seeders;

use App\Models\Object as ObjectLocation;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();


        ObjectLocation::create([
            'name' => 'Test Object',
            'address' => '123 Test St',
            'description' => 'This is a test object for seeding purposes.',
        ]);
    }
}
