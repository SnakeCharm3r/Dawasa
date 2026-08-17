<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderItem extends Model
{
    protected $guarded = [];
    protected $casts = ['quantity' => 'decimal:3'];
    public function tender(): BelongsTo { return $this->belongsTo(Tender::class); }
}
