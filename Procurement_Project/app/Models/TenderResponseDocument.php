<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderResponseDocument extends Model
{
    protected $guarded = [];
    protected $hidden = ['storage_path'];
    public function response(): BelongsTo { return $this->belongsTo(TenderResponse::class, 'tender_response_id'); }
}
