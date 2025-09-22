<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Actions\Guest\FindOrCreateGuestAction;
use App\Actions\Guest\UpdateGuestStatsAction;
use App\Actions\Guest\SearchGuestByPhoneAction;
use App\Actions\Booking\CreateBookingAction;
use App\Actions\Booking\CheckoutBookingAction;
use App\Actions\Booking\ExtendBookingAction;
use App\Actions\Room\GetAvailableRoomsAction;
use App\Actions\Room\GetRoomOccupancyStatsAction;
use App\Actions\Payment\CreatePaymentAction;
use App\Actions\Payment\ConfirmPaymentAction;
use App\Actions\VisitorPass\IssueVisitorPassAction;
use App\Actions\VisitorPass\CheckoutVisitorAction;

class ActionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Guest Actions
        $this->app->singleton(FindOrCreateGuestAction::class);
        $this->app->singleton(UpdateGuestStatsAction::class);
        $this->app->singleton(SearchGuestByPhoneAction::class);

        // Booking Actions
        $this->app->singleton(CreateBookingAction::class);
        $this->app->singleton(CheckoutBookingAction::class);
        $this->app->singleton(ExtendBookingAction::class);

        // Room Actions
        $this->app->singleton(GetAvailableRoomsAction::class);
        $this->app->singleton(GetRoomOccupancyStatsAction::class);

        // Payment Actions
        $this->app->singleton(CreatePaymentAction::class);
        $this->app->singleton(ConfirmPaymentAction::class);

        // Visitor Pass Actions
        $this->app->singleton(IssueVisitorPassAction::class);
        $this->app->singleton(CheckoutVisitorAction::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
