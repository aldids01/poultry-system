<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    protected $guarded = [];
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

}
