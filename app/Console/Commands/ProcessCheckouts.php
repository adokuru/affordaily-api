<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class ProcessCheckouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'affordaily:process-checkouts {--time=now}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic checkouts for overdue bookings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $time = $this->option('time') === 'now' ? now() : Carbon::parse($this->option('time'));
        
        $this->info("Processing checkouts for time: {$time->format('Y-m-d H:i:s')}");

        // Midnight operation: Mark bookings with scheduled checkout as pending checkout
        if ($time->format('H:i') === '00:00') {
            $this->processMidnightCheckouts($time);
        }

        // Noon operation: Auto-checkout overdue bookings
        if ($time->format('H:i') === '12:00') {
            $this->processNoonCheckouts($time);
        }

        $this->info('Checkout processing completed.');
    }

    /**
     * Process midnight checkouts - mark as pending checkout
     */
    private function processMidnightCheckouts(Carbon $time)
    {
        $this->info('Processing midnight checkouts...');

        $bookings = Booking::active()
            ->whereDate('scheduled_checkout_time', $time->toDateString())
            ->where('status', 'active')
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            $booking->update(['status' => 'pending_checkout']);
            $count++;
        }

        $this->info("Marked {$count} bookings as pending checkout.");
    }

    /**
     * Process noon checkouts - auto-checkout overdue bookings
     */
    private function processNoonCheckouts(Carbon $time)
    {
        $this->info('Processing noon auto-checkouts...');

        $bookings = Booking::pendingCheckout()
            ->where('scheduled_checkout_time', '<', $time)
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            // Auto-checkout the booking
            $booking->update([
                'status' => 'auto_checkout',
                'check_out_time' => $time,
                'auto_checkout_time' => $time,
                'auto_checkout_reason' => 'Automatic checkout at noon - key not returned',
            ]);

            // Make room available again
            $booking->room->update(['is_available' => true]);

            // Deactivate all visitor passes
            $booking->visitorPasses()->update([
                'is_active' => false,
                'check_out_time' => $time,
            ]);

            $count++;
        }

        $this->info("Auto-checked out {$count} bookings.");
    }
}
