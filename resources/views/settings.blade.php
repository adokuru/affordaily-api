<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Affordaily Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Settings</h1>
            <a href="{{ route('dashboard') }}" class="text-blue-600">Dashboard</a>
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="font-semibold mb-3">Active Room Rates</h2>
                @forelse($roomRates as $rate)
                    <div class="flex justify-between border-t py-2">
                        <span>Type {{ $rate->bed_type }}</span>
                        <span>{{ number_format((float) $rate->rate_per_night, 2) }}</span>
                    </div>
                @empty
                    <p class="text-gray-500">No active rates configured.</p>
                @endforelse
            </div>
            <div class="bg-white rounded shadow p-4">
                <h2 class="font-semibold mb-3">Room Count</h2>
                <p class="text-3xl font-bold">{{ $rooms->count() }}</p>
            </div>
        </div>
    </div>
</body>
</html>
