<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Affordaily Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Payments</h1>
            <a href="{{ route('dashboard') }}" class="text-blue-600">Dashboard</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            @foreach($summary as $label => $amount)
                <div class="bg-white rounded shadow p-4">
                    <div class="text-xs uppercase text-gray-500">{{ str_replace('_', ' ', $label) }}</div>
                    <div class="text-xl font-semibold">{{ number_format($amount, 2) }}</div>
                </div>
            @endforeach
        </div>
        <div class="overflow-x-auto bg-white shadow rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="p-3">Date</th>
                        <th class="p-3">Guest</th>
                        <th class="p-3">Method</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Confirmed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr class="border-t">
                            <td class="p-3">{{ $payment->created_at?->format('M d, Y H:i') }}</td>
                            <td class="p-3">{{ $payment->booking?->guest_name ?? '-' }}</td>
                            <td class="p-3">{{ $payment->payment_method }}</td>
                            <td class="p-3">{{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="p-3">{{ $payment->is_confirmed ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-3" colspan="5">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $payments->links() }}</div>
    </div>
</body>
</html>
