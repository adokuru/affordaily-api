<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 400 rooms - 200 Type A and 200 Type B
        for ($i = 1; $i <= 400; $i++) {
            $bedType = $i <= 200 ? 'A' : 'B';
            $roomNumber = $bedType . str_pad($i <= 200 ? $i : $i - 200, 3, '0', STR_PAD_LEFT);
            
            Room::create([
                'room_number' => $roomNumber,
                'bed_type' => $bedType,
                'is_available' => true,
                'description' => "Room {$roomNumber} with Bed Space {$bedType}",
            ]);
        }
    }
}
