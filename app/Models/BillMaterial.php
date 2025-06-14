<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillMaterial extends Model
{
    protected $guarded = [];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }
    public function finishedGood(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'finished_good_id');
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
