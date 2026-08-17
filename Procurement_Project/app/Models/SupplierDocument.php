<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDocument extends Model
{
    protected $guarded = [];

    protected $hidden = ['storage_path', 'file_path'];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'expires_at' => 'date',
        'reviewed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
