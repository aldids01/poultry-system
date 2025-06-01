<?php

namespace App\Observers;

use App\Models\InventoryTransactions;
use App\Models\Payment;
use App\Models\Proforma;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->updateRelatedEntityStatusAndProcessInventory($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Only trigger if 'status' or 'amount' changed, as these affect payment completion.
        // Or if 'payment_method' changed (though not directly affecting status, could be relevant).
        if ($payment->isDirty('status') || $payment->isDirty('amount')) {
            $this->updateRelatedEntityStatusAndProcessInventory($payment);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     * When a payment is deleted, it might change the overall status of the related entity.
     */
    public function deleted(Payment $payment): void
    {
        $this->updateRelatedEntityStatusAndProcessInventory($payment);
    }

    /**
     * Handle the Payment "restored" event.
     * When a payment is restored, it might change the overall status of the related entity.
     */
    public function restored(Payment $payment): void
    {
        $this->updateRelatedEntityStatusAndProcessInventory($payment);
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        // This is typically for auditing or cleanup if a payment is permanently removed.
        // The 'deleted' method already reversed the status. No further inventory action needed here.
    }

    /**
     * Core logic to update the related entity's status based on its payments
     * and process inventory for Proforma.
     */
    protected function updateRelatedEntityStatusAndProcessInventory(Payment $payment): void
    {
        $relatedEntity = null;
        $totalField = '';
        $statusField = '';
        $mainStatusField = ''; // For PO's main status field (if applicable)

        // Determine which entity this payment belongs to
        if ($payment->sale_id) {
            $relatedEntity = $payment->sale;
            $totalField = 'total';
            $statusField = 'status'; // Assuming 'status' for Sale payment status
        } elseif ($payment->purchase_order_id) {
            $relatedEntity = $payment->purchaseOrder;
            $totalField = 'total';
            $statusField = 'payment'; // Assuming 'payment_status' for PurchaseOrder
            $mainStatusField = 'status'; // e.g., 'pending', 'approved', 'received'
        } elseif ($payment->proforma_id) {
            $relatedEntity = $payment->proforma;
            $totalField = 'total';
            $statusField = 'status'; // Assuming 'payment_status' for Proforma
            $mainStatusField = 'status'; // e.g., 'pending', 'approved', 'invoiced'
        }

        if (!$relatedEntity) {
            return; // No related entity found or handled
        }

        DB::transaction(function () use ($payment, $relatedEntity, $totalField, $statusField, $mainStatusField) {
            $relatedEntity->refresh()->lockForUpdate(); // Re-fetch and lock

            $totalPaid = $relatedEntity->payments()
                ->where('status', 'Completed')
                ->sum('amount');

            $hasProcessingPayments = $relatedEntity->payments()
                ->where('status', 'Processing')
                ->exists();

            $entityTotal = $relatedEntity->{$totalField};
            $oldPaymentStatus = $relatedEntity->{$statusField}; // Capture current status before update
            $newPaymentStatus = $oldPaymentStatus; // Default to current

            if ($totalPaid >= $entityTotal) {
                $newPaymentStatus = 'Completed';
            } elseif ($totalPaid > 0 || $hasProcessingPayments) {
                $newPaymentStatus = 'Processing';
            } else {
                $newPaymentStatus = 'Pending';
            }

            // Update the entity's payment status if it changed
            if ($oldPaymentStatus !== $newPaymentStatus) {
                $relatedEntity->{$statusField} = $newPaymentStatus;
                $relatedEntity->save(); // Save immediately to reflect status change
            }

            // --- Inventory Transaction Logic for Proforma ---
            if ($relatedEntity instanceof Proforma) {
                // IMPORTANT: Only trigger inventory deduction if the payment status
                // *just became* Completed AND it wasn't already completed before this payment.
                if ($newPaymentStatus === 'Completed' &&
                    $oldPaymentStatus !== 'Completed') {

                    // Load the proforma items and their products
                    $relatedEntity->load('proformaItems.product'); // Assuming 'items' relationship to ProformaItem

                    foreach ($relatedEntity->proformaItems as $item) {
                        $product = $item->product; // The actual product being sold/reserved

                        if (!$product) {

                            continue;
                        }

                        try {
                            // Create an OUT transaction for each product in the proforma
                            InventoryTransactions::create([
                                'product_id' => $product->id,
                                'factory_id' => $product->factory_id, // Get from product
                                'transaction_type' => 'OUT',
                                'quantity_changed' => -$item->quantity, // Quantity from the proforma item
                                'transaction_date' => now(),
                                'source_destination' => "Proforma Sale #{$relatedEntity->id}",
                                'reference_number' => "PROFORMA-{$relatedEntity->id}",
                                'user_id' => Auth::id(), // User who created the payment
                                'notes' => "Stock deducted for proforma #{$relatedEntity->id}, product: {$product->name}.",
                            ]);

                            // Optional: Update the proforma's main status (e.g., to 'Invoiced' or 'Fulfilled')
                            // if you have such a workflow.
                            // if ($relatedEntity->status !== 'Invoiced') {
                            //     $relatedEntity->status = 'Invoiced';
                            //     $relatedEntity->save();
                            // }

                        } catch (\Exception $e) {

                            Notification::make()
                                ->title('Inventory Deduction Failed!')
                                ->body("Could not deduct stock for product '{$product->name}' on Proforma {$relatedEntity->id}. Error: " . $e->getMessage())
                                ->danger()
                                ->send();
                            // Consider re-throwing or marking proforma as needing manual review
                        }
                    }
                    // Optional: Send a success notification after all deductions are processed
                    Notification::make()
                        ->title('Proforma Inventory Processed')
                        ->body("Stock has been deducted for Proforma {$relatedEntity->id}.")
                        ->success()
                        ->send();
                }
            }
            // Add other entity-specific status updates if needed
            // e.g., for PurchaseOrder main status based on payment completion
        });
    }
}
