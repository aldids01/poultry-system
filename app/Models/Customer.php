<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
