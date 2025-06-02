<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HygieneClean extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function hygiene(): HasMany
    {
        return $this->hasMany(HygieneItem::class, 'hygiene_id');
    }
    public function supervisor():BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
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
