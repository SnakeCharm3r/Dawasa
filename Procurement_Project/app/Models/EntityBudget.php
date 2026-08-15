<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntityBudget extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'business_entity_id',
        'financial_year_id',
        'proposed_amount',
        'approved_amount',
        'committed_amount',
        'spent_amount',
        'available_amount',
        'status',
        'proposed_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'approval_comments',
        'notes',
    ];

    protected $casts = [
        'proposed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BudgetApproval::class, 'entity_budget_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class, 'entity_budget_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
            ->where('subject_type', self::class)
            ->orderBy('created_at', 'desc');
    }

    public function calculateAvailable(): string
    {
        $approved = $this->approved_amount !== null ? $this->approved_amount : 0;
        return number_format($approved - $this->committed_amount - $this->spent_amount, 2, '.', '');
    }

    public function syncAvailable(): self
    {
        $this->available_amount = $this->calculateAvailable();
        $this->saveQuietly();

        return $this;
    }
}
