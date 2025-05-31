<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'purchase_order_id');
    }
    protected $casts = [
        'order_date' => 'datetime',
        'delivery_date' => 'datetime',
        'approval_date' => 'datetime',
        'total' => 'decimal:2',
    ];
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }
    protected static function booted(): void
    {
        parent::booted();

        // Auto-generate PO number on creation
        static::creating(function (PurchaseOrder $po) {
            $po->po_number = 'PO-' . now()->format('Ymd') . '-' . str_pad(self::whereDate('created_at', now()->toDateString())->count() + 1, 4, '0', STR_PAD_LEFT);
            if (empty($po->factory_id) && Filament::hasTenant()) {
                $po->factory_id = Filament::getTenant()->id;
            }
        });

        // Global scope for multi-tenancy
        if (auth()->check()) {
            static::addGlobalScope('factory', function (Builder $builder) {
                if ($tenant = Filament::getTenant()) {
                    $builder->where('factory_id', $tenant->id);
                }
            });
        }
    }
    public function isFullyReceived(): bool
    {
        foreach ($this->items as $item) {
            if ($item->quantity_received < $item->quantity) {
                return false;
            }
        }
        return true;
    }

    // Helper to check if any item is received
    public function isPartiallyReceived(): bool
    {
        return $this->items->where('quantity_received', '>', 0)->count() > 0 && !$this->isFullyReceived();
    }

}
