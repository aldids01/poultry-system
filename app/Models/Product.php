<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransactions::class);
    }
    public function reorderSetting(): HasOne
    {
        return $this->hasOne(ReorderSetting::class);
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
    public function rawMaterials(): HasMany
    {
        return $this->hasMany(BillMaterial::class, 'finished_good_id');
    }
    public function usedInBillMaterials(): HasMany
    {
        return $this->hasMany(BillMaterial::class, 'raw_material_id');
    }
    public function scopeRawMaterials(Builder $query): Builder
    {
        return $query->where('product_type', 'raw_materials');
    }
    public function scopeFinishedGoods(Builder $query): Builder
    {
        return $query->where('product_type', 'finished_goods');
    }
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'product_id');
    }
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }


}
