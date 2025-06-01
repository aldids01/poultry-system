<?php

namespace App\Observers;

use App\Models\InventoryTransactions;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        if ($product->product_type === 'finished_good' && $product->quantity_on_hand > 0) {
            try {
                // Creates an InventoryTransaction. The InventoryTransactionObserver will
                // then update the product's quantity_on_hand based on this transaction.
                InventoryTransactions::create([
                    'product_id' => $product->id,
                    'factory_id' => $product->factory_id ?? Filament::getTenant()?->id,
                    'transaction_type' => 'ASSEMBLY_PRODUCTION',
                    'quantity_changed' => $product->quantity_on_hand,
                    'transaction_date' => now(),
                    'source_destination' => 'Finished Goods Production',
                    'reference_number' => "PROD-{$product->id}",
                    'user_id' => Auth::id(),
                    'notes' => "Initial stock recorded for new finished good: {$product->name}.",
                ]);

                // IMPORTANT: If you want quantity_on_hand to ONLY ever be derived from transactions,
                // you would reset $product->quantity_on_hand = 0; before saving the product initially,
                // or ensure your Filament form doesn't allow direct input for finished goods' initial stock.
                // For now, we assume the form sets it, and the transaction observer will add to it.

            } catch (\Exception $e) {
                Notification::make()
                    ->title('Inventory Error: Cannot reduce stock below zero.')
                    ->body("Error creating initial inventory transaction for finished good '{$product->name}': " . $e->getMessage())
                    ->danger()
                    ->send();
                Log::error("Error creating initial inventory transaction for finished good '{$product->name}': " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
