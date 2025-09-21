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
        // Create 20 rooms - 10 Type A and 10 Type B
        for ($i = 1; $i <= 20; $i++) {
            $bedType = $i <= 10 ? 'A' : 'B';
            $roomNumber = $bedType . str_pad($i % 10 === 0 ? 10 : $i % 10, 2, '0', STR_PAD_LEFT);
            
            Room::create([
                'room_number' => $roomNumber,
                'bed_type' => $bedType,
                'is_available' => true,
                'description' => "Room {$roomNumber} with Bed Type {$bedType}",
            ]);
        }
    }
}
