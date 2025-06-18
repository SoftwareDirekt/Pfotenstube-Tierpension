<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Customer;
use App\Models\Dog;
use App\Models\Reservation;
use View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $total_customers = Customer::get()->count();
        $total_dogs = Dog::get()->count();
        $total_reservations = Reservation::where('status', 3)->get()->count();
        View::share('total_customers', $total_customers);
        View::share('total_dogs', $total_dogs);
        View::share('total_reservations_count', $total_reservations);
        Paginator::useBootstrap();
    }
}
