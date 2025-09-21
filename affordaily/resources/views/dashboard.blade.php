<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affordaily Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-blue-600 text-white p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold">Affordaily Dashboard</h1>
                <div class="space-x-4">
                    <a href="{{ route('dashboard') }}" class="hover:text-blue-200">Dashboard</a>
                    <a href="{{ route('dashboard.rooms') }}" class="hover:text-blue-200">Rooms</a>
                    <a href="{{ route('dashboard.roll-call') }}" class="hover:text-blue-200">Roll Call</a>
                    <a href="{{ route('dashboard.payments') }}" class="hover:text-blue-200">Payments</a>
                    <a href="{{ route('dashboard.settings') }}" class="hover:text-blue-200">Settings</a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container mx-auto p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-700">Total Rooms</h3>
                    <p class="text-3xl font-bold text-blue-600">{{ $stats['total_rooms'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-700">Occupied Rooms</h3>
                    <p class="text-3xl font-bold text-red-600">{{ $stats['occupied_rooms'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-700">Available Rooms</h3>
                    <p class="text-3xl font-bold text-green-600">{{ $stats['available_rooms'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-700">Active Bookings</h3>
                    <p class="text-3xl font-bold text-purple-600">{{ $stats['active_bookings'] }}</p>
                </div>
            </div>

            <!-- Occupancy Rate -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Occupancy Rate</h3>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-blue-600 h-4 rounded-full" style="width: {{ $occupancyStats['occupancy_rate'] }}%"></div>
                </div>
                <p class="text-sm text-gray-600 mt-2">{{ $occupancyStats['occupancy_rate'] }}% occupied</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Bookings -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Recent Bookings</h3>
                    <div class="space-y-4">
                        @forelse($recentBookings as $booking)
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <p class="font-medium">{{ $booking->guest_name }}</p>
                                <p class="text-sm text-gray-600">Room {{ $booking->room->room_number }} ({{ $booking->room->bed_type }})</p>
                                <p class="text-sm text-gray-500">{{ $booking->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        @empty
                            <p class="text-gray-500">No recent bookings</p>
                        @endforelse
                    </div>
                </div>

                <!-- Pending Checkouts -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Pending Checkouts</h3>
                    <div class="space-y-4">
                        @forelse($pendingCheckouts as $booking)
                            <div class="border-l-4 border-orange-500 pl-4 py-2">
                                <p class="font-medium">{{ $booking->guest_name }}</p>
                                <p class="text-sm text-gray-600">Room {{ $booking->room->room_number }}</p>
                                <p class="text-sm text-gray-500">Due: {{ $booking->scheduled_checkout_time->format('M d, Y H:i') }}</p>
                            </div>
                        @empty
                            <p class="text-gray-500">No pending checkouts</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('dashboard.rooms') }}" class="bg-blue-500 text-white p-4 rounded-lg text-center hover:bg-blue-600 transition">
                        <h4 class="font-medium">View Rooms</h4>
                        <p class="text-sm opacity-90">Manage room status</p>
                    </a>
                    <a href="{{ route('dashboard.roll-call') }}" class="bg-green-500 text-white p-4 rounded-lg text-center hover:bg-green-600 transition">
                        <h4 class="font-medium">Roll Call</h4>
                        <p class="text-sm opacity-90">Current occupants</p>
                    </a>
                    <a href="{{ route('dashboard.payments') }}" class="bg-purple-500 text-white p-4 rounded-lg text-center hover:bg-purple-600 transition">
                        <h4 class="font-medium">Payments</h4>
                        <p class="text-sm opacity-90">Payment ledger</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>