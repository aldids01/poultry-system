<?php

namespace App\Providers;

use App\Models\InventoryTransactions;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Observers\InventoryTransactionObserver;
use App\Observers\PaymentObserver;
use App\Observers\ProductObserver;
use App\Observers\SaleObserver;
use Illuminate\Support\ServiceProvider;

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
        InventoryTransactions::observe(InventoryTransactionObserver::class);
        Payment::observe(PaymentObserver::class);
        Sale::observe(SaleObserver::class);
        Product::observe(ProductObserver::class);
    }
}
