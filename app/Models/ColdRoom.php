<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ColdRoom extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by_id');
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
