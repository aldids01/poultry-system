<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
