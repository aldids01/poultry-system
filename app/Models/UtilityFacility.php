<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UtilityFacility extends Model
{
    protected $guarded = [];
    public function supervisor():BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
    public function lineItems():HasMany
    {
        return $this->hasMany(UtilityLineItem::class, 'utility_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function factory():BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
