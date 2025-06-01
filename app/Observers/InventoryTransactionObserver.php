<?php

namespace App\Observers;

use App\Models\InventoryTransactions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class InventoryTransactionObserver
{
    /**
     * Handle the InventoryTransactions "created" event.
     */
    public function created(InventoryTransactions $inventoryTransactions): void
    {
        $this->updateProductQuantity($inventoryTransactions, $inventoryTransactions->quantity_changed);
    }

    /**
     * Handle the InventoryTransactions "updated" event.
     */
    public function updated(InventoryTransactions $inventoryTransactions): void
    {
        // Calculate the difference between old and new quantity_changed
        $oldQuantity = $inventoryTransactions->getOriginal('quantity_changed');
        $newQuantity = $inventoryTransactions->quantity_changed;
        $quantityDifference = $newQuantity - $oldQuantity;

        $this->updateProductQuantity($inventoryTransactions, $quantityDifference);
    }

    /**
     * Handle the InventoryTransactions "deleted" event.
     */
    public function deleted(InventoryTransactions $inventoryTransactions): void
    {
        $this->updateProductQuantity($inventoryTransactions, -$inventoryTransactions->quantity_changed);
    }

    /**
     * Handle the InventoryTransactions "restored" event.
     */
    public function restored(InventoryTransactions $inventoryTransactions): void
    {
        $this->updateProductQuantity($inventoryTransactions, $inventoryTransactions->quantity_changed);
    }

    /**
     * Handle the InventoryTransactions "force deleted" event.
     */
    public function forceDeleted(InventoryTransactions $inventoryTransactions): void
    {
        $this->updateProductQuantity($inventoryTransactions, -$inventoryTransactions->quantity_changed);
    }

    protected function updateProductQuantity(InventoryTransactions $inventoryTransaction, int $changeAmount): void
    {
        $product = $inventoryTransaction->product;

        if ($product && $inventoryTransaction->transaction_type != 'ASSEMBLY_PRODUCTION') {
            DB::transaction(function () use ($product, $changeAmount, $inventoryTransaction) {
                // Ensure we handle potential race conditions by re-fetching the latest quantity
                $product->lockForUpdate(); // Locks the row for the duration of the transaction

                $currentQuantity = $product->quantity_on_hand;
                $newQuantity = $currentQuantity + $changeAmount;

                // Basic validation for 'OUT' transactions during update/delete
                // This prevents negative stock if an 'OUT' transaction's quantity is increased
                // or if an 'IN' transaction is deleted that would make stock negative.
                if ($newQuantity < 0 && $inventoryTransaction->transaction_type !== 'ADJUSTMENT' && $inventoryTransaction->transaction_type !== 'OUT') {
                    // This scenario should ideally be caught by frontend validation or 'updating' event
                    // For robustness, you might throw an exception here
                    Notification::make()
                        ->title('Inventory Error: Cannot reduce stock below zero.')
                        ->danger()
                        ->send();
                    throw new \Exception('Cannot reduce stock below zero for product ' . $product->product_name);
                }


                $product->quantity_on_hand = $newQuantity;
                $product->save();
            });
        }
    }
}
