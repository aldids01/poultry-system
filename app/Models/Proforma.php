<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proforma extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function proformaItems(): HasMany
    {
        return $this->hasMany(ProformaItem::class);
    }
    public function payments():HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
