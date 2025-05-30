<?php

namespace App\Observers;

use App\Models\InventoryTransactions;
use App\Models\Sale;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleObserver
{
    /**
     * Handle the Sale "deleted" event (soft delete).
     * Credits inventory back when a sale is soft-deleted.
     */
    public function deleted(Sale $sale): void
    {
        // Check if the model uses SoftDeletes. If not, this event won't fire.
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($sale))) {
            $this->processSaleInventory($sale, 'RETURN', 'Stock credited due to soft-deletion of Sale #');
        }
    }

    /**
     * Handle the Sale "restored" event.
     * Re-debits inventory when a soft-deleted sale is restored.
     */
    public function restored(Sale $sale): void
    {
        $this->processSaleInventory($sale, 'OUT', 'Stock re-debited due to restoration of Sale #');
    }

    /**
     * Handle the Sale "force deleted" event (permanent delete).
     * Credits inventory back when a sale is permanently deleted.
     */
    public function forceDeleted(Sale $sale): void
    {
        $this->processSaleInventory($sale, 'RETURN', 'Stock credited due to permanent deletion of Sale #');
    }

    /**
     * Helper method to process inventory changes for a sale.
     *
     * @param Sale $sale The sale model.
     * @param string $transactionType The type of inventory transaction ('OUT', 'RETURN').
     * @param string $notesPrefix A prefix for the transaction notes.
     */
    protected function processSaleInventory(Sale $sale, string $transactionType, string $notesPrefix = 'Stock debited for Sale #'): void
    {
        DB::transaction(function () use ($sale, $transactionType, $notesPrefix) {
            // Ensure sale items are loaded from the database.
            // This is crucial for 'deleted', 'restored', 'forceDeleted' events.
            $sale->load('saleItems');

            if ($sale->saleItems->isEmpty()) {
                // This warning should now only appear if a sale truly has no items,
                // or if there's an unexpected issue during delete/restore.
                Notification::make()
                    ->title('Warning: Sale #'.$sale->id.' has no items for inventory processing during ' . $transactionType . ' event.')
                    ->warning()
                    ->send();
                return;
            }

            foreach ($sale->saleItems as $saleItem) {
                // Determine the quantity change based on transaction type
                $quantityChange = ($transactionType === 'OUT') ? -$saleItem->quantity : $saleItem->quantity;

                InventoryTransactions::create([ // Corrected: Use singular 'InventoryTransaction'
                    'product_id' => $saleItem->product_id,
                    'transaction_type' => $transactionType,
                    'quantity_changed' => $quantityChange,
                    'transaction_date' => now(),
                    'source_destination' => 'Sale #'.$sale->id . ' (Customer Order)',
                    'reference_number' => $sale->id,
                    'user_id' => Auth::id(),
                    'notes' => $notesPrefix . $sale->id,
                    'factory_id' => $sale->factory_id, // Uncomment if 'factory_id' exists on Sale model
                ]);
            }
        });
    }
}
