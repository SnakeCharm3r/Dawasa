<?php

namespace Tests\Feature;

use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\ProcurementApproval;
use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase5QuotationRecommendationTest extends TestCase
{
    use RefreshDatabase;

    private array $roles = ['super_admin', 'gm', 'accountant', 'procurement_officer', 'department_head', 'requester', 'auditor', 'line_manager'];

    private User $superAdmin;

    private User $requester;

    private User $lineManager;

    private User $procurementOfficer;

    private User $gm;

    private User $accountant;

    private User $auditor;

    private User $departmentHead;

    private BusinessEntity $entity;

    private Department $department;

    private FinancialYear $financialYear;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->entity = BusinessEntity::create(['name' => 'Entity', 'code' => 'ENT', 'is_active' => true]);
        $this->department = Department::create(['business_entity_id' => $this->entity->id, 'name' => 'Dept', 'code' => 'DPT', 'is_active' => true]);
        $this->financialYear = FinancialYear::create(['name' => 'FY2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);

        $this->superAdmin = $this->makeUser('super_admin');
        $this->lineManager = $this->makeUser('line_manager', ['is_line_manager' => true]);
        $this->requester = $this->makeUser('requester', ['line_manager_id' => $this->lineManager->id]);
        $this->procurementOfficer = $this->makeUser('procurement_officer');
        $this->gm = $this->makeUser('gm');
        $this->accountant = $this->makeUser('accountant');
        $this->auditor = $this->makeUser('auditor');
        $this->departmentHead = $this->makeUser('department_head');

        $this->supplier = Supplier::create([
            'name' => 'Test Supplier',
            'code' => 'SUP-PHASE5',
            'is_active' => true,
        ]);
    }

    private function makeUser(string $role, array $extra = []): User
    {
        $user = User::create(array_merge([
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'name' => ucfirst($role).' User',
            'email' => $role.'@example.com',
            'department_id' => $this->department->id ?? null,
            'job_title' => $role,
            'is_line_manager' => false,
            'is_active' => true,
            'password' => bcrypt('password'),
        ], $extra));

        $user->assignRole($role);

        return $user;
    }

    /**
     * Build a requisition in approved_for_sourcing with an approved entity budget and
     * three valid supplier quotations.
     */
    private function buildScenario(float $budgetApproved = 5000.00, array $quoteAmounts = [900.00, 1000.00, 1100.00], float $estimated = 1000.00): PurchaseRequisition
    {
        $budget = EntityBudget::create([
            'business_entity_id' => $this->entity->id,
            'financial_year_id' => $this->financialYear->id,
            'proposed_amount' => $budgetApproved,
            'approved_amount' => $budgetApproved,
            'committed_amount' => $estimated,
            'spent_amount' => 0,
            'available_amount' => $budgetApproved - $estimated,
            'status' => EntityBudget::STATUS_APPROVED,
            'proposed_by' => $this->superAdmin->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        $requisition = PurchaseRequisition::create([
            'requisition_number' => 'PR-'.uniqid(),
            'business_entity_id' => $this->entity->id,
            'department_id' => $this->department->id,
            'requester_id' => $this->requester->id,
            'line_manager_id' => $this->lineManager->id,
            'required_date' => now()->addDays(10),
            'purpose' => 'Test requisition',
            'estimated_amount' => $estimated,
            'committed_amount' => $estimated,
            'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
        ]);

        $counter = 1;
        foreach ($quoteAmounts as $amount) {
            SupplierQuotation::create([
                'purchase_requisition_id' => $requisition->id,
                'supplier_id' => $this->supplier->id,
                'prepared_by' => $this->procurementOfficer->id,
                'quotation_number' => 'Q-'.$counter++,
                'valid_until' => now()->addDays(20),
                'total_amount' => $amount,
                'status' => 'active',
                'submitted_at' => now(),
            ]);
        }

        return $requisition;
    }

    private function lowestQuote(PurchaseRequisition $requisition): SupplierQuotation
    {
        return SupplierQuotation::where('purchase_requisition_id', $requisition->id)->valid()->orderBy('total_amount')->first();
    }

    private function highestQuote(PurchaseRequisition $requisition): SupplierQuotation
    {
        return SupplierQuotation::where('purchase_requisition_id', $requisition->id)->valid()->orderByDesc('total_amount')->first();
    }

    private function markReady(PurchaseRequisition $requisition): void
    {
        $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/quotations-ready')
            ->assertStatus(200);

        $requisition->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_QUOTATIONS_READY, $requisition->status);
    }

    private function sendForRequesterReview(PurchaseRequisition $requisition, SupplierQuotation $quote): TestResponse
    {
        $lowest = $this->lowestQuote($requisition);
        $isLowest = $quote->id === $lowest->id;
        
        $data = [
            'reason_for_selection' => 'Best fit',
        ];
        
        if (! $isLowest) {
            $data['non_lowest_price_reason'] = 'Faster delivery lead time and warranty coverage.';
        }
        
        return $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/supplier-quotations/'.$quote->id.'/request-approval', $data);
    }

    private function createRecommendation(PurchaseRequisition $requisition, SupplierQuotation $quote, array $extra = []): TestResponse
    {
        return $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/quotation-recommendations', array_merge([
                'selected_quotation_id' => $quote->id,
                'reason_for_selection' => 'Best fit',
            ], $extra));
    }

    private function submitRecommendation(PurchaseRequisition $requisition, QuotationRecommendation $recommendation): TestResponse
    {
        $response = $this->actingAs($this->requester)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/quotation-recommendations/'.$recommendation->id.'/submit');
        
        $recommendation->refresh();
        
        return $response;
    }

    private function lineManagerApprove(PurchaseRequisition $requisition, QuotationRecommendation $recommendation, ?string $comments = 'Approved'): TestResponse
    {
        $recommendation->refresh();
        
        $response = $this->actingAs($this->lineManager)
            ->postJson('/admin/quotation-recommendations/'.$recommendation->id.'/line-manager-approve', ['comments' => $comments]);
        
        $recommendation->refresh();
        
        return $response;
    }

    /** Test 1: Procurement records at least three quotations for one approved requisition. */
    public function test_procurement_records_at_least_three_quotations(): void
    {
        $requisition = $this->buildScenario();

        $count = SupplierQuotation::where('purchase_requisition_id', $requisition->id)->valid()->count();
        $this->assertGreaterThanOrEqual(3, $count);
    }

    /** Test 2: Requester selects the lowest quotation and submits it to GM. */
    public function test_requester_submits_lowest_quotation(): void
    {
        $requisition = $this->buildScenario();
        $this->markReady($requisition);

        $lowest = $this->lowestQuote($requisition);

        $this->sendForRequesterReview($requisition, $lowest)->assertStatus(200);

        $recommendation = QuotationRecommendation::where('purchase_requisition_id', $requisition->id)->first();

        $this->submitRecommendation($requisition, $recommendation)->assertStatus(200);

        $requisition->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_PENDING_LINE_MANAGER_APPROVAL, $requisition->status);
        $this->assertEquals(QuotationRecommendation::STATUS_SUBMITTED, $recommendation->fresh()->status);
    }

    /** Test 3: Requester selects a higher quotation and is blocked until a justification is supplied. */
    public function test_higher_quotation_blocked_without_justification(): void
    {
        $requisition = $this->buildScenario();
        $this->markReady($requisition);

        $highest = $this->highestQuote($requisition);

        $this->createRecommendation($requisition, $highest)
            ->assertStatus(422)
            ->assertJsonValidationErrors('non_lowest_price_reason');

        $this->assertDatabaseCount('quotation_recommendations', 0);

        $response = $this->createRecommendation($requisition, $highest, [
            'non_lowest_price_reason' => 'Faster delivery lead time and warranty coverage.',
        ]);
        $response->assertStatus(201);
    }

    /** Test 4: GM approves a lower final amount and the unused committed budget is released. */
    public function test_gm_approves_lower_amount_releases_budget(): void
    {
        $requisition = $this->buildScenario(5000.00, [900.00, 1000.00, 1100.00], 1000.00);
        $this->markReady($requisition);

        $lowest = $this->lowestQuote($requisition);
        $this->sendForRequesterReview($requisition, $lowest);
        $recommendation = QuotationRecommendation::where('purchase_requisition_id', $requisition->id)->first();

        $this->submitRecommendation($requisition, $recommendation)->assertStatus(200);
        $this->lineManagerApprove($requisition, $recommendation)->assertStatus(200);

        $budget = EntityBudget::where('business_entity_id', $this->entity->id)
            ->where('financial_year_id', $this->financialYear->id)
            ->first();
        $committedBefore = $budget->committed_amount;

        $this->actingAs($this->gm)
            ->postJson('/admin/quotation-recommendations/'.$recommendation->id.'/approve', ['comments' => 'Approved'])
            ->assertStatus(200);

        $requisition->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_APPROVED_FOR_PURCHASE, $requisition->status);

        $budget->refresh();
        $this->assertEquals($committedBefore - 100.00, $budget->committed_amount);
        $this->assertDatabaseHas('budget_transactions', [
            'entity_budget_id' => $budget->id,
            'transaction_type' => 'commitment_release',
            'amount' => 100.00,
        ]);
    }

    /** Test 5: GM approves a higher final amount only when additional budget is available. */
    public function test_gm_approves_higher_amount_when_budget_available(): void
    {
        $requisition = $this->buildScenario(5000.00, [900.00, 1000.00, 1100.00], 1000.00);
        $this->markReady($requisition);

        $highest = $this->highestQuote($requisition);
        $this->sendForRequesterReview($requisition, $highest);
        $recommendation = QuotationRecommendation::where('purchase_requisition_id', $requisition->id)->first();

        $this->submitRecommendation($requisition, $recommendation)->assertStatus(200);
        $this->lineManagerApprove($requisition, $recommendation)->assertStatus(200);

        $budget = EntityBudget::where('business_entity_id', $this->entity->id)
            ->where('financial_year_id', $this->financialYear->id)
            ->first();
        $committedBefore = $budget->committed_amount;

        $this->actingAs($this->gm)
            ->postJson('/admin/quotation-recommendations/'.$recommendation->id.'/approve', ['comments' => 'Approved'])
            ->assertStatus(200);

        $budget->refresh();
        $this->assertEquals($committedBefore + 100.00, $budget->committed_amount);
        $this->assertDatabaseHas('budget_transactions', [
            'entity_budget_id' => $budget->id,
            'transaction_type' => 'commitment',
            'amount' => 100.00,
        ]);
    }

    /** Test 6: GM is blocked if the additional amount exceeds available budget. */
    public function test_gm_blocked_when_budget_insufficient(): void
    {
        $requisition = $this->buildScenario(1000.00, [900.00, 1000.00, 1100.00], 1000.00);
        $this->markReady($requisition);

        $highest = $this->highestQuote($requisition);
        $this->sendForRequesterReview($requisition, $highest);
        $recommendation = QuotationRecommendation::where('purchase_requisition_id', $requisition->id)->first();

        $this->submitRecommendation($requisition, $recommendation)->assertStatus(200);
        $this->lineManagerApprove($requisition, $recommendation)->assertStatus(200);

        $this->actingAs($this->gm)
            ->postJson('/admin/quotation-recommendations/'.$recommendation->id.'/approve', ['comments' => 'Approved'])
            ->assertStatus(422);

        $requisition->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_PENDING_FINAL_APPROVAL, $requisition->status);
    }

    /** Test 7: GM returns a recommendation to sourcing and Procurement can revise/add quotations. */
    public function test_gm_returns_to_sourcing(): void
    {
        $requisition = $this->buildScenario();
        $this->markReady($requisition);

        $lowest = $this->lowestQuote($requisition);
        $this->sendForRequesterReview($requisition, $lowest);
        $recommendation = QuotationRecommendation::where('purchase_requisition_id', $requisition->id)->first();

        $this->submitRecommendation($requisition, $recommendation)->assertStatus(200);
        $this->lineManagerApprove($requisition, $recommendation)->assertStatus(200);

        $this->actingAs($this->gm)
            ->postJson('/admin/quotation-recommendations/'.$recommendation->id.'/return', ['comments' => 'Revise quotes'])
            ->assertStatus(200);

        $requisition->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_RETURNED_TO_SOURCING, $requisition->status);
        $this->assertEquals(QuotationRecommendation::STATUS_RETURNED, $recommendation->fresh()->status);

        // Procurement marks ready again and a new recommendation can be created.
        $this->markReady($requisition);
        $this->sendForRequesterReview($requisition, $lowest)->assertStatus(200);
    }

    /** Test 8: Confirm Procurement never receives estimated or budget figures in any endpoint response. */
    public function test_procurement_never_sees_confidential_figures(): void
    {
        $requisition = $this->buildScenario();
        $this->markReady($requisition);

        $response = $this->actingAs($this->procurementOfficer)
            ->getJson('/admin/purchase-requisitions/'.$requisition->id.'/quotation-comparison')
            ->assertStatus(200);

        $data = $response->json();
        $this->assertArrayNotHasKey('estimated_amount', $data['requisition']);
        $this->assertArrayNotHasKey('committed_amount', $data['requisition']);

        foreach ($data['requisition']['items'] as $item) {
            $this->assertArrayNotHasKey('estimated_unit_price', $item);
            $this->assertArrayNotHasKey('estimated_total', $item);
        }
    }

    public function test_procurement_can_reject_an_active_proforma_with_an_audit_reason(): void
    {
        $requisition = $this->buildScenario();
        $proforma = $this->lowestQuote($requisition);

        $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/supplier-quotations/'.$proforma->id.'/reject', [
                'reason' => 'Supplier terms are not acceptable.',
            ])
            ->assertOk();

        $proforma->refresh();
        $this->assertEquals(SupplierQuotation::STATUS_REJECTED, $proforma->status);
        $this->assertEquals($this->procurementOfficer->id, $proforma->rejected_by);
        $this->assertNotNull($proforma->rejected_at);
        $this->assertEquals('Supplier terms are not acceptable.', $proforma->rejection_reason);
    }

    /** Test 9: Procurement can withdraw a sent proforma and the requisition returns to quotations_ready. */
    public function test_procurement_can_withdraw_a_sent_proforma(): void
    {
        $requisition = $this->buildScenario();
        $this->markReady($requisition);

        $lowest = $this->lowestQuote($requisition);
        $this->sendForRequesterReview($requisition, $lowest);
        $recommendation = QuotationRecommendation::where('purchase_requisition_id', $requisition->id)->first();

        $this->submitRecommendation($requisition, $recommendation)->assertStatus(200);
        $this->assertEquals(PurchaseRequisition::STATUS_PENDING_LINE_MANAGER_APPROVAL, $requisition->refresh()->status);

        $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/supplier-quotations/'.$lowest->id.'/withdraw', [
                'reason' => 'Pricing no longer valid.',
            ])
            ->assertStatus(200);

        $recommendation->refresh();
        $requisition->refresh();
        $lowest->refresh();

        $this->assertEquals(QuotationRecommendation::STATUS_WITHDRAWN, $recommendation->status);
        $this->assertEquals(PurchaseRequisition::STATUS_QUOTATIONS_READY, $requisition->status);
        $this->assertEquals(SupplierQuotation::STATUS_WITHDRAWN, $lowest->status);

        $this->assertDatabaseHas('procurement_approvals', [
            'purchase_requisition_id' => $requisition->id,
            'quotation_recommendation_id' => $recommendation->id,
            'action' => ProcurementApproval::ACTION_WITHDRAWN,
            'actor_id' => $this->procurementOfficer->id,
            'comments' => 'Pricing no longer valid.',
        ]);
    }
}
