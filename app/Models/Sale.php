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

}
