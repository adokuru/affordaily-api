<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roll Call - Affordaily Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Roll Call</h1>
            <a href="{{ route('dashboard') }}" class="text-blue-600">Dashboard</a>
        </div>
        <div class="grid gap-4">
            @forelse($activeBookings as $booking)
                <div class="bg-white rounded shadow p-4">
                    <div class="font-semibold">{{ $booking->guest_name }} - Room {{ $booking->room?->room_number }}</div>
                    <div class="text-sm text-gray-600">Checkout due {{ $booking->scheduled_checkout_time?->format('M d, Y H:i') }}</div>
                    <div class="text-sm text-gray-600">Active visitors: {{ $booking->visitorPasses->count() }}</div>
                </div>
            @empty
                <div class="bg-white rounded shadow p-4">No active bookings.</div>
            @endforelse
        </div>
    </div>
</body>
</html>
