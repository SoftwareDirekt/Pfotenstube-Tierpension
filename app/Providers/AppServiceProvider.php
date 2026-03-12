<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use App\Models\Customer;
use App\Models\Dog;
use App\Models\Reservation;

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
        // Validate VAT configuration
        $this->validateVATConfiguration();
        
        if (
            !$this->app->runningInConsole() &&
            Schema::hasTable('customers') &&
            Schema::hasTable('dogs') &&
            Schema::hasTable('reservations') &&
            Schema::hasColumn('dogs', 'deleted_at') 
        ) {
            $total_customers = Customer::count();
            $total_dogs = Dog::count();
            $total_reservations = Reservation::where('status', 3)->count();
    
            View::share('total_customers', $total_customers);
            View::share('total_dogs', $total_dogs);
            View::share('total_reservations_count', $total_reservations);
        }
    
        Paginator::useBootstrap();
    }
    
    /**
     * Validate VAT configuration to ensure it's properly set
     */
    private function validateVATConfiguration(): void
    {
        $vatMode = config('app.vat_calculation_mode');
        
        if ($vatMode && !in_array($vatMode, ['inclusive', 'exclusive'])) {
            throw new \RuntimeException(
                "Ungültiger MwSt-Berechnungsmodus: '{$vatMode}'. Muss 'inclusive' oder 'exclusive' sein."
            );
        }
    }
}
