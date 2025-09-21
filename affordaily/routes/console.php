<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automated checkout processing
Schedule::command('affordaily:process-checkouts --time=now')
    ->dailyAt('00:00')  // Midnight - mark as pending checkout
    ->withoutOverlapping();

Schedule::command('affordaily:process-checkouts --time=now')
    ->dailyAt('12:00')  // Noon - auto-checkout overdue bookings
    ->withoutOverlapping();
