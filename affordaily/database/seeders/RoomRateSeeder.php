<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RoomRate;

class RoomRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create room rates for both bed types
        RoomRate::create([
            'bed_type' => 'A',
            'rate_per_night' => 50.00,
            'is_active' => true,
        ]);

        RoomRate::create([
            'bed_type' => 'B',
            'rate_per_night' => 75.00,
            'is_active' => true,
        ]);
    }
}
