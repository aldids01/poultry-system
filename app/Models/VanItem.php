<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VanItem extends Model
{
    protected $guarded = [];
    public function coolingVan(): BelongsTo
    {
        return $this->belongsTo(CoolingVan::class, 'cooling_id');
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
