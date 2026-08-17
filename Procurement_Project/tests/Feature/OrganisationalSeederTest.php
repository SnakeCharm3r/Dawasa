<?php

namespace Tests\Feature;

use App\Models\BusinessEntity;
use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\User;
use Database\Seeders\OrganisationalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganisationalSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_an_approved_current_budget_for_the_demo_entity(): void
    {
        $this->seed(OrganisationalSeeder::class);

        $entity = BusinessEntity::where('code', 'PROC001')->firstOrFail();
        $financialYear = FinancialYear::where('is_active', true)->sole();
        $budget = EntityBudget::query()
            ->where('business_entity_id', $entity->id)
            ->where('financial_year_id', $financialYear->id)
            ->firstOrFail();

        $this->assertSame('FY'.now()->year, $financialYear->name);
        $this->assertSame(EntityBudget::STATUS_APPROVED, $budget->status);
        $this->assertSame('250000000.00', $budget->approved_amount);
        $this->assertSame('43000000.00', $budget->committed_amount);
        $this->assertSame('18000000.00', $budget->spent_amount);
        $this->assertSame('189000000.00', $budget->available_amount);
        $this->assertSame('accountant@hq.test', User::findOrFail($budget->proposed_by)->email);
        $this->assertSame('gm@hq.test', User::findOrFail($budget->approved_by)->email);
    }
}
