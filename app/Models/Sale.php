<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
   use SoftDeletes;
   protected $guarded = [];
   public function customer(): BelongsTo
   {
       return $this->belongsTo(Customer::class);
   }
   public function factory(): BelongsTo
   {
       return $this->belongsTo(Factory::class);
   }
   public function user(): BelongsTo
   {
       return $this->belongsTo(User::class);
   }
   public function saleItems(): HasMany
   {
       return $this->hasMany(SaleItem::class);
   }
   public function payments():HasMany
   {
       return $this->hasMany(Payment::class);
   }
    protected function afterCreate(): void
    {
        $sale = $this->record; // The newly created Sale model

        DB::transaction(function () use ($sale) {
            // Ensure sale items are loaded, as they are now persisted
            $sale->load('saleItems');

            if ($sale->saleItems->isEmpty()) {
                Notification::make()
                    ->title('Warning: Sale #'.$sale->id.' created but has no items for inventory processing.')
                    ->warning()
                    ->send();
                return;
            }

            foreach ($sale->saleItems as $saleItem) {
                // Create an OUT inventory transaction for each item sold
                InventoryTransactions::create([
                    'product_id' => $saleItem->product_id,
                    'transaction_type' => 'OUT',
                    'quantity_changed' => -$saleItem->quantity, // Negative for debiting stock
                    'transaction_date' => now(),
                    'source_destination' => 'Sale #'.$sale->id . ' (Customer Order)',
                    'reference_number' => $sale->id,
                    'user_id' => Auth::id(),
                    'notes' => 'Stock debited for Sale #' . $sale->id,
                    'factory_id' => $sale->factory_id ?? null, // Use null-safe if factory_id might not exist
                ]);
            }
        });

        Notification::make()
            ->title('Sale created and inventory debited successfully!')
            ->success()
            ->send();
    }
}
