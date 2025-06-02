<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityLineItem extends Model
{
    protected $guarded = [];
    public function utility():BelongsTo
    {
        return $this->belongsTo(UtilityFacility::class, 'utility_id');
    }
    public function item():BelongsTo
    {
        return $this->belongsTo(UtilityItem::class, 'utility_item_id');
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
