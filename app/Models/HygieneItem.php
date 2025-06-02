<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HygieneItem extends Model
{
    protected $guarded = [];
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
    public function hygiene(): BelongsTo
    {
        return $this->belongsTo(HygieneClean::class, 'hygiene_id');
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
