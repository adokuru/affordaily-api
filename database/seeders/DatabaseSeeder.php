<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoomRateSeeder::class,
            RoomSeeder::class,
        ]);

        if ((bool) env('SEED_DEFAULT_USERS', false)) {
            $this->call(UserSeeder::class);
        }
    }
}
