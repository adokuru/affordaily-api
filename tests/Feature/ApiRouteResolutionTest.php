<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiRouteResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_collection_routes_are_not_shadowed_by_resource_ids(): void
    {
        $this->actingUser();

        Room::create([
            'room_number' => '101',
            'bed_type' => 'A',
            'is_available' => true,
        ]);

        $this->getJson('/api/v1/rooms/available')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_available', 1);

        $this->getJson('/api/v1/bookings/search')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/bookings/active')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/payments/ledger')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/guests/search/phone')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }
}
