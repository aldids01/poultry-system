<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoolingVan extends Model
{
    protected $guarded = [];
    public function supervisor():BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
    public function vanItems(): HasMany
    {
        return $this->hasMany(VanItem::class, 'cooling_id');
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
