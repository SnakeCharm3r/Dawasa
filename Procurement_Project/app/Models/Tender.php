<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tender extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_PUBLICATION = 'pending_publication';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_EVALUATION = 'evaluation_in_progress';
    public const STATUS_AWARDED = 'awarded';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'publication_at' => 'datetime', 'submission_deadline' => 'datetime',
        'expected_delivery_date' => 'date', 'published_at' => 'datetime',
    ];

    public function requisition(): BelongsTo { return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id'); }
    public function category(): BelongsTo { return $this->belongsTo(SupplierCategory::class, 'supplier_category_id'); }
    public function items(): HasMany { return $this->hasMany(TenderItem::class); }
    public function responses(): HasMany { return $this->hasMany(TenderResponse::class); }
    public function invitedSuppliers(): BelongsToMany { return $this->belongsToMany(Supplier::class, 'tender_invitations')->withPivot(['invited_at', 'invited_by']); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }

    public function scopePubliclyOpen($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where('visibility', 'public')->where('publication_at', '<=', now())
            ->where('submission_deadline', '>', now());
    }
}
