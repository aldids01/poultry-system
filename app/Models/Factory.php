<?php

namespace App\Models;

use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Factory extends Model implements HasAvatar, HasCurrentTenantLabel
{
    use SoftDeletes;
    protected $guarded = [];
    public function members():BelongsToMany
    {
        return $this->belongsToMany(User::class, 'factory_user', 'factory_id', 'user_id');
    }
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null ;
    }
    public function getCurrentTenantLabel(): string
    {
        return 'Active Factory';
    }
//    public function roles(): HasMany
//    {
//        return $this->hasMany(\Spatie\Permission\Models\Role::class);
//    }
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransactions::class);
    }
    public function reorderSettigs(): HasMany
    {
        return $this->hasMany(ReorderSetting::class);
    }
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    public function proformas():HasMany
    {
        return $this->hasMany(Proforma::class);
    }
    public function proformaItems(): HasMany
    {
        return $this->hasMany(ProformaItem::class);
    }
    public function areas():HasMany
    {
        return $this->hasMany(Area::class);
    }
    public function utilityItems():HasMany
    {
        return $this->hasMany(UtilityItem::class);
    }
    public function birdReceiveds():HasMany
    {
        return $this->hasMany(BirdReceived::class);
    }
    public function blastFreezers():HasMany
    {
        return $this->hasMany(BlastFreezer::class);
    }
    public function coldRooms():HasMany
    {
        return $this->hasMany(ColdRoom::class);
    }
    public function coolingVans():HasMany
    {
        return $this->hasMany(CoolingVan::class);
    }
    public function vanItems():HasMany
    {
        return $this->hasMany(VanItem::class);
    }
    public function hygieneCleans():HasMany
    {
        return $this->hasMany(HygieneClean::class);
    }
    public function utilityFacilities():HasMany
    {
        return $this->hasMany(UtilityFacility::class);
    }
}
