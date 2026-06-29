<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Affordaily Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Rooms</h1>
            <a href="{{ route('dashboard') }}" class="text-blue-600">Dashboard</a>
        </div>
        <div class="overflow-x-auto bg-white shadow rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="p-3">Room</th>
                        <th class="p-3">Bed Type</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Guest</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $room)
                        <tr class="border-t">
                            <td class="p-3 font-medium">{{ $room->room_number }}</td>
                            <td class="p-3">{{ $room->bed_type }}</td>
                            <td class="p-3">{{ $room->activeBooking ? 'Occupied' : 'Available' }}</td>
                            <td class="p-3">{{ $room->activeBooking?->guest_name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-3" colspan="4">No rooms configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
