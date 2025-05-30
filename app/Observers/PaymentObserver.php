<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->updateSaleStatus($payment->sale);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        if ($payment->isDirty('status') || $payment->isDirty('amount')) {
            $this->updateSaleStatus($payment->sale);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        if ($payment->sale) {
            $this->updateSaleStatus($payment->sale);
        }
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        $this->updateSaleStatus($payment->sale);
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
    protected function updateSaleStatus(Sale $sale): void
    {
        // Use a database transaction to ensure atomicity
        DB::transaction(function () use ($sale) {
            // Re-fetch the sale to ensure we have the latest version and lock it
            $sale->refresh()->lockForUpdate();

            $totalPaid = $sale->payments()
                ->where('status', 'Completed')
                ->sum('amount');

            $hasProcessingPayments = $sale->payments()
                ->where('status', 'Processing')
                ->exists();

            $newStatus = $sale->status; // Default to current status

            if ($totalPaid >= $sale->total) {
                $newStatus = 'Completed';
            } elseif ($totalPaid > 0 || $hasProcessingPayments) {
                // If some amount is paid or there are payments in processing, set to 'processing'
                $newStatus = 'Processing';
            } else {
                // No completed payments and no payments in processing, revert to 'pending'
                $newStatus = 'Pending';
            }

            // Only update if the status has actually changed to avoid unnecessary database writes
            if ($sale->status !== $newStatus) {
                $sale->status = $newStatus;
                $sale->save();
            }
        });
    }
}
