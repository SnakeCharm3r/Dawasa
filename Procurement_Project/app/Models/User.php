<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'department_id',
        'line_manager_id',
        'job_title',
        'is_line_manager',
        'email',
        'phone',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_line_manager' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'line_manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'line_manager_id');
    }

    public function proposedBudgets(): HasMany
    {
        return $this->hasMany(EntityBudget::class, 'proposed_by');
    }

    public function approvedBudgets(): HasMany
    {
        return $this->hasMany(EntityBudget::class, 'approved_by');
    }

    public function budgetApprovals(): HasMany
    {
        return $this->hasMany(BudgetApproval::class, 'actor_id');
    }

    public function budgetTransactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class, 'created_by');
    }

    public function hasReport(User $user): bool
    {
        foreach ($this->directReports as $report) {
            if ($report->id === $user->id || $report->hasReport($user)) {
                return true;
            }
        }

        return false;
    }
}
