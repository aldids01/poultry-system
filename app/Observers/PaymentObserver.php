<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->updateSaleStatus($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        if ($payment->isDirty('status') || $payment->isDirty('amount')) {
            $this->updateSaleStatus($payment);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
            $this->updateSaleStatus($payment);
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        $this->updateSaleStatus($payment);
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
    /**
     * Core logic to update the sale's status based on its payments.
     */
    protected function updateSaleStatus(Payment $payment): void
    {
        // Determine which entity this payment belongs to
        $relatedEntity = null;
        $totalField = ''; // Field on the entity that stores its total amount
        $statusField = ''; // Field on the entity that stores its payment status
        $mainStatusField = ''; // For PO's main status field

        if ($payment->sale_id) {
            $relatedEntity = $payment->sale;
            $totalField = 'total';
            $statusField = 'status'; // Assuming your Sale model uses 'sales_status'
        } elseif ($payment->purchase_order_id) {
            $relatedEntity = $payment->purchaseOrder;
            $totalField = 'total'; // Assuming your PurchaseOrder model uses 'total_amount'
            $statusField = 'payment'; // PO's payment status field
            $mainStatusField = 'status'; // PO's main status field
        }
        // Add more conditions for customer_id or supplier_id if they have statuses to update
        // elseif ($payment->customer_id) {
        //     $relatedEntity = $payment->customer;
        //     $totalField = 'total_due'; // Example
        //     $statusField = 'account_status'; // Example
        // }

        if (!$relatedEntity) {
            // No related entity found, or it's a type not handled by this observer
            return;
        }

        // Use a database transaction to ensure atomicity
        DB::transaction(function () use ($relatedEntity, $totalField, $statusField, $mainStatusField) {
            // Re-fetch the entity to ensure we have the latest version and lock it
            $relatedEntity->refresh()->lockForUpdate();

            $totalPaid = $relatedEntity->payments()
                ->where('status', 'Completed') // Use Enum value
                ->sum('amount'); // Your Payment model uses 'amount'

            $hasProcessingPayments = $relatedEntity->payments()
                ->where('status', 'Processing') // Use Enum value
                ->exists();

            $entityTotal = $relatedEntity->{$totalField};
            $currentPaymentStatus = $relatedEntity->{$statusField};
            $newPaymentStatus = $currentPaymentStatus; // Default to current

            if ($totalPaid >= $entityTotal) {
                $newPaymentStatus = 'Completed'; // For PO
                // For Sale: $newPaymentStatus = SaleStatusEnum::COMPLETED->value;
            } elseif ($totalPaid > 0 || $hasProcessingPayments) {
                $newPaymentStatus = 'Processing'; // For PO
                // For Sale: $newPaymentStatus = SaleStatusEnum::PROCESSING->value;
            } else {
                $newPaymentStatus = 'Pending'; // For PO
                // For Sale: $newPaymentStatus = SaleStatusEnum::PENDING->value;
            }

            // Update the payment status field on the related entity
            if ($relatedEntity->{$statusField} !== $newPaymentStatus) {
                $relatedEntity->{$statusField} = $newPaymentStatus;
            }

            // Specific logic for PurchaseOrder's 'paid_amount' and 'status'
            if ($relatedEntity instanceof PurchaseOrder) {
                // Also update the main 'status' of the PO if it's 'approved' and becomes 'paid'
                // This is optional and depends on your PO workflow.
                // You might have other conditions here (e.g., only if not already received)
                if ($newPaymentStatus === 'Completed' &&
                    $relatedEntity->{$mainStatusField} === 'Approved') {
                    // Example: Change main PO status to indicate it's ready for receipt
                    // $relatedEntity->{$mainStatusField} = PurchaseOrderStatusEnum::READY_FOR_RECEIPT; // Custom enum value
                }
            }

            // Save the related entity if anything changed
            if ($relatedEntity->isDirty()) {
                $relatedEntity->save();
            }
        });
    }
}
