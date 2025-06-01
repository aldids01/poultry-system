<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function reorderSetting(): HasMany
    {
        return $this->hasMany(ReorderSetting::class, 'preferred_supplier_id');
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
    public function payments():HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
