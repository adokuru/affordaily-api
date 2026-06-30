<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        return $this->actingUserWithRole('admin');
    }

    private function actingUserWithRole(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role).' User',
            'email' => 'test-'.$role.'-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_api_does_not_register_unimplemented_resource_actions(): void
    {
        $routes = collect(Route::getRoutes())->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri());

        $this->assertFalse($routes->contains('PUT|PATCH api/v1/bookings/{booking}'));
        $this->assertFalse($routes->contains('DELETE api/v1/bookings/{booking}'));
        $this->assertFalse($routes->contains('PUT|PATCH api/v1/visitor-passes/{visitor_pass}'));
        $this->assertFalse($routes->contains('DELETE api/v1/visitor-passes/{visitor_pass}'));
        $this->assertFalse($routes->contains('DELETE api/v1/guests/{guest}'));
    }

    public function test_pending_checkout_booking_can_be_checked_out_manually(): void
    {
        $user = $this->actingUser();
        $booking = $this->booking($user, 'pending_checkout');

        $this->postJson("/api/v1/bookings/{$booking->id}/checkout", [
            'key_returned' => true,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertTrue($booking->room->fresh()->is_available);
    }

    public function test_noon_checkout_processes_bookings_due_exactly_at_noon(): void
    {
        $user = $this->actingUser();
        $booking = $this->booking($user, 'pending_checkout');

        $this->artisan('affordaily:process-checkouts', [
            '--time' => '2026-06-29 12:00:00',
        ])->assertExitCode(0);

        $booking->refresh();

        $this->assertSame('auto_checkout', $booking->status);
        $this->assertTrue($booking->room->fresh()->is_available);
    }

    public function test_available_room_cache_is_scoped_by_bed_type(): void
    {
        $this->actingUser();

        Room::create([
            'room_number' => '301',
            'bed_type' => 'A',
            'is_available' => true,
        ]);

        Room::create([
            'room_number' => '401',
            'bed_type' => 'B',
            'is_available' => true,
        ]);

        $this->getJson('/api/v1/rooms/available?bed_type=A')
            ->assertOk()
            ->assertJsonPath('data.total_available', 1)
            ->assertJsonPath('data.A.0.room_number', '301');

        $this->getJson('/api/v1/rooms/available?bed_type=B')
            ->assertOk()
            ->assertJsonPath('data.total_available', 1)
            ->assertJsonPath('data.B.0.room_number', '401');
    }

    public function test_payment_ledger_summary_covers_all_filtered_rows_not_only_current_page(): void
    {
        $user = $this->actingUser();
        $booking = $this->booking($user, 'active');

        foreach (range(1, 60) as $index) {
            Payment::create([
                'booking_id' => $booking->id,
                'payment_method' => $index <= 40 ? 'cash' : 'transfer',
                'amount' => 1,
                'payer_name' => 'Jane Doe',
                'is_confirmed' => $index <= 45,
                'confirmed_at' => $index <= 45 ? now() : null,
                'processed_by' => $user->id,
            ]);
        }

        $this->getJson('/api/v1/payments/ledger')
            ->assertOk()
            ->assertJsonPath('summary.total_amount', 60)
            ->assertJsonPath('summary.cash_total', 40)
            ->assertJsonPath('summary.transfer_total', 20)
            ->assertJsonPath('summary.confirmed_total', 45)
            ->assertJsonPath('summary.pending_total', 15)
            ->assertJsonPath('data.per_page', 50)
            ->assertJsonPath('data.total', 60);
    }

    public function test_payment_filters_are_validated_consistently(): void
    {
        $this->actingUser();

        $this->getJson('/api/v1/payments?is_confirmed=maybe')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_confirmed']);

        $this->getJson('/api/v1/payments/ledger?date_from=2026-06-30&date_to=2026-06-29')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to']);

        $this->getJson('/api/v1/dashboard/payments?payment_method=cheque')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_dashboard_payment_api_uses_same_filtered_summary_as_ledger(): void
    {
        $user = $this->actingUser();
        $booking = $this->booking($user, 'active');

        Payment::create([
            'booking_id' => $booking->id,
            'payment_method' => 'cash',
            'amount' => 30,
            'payer_name' => 'Jane Doe',
            'is_confirmed' => true,
            'confirmed_at' => now(),
            'processed_by' => $user->id,
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'payment_method' => 'transfer',
            'amount' => 70,
            'payer_name' => 'Jane Doe',
            'is_confirmed' => false,
            'processed_by' => $user->id,
        ]);

        $this->getJson('/api/v1/dashboard/payments?confirmed=false')
            ->assertOk()
            ->assertJsonPath('summary.total_amount', 70)
            ->assertJsonPath('summary.cash_total', 0)
            ->assertJsonPath('summary.transfer_total', 70)
            ->assertJsonPath('summary.confirmed_total', 0)
            ->assertJsonPath('summary.pending_total', 70);
    }

    public function test_failed_check_in_does_not_leak_exception_details_or_persist_partial_guest(): void
    {
        config(['app.debug' => false]);
        $this->actingUser();

        $this->postJson('/api/v1/bookings', [
            'guest_name' => 'No Room Guest',
            'guest_phone' => '+2348111111111',
            'number_of_nights' => 1,
            'payment_method' => 'cash',
            'payer_name' => 'No Room Guest',
        ])
            ->assertStatus(500)
            ->assertJsonPath('message', 'Error creating booking')
            ->assertJsonMissing(['message' => 'No available rooms found']);

        $this->assertDatabaseMissing('guests', [
            'phone' => '+2348111111111',
        ]);
    }

    public function test_dashboard_requires_basic_auth_in_production(): void
    {
        config([
            'app.env' => 'production',
            'dashboard.username' => 'manager',
            'dashboard.password' => 'secret',
        ]);

        $this->get('/dashboard')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic realm="Affordaily Dashboard"');
    }

    public function test_dashboard_fails_closed_in_production_when_credentials_are_missing(): void
    {
        config([
            'app.env' => 'production',
            'dashboard.username' => null,
            'dashboard.password' => null,
        ]);

        $this->get('/dashboard')->assertForbidden();
    }

    public function test_dashboard_pages_render_for_authenticated_production_user(): void
    {
        config([
            'app.env' => 'production',
            'dashboard.username' => 'manager',
            'dashboard.password' => 'secret',
        ]);

        foreach (['/dashboard', '/dashboard/rooms', '/dashboard/roll-call', '/dashboard/payments', '/dashboard/settings'] as $path) {
            $this->withHeaders([
                'Authorization' => 'Basic '.base64_encode('manager:secret'),
            ])->get($path)->assertOk();
        }
    }

    public function test_default_demo_api_credentials_are_blocked_in_production(): void
    {
        config(['app.env' => 'production']);

        User::create([
            'name' => 'Receptionist User',
            'email' => 'receptionist@affordaily.com',
            'password' => Hash::make('receptionist123'),
            'role' => 'receptionist',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'receptionist@affordaily.com',
            'password' => 'receptionist123',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Default demo credentials are disabled in production');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_payment_create_update_and_delete_keep_booking_balance_consistent(): void
    {
        $user = $this->actingUser();
        $booking = $this->booking($user, 'active');
        $booking->update(['amount_paid' => 0]);

        $this->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'payment_method' => 'cash',
            'amount' => 30,
            'payer_name' => 'Jane Doe',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $payment = Payment::firstOrFail();
        $this->assertSame('30.00', $booking->fresh()->amount_paid);

        $this->putJson("/api/v1/payments/{$payment->id}", [
            'amount' => 110,
        ])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Payment amount exceeds remaining balance');

        $this->assertSame('30.00', $booking->fresh()->amount_paid);
        $this->assertSame('30.00', $payment->fresh()->amount);

        $this->putJson("/api/v1/payments/{$payment->id}", [
            'amount' => 60,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('60.00', $booking->fresh()->amount_paid);

        $this->deleteJson("/api/v1/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('0.00', $booking->fresh()->amount_paid);
    }

    public function test_room_rate_updates_replace_active_rates_atomically(): void
    {
        $this->actingUser();

        RoomRate::create(['bed_type' => 'A', 'rate_per_night' => 100, 'is_active' => true]);
        RoomRate::create(['bed_type' => 'B', 'rate_per_night' => 150, 'is_active' => true]);

        $this->postJson('/api/v1/rooms/rates', [
            'rates' => [
                'A' => 120,
                'B' => 180,
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(2, RoomRate::active()->count());
        $this->assertDatabaseHas('room_rates', [
            'bed_type' => 'A',
            'rate_per_night' => 120,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('room_rates', [
            'bed_type' => 'B',
            'rate_per_night' => 180,
            'is_active' => true,
        ]);
        $this->assertSame(2, RoomRate::where('is_active', false)->count());
    }

    public function test_receptionist_cannot_access_admin_only_api_mutations(): void
    {
        $user = $this->actingUserWithRole('receptionist');
        $booking = $this->booking($user, 'active');
        $booking->update(['amount_paid' => 0]);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'payment_method' => 'cash',
            'amount' => 10,
            'payer_name' => 'Jane Doe',
            'processed_by' => $user->id,
        ]);

        $this->postJson('/api/v1/rooms/rates', [
            'rates' => ['A' => 100, 'B' => 150],
        ])->assertForbidden();

        $this->postJson('/api/v1/rooms', [
            'room_number' => '999',
            'bed_type' => 'A',
        ])->assertForbidden();

        $this->putJson("/api/v1/rooms/{$booking->room_id}", [
            'description' => 'Updated by receptionist',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/rooms/{$booking->room_id}")
            ->assertForbidden();

        $this->postJson("/api/v1/payments/{$payment->id}/confirm")
            ->assertForbidden();

        $this->putJson("/api/v1/payments/{$payment->id}", [
            'amount' => 20,
        ])->assertForbidden();

        $this->deleteJson("/api/v1/payments/{$payment->id}")
            ->assertForbidden();
    }

    public function test_receptionist_can_still_perform_pos_safe_operations(): void
    {
        $user = $this->actingUserWithRole('receptionist');
        $booking = $this->booking($user, 'active');
        $booking->update([
            'total_amount' => 100,
            'amount_paid' => 0,
        ]);

        $this->getJson('/api/v1/rooms')
            ->assertOk();

        $this->getJson('/api/v1/payments')
            ->assertOk();

        $this->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'payment_method' => 'cash',
            'amount' => 25,
            'payer_name' => 'Jane Doe',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertSame('25.00', $booking->fresh()->amount_paid);
    }

    private function booking(User $user, string $status): Booking
    {
        $room = Room::create([
            'room_number' => '201',
            'bed_type' => 'A',
            'is_available' => false,
        ]);

        $guest = Guest::create([
            'name' => 'Jane Doe',
            'phone' => '+2348000000000',
        ]);

        return Booking::create([
            'booking_reference' => 'REFTEST'.uniqid(),
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'guest_name' => $guest->name,
            'guest_phone' => $guest->phone,
            'check_in_time' => '2026-06-28 14:00:00',
            'check_out_time' => '2026-06-29 12:00:00',
            'scheduled_checkout_time' => '2026-06-29 12:00:00',
            'number_of_nights' => 1,
            'status' => $status,
            'total_amount' => 100,
            'amount_paid' => 100,
            'created_by' => $user->id,
        ]);
    }
}
