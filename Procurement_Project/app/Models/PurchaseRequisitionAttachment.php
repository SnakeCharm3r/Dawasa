<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_requisition_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'is_confidential',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_confidential' => 'boolean',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
