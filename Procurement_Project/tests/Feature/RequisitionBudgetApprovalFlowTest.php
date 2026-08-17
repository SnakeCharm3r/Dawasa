<?php

namespace Tests\Feature;

use App\Models\BudgetTransaction;
use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\SupplierInvoice;
use App\Models\SupplierQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequisitionBudgetApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private BusinessEntity $entity;

    private Department $department;

    private FinancialYear $financialYear;

    private User $requester;

    private User $lineManager;

    private User $gm;

    private User $accountant;

    private User $ceo;

    private User $superAdmin;

    private SupplierCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['requester', 'line_manager', 'department_head', 'gm', 'accountant', 'ceo', 'super_admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->entity = BusinessEntity::create(['name' => 'Operating Company', 'code' => 'OPCO', 'is_active' => true]);
        $this->department = Department::create(['business_entity_id' => $this->entity->id, 'name' => 'Operations', 'code' => 'OPS', 'is_active' => true]);
        $this->financialYear = FinancialYear::create(['name' => 'FY2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $this->category = SupplierCategory::create(['code' => 'TEST', 'name' => 'Test supplies', 'is_active' => true]);
        $this->lineManager = $this->user('manager@example.test', 'line_manager');
        $this->requester = $this->user('requester@example.test', 'requester', $this->lineManager->id);
        $this->gm = $this->user('gm@example.test', 'gm');
        $this->accountant = $this->user('accountant@example.test', 'accountant');
        $this->ceo = $this->user('ceo@example.test', 'ceo');
        $this->superAdmin = $this->user('admin@example.test', 'super_admin');
    }

    public function test_sufficient_request_moves_requester_to_line_manager_to_gm_before_sourcing(): void
    {
        $budget = $this->budget(10_000, 2_000, 1_000);
        $requisition = $this->requisition(3_000);

        $this->actingAs($this->requester)
            ->getJson('/admin/requisition-budget-check?business_entity_id='.$this->entity->id.'&amount=3000')
            ->assertOk()
            ->assertJsonPath('data.status', 'sufficient')
            ->assertJsonMissingPath('data.total_allocated_budget')
            ->assertJsonMissingPath('data.total_used_amount')
            ->assertJsonMissingPath('data.available_amount');

        $this->actingAs($this->requester)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/submit')
            ->assertOk();

        $this->actingAs($this->lineManager)
            ->getJson('/admin/purchase-requisitions')
            ->assertOk()
            ->assertJsonFragment(['requisition_number' => $requisition->requisition_number]);
        $this->actingAs($this->lineManager)
            ->getJson('/admin/dashboard/requester')
            ->assertOk()
            ->assertJsonPath('data.awaiting_my_approval', 1);

        $this->actingAs($this->lineManager)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Department need confirmed.'])
            ->assertOk()
            ->assertJsonPath('data.status', PurchaseRequisition::STATUS_PENDING_GM_APPROVAL);

        $this->assertSame('2000.00', $budget->fresh()->committed_amount);

        $this->actingAs($this->gm)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Approved for sourcing.'])
            ->assertOk()
            ->assertJsonPath('data.status', PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING)
            ->assertJsonPath('data.budget_check.total_allocated_budget', '10000.00')
            ->assertJsonPath('data.budget_check.total_used_amount', '6000.00')
            ->assertJsonPath('data.budget_check.available_amount', '4000.00');

        $this->assertSame('5000.00', $budget->fresh()->committed_amount);
        $this->assertSame('4000.00', $budget->fresh()->available_amount);
        $this->assertDatabaseHas('requisition_approvals', ['purchase_requisition_id' => $requisition->id, 'action' => 'line_manager_approved']);
        $this->assertDatabaseHas('requisition_approvals', ['purchase_requisition_id' => $requisition->id, 'action' => 'gm_approved']);
    }

    public function test_shortfall_requires_justification_but_can_be_approved_and_committed(): void
    {
        $budget = $this->budget(5_000, 3_500, 1_000);
        $requisition = $this->requisition(2_000);

        $this->actingAs($this->requester)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/submit')
            ->assertStatus(422)
            ->assertJsonValidationErrors('budget_shortfall_acknowledged');

        $this->actingAs($this->requester)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/submit', [
                'budget_shortfall_acknowledged' => true,
                'budget_shortfall_reason' => 'The balance will be funded through the approved working-capital loan facility.',
            ])
            ->assertOk();

        $this->actingAs($this->lineManager)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Operationally required.'])
            ->assertOk();

        $this->actingAs($this->gm)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Loan funding acknowledged.'])
            ->assertOk();

        $requisition->refresh();
        $this->assertSame(PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING, $requisition->status);
        $this->assertTrue($requisition->budget_shortfall_acknowledged);
        $this->assertSame('1500.00', $requisition->budget_shortfall_amount);
        $this->assertSame('5500.00', $budget->fresh()->committed_amount);
        $this->assertSame('-1500.00', $budget->fresh()->available_amount);
        $this->assertDatabaseHas('budget_transactions', [
            'reference_id' => $requisition->id,
            'transaction_type' => BudgetTransaction::TYPE_COMMITMENT,
            'amount' => 2000,
        ]);
    }

    public function test_request_can_reach_sourcing_without_an_approved_budget_when_loan_funding_is_recorded(): void
    {
        $requisition = $this->requisition(2_000);

        $this->actingAs($this->requester)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/submit', [
                'budget_shortfall_acknowledged' => true,
                'budget_shortfall_reason' => 'Purchase will be financed by a short-term supplier credit facility.',
            ])
            ->assertOk();
        $this->actingAs($this->lineManager)->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Required for operations.'])->assertOk();
        $this->actingAs($this->gm)->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Credit funding approved.'])->assertOk();

        $this->assertSame(PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING, $requisition->fresh()->status);
        $this->assertDatabaseCount('budget_transactions', 0);
    }

    public function test_only_gm_ceo_and_accountant_receive_consolidated_budget_totals(): void
    {
        $this->budget(10_000, 2_000, 1_000);
        $requisition = $this->requisition(3_000);

        $this->actingAs($this->requester)
            ->getJson('/admin/purchase-requisitions/'.$requisition->id)
            ->assertOk()
            ->assertJsonPath('budget_check.status', 'sufficient')
            ->assertJsonMissingPath('budget_check.total_allocated_budget')
            ->assertJsonMissingPath('budget_check.total_used_amount')
            ->assertJsonMissingPath('budget_check.available_amount');

        $this->actingAs($this->lineManager)
            ->getJson('/admin/purchase-requisitions/'.$requisition->id)
            ->assertOk()
            ->assertJsonPath('budget_check.status', 'sufficient')
            ->assertJsonMissingPath('budget_check.total_allocated_budget')
            ->assertJsonMissingPath('budget_check.total_used_amount')
            ->assertJsonMissingPath('budget_check.available_amount');

        foreach ([$this->gm, $this->ceo, $this->accountant] as $executiveUser) {
            $this->actingAs($executiveUser)
                ->getJson('/admin/purchase-requisitions/'.$requisition->id)
                ->assertOk()
                ->assertJsonPath('budget_check.total_allocated_budget', '10000.00')
                ->assertJsonPath('budget_check.total_used_amount', '3000.00')
                ->assertJsonPath('budget_check.available_amount', '7000.00')
                ->assertJsonMissingPath('budget_check.committed_amount')
                ->assertJsonMissingPath('budget_check.spent_amount');
        }
    }

    public function test_budget_register_history_and_spend_reports_are_executive_finance_only(): void
    {
        $budget = $this->budget(10_000, 2_000, 1_000);

        $this->actingAs($this->superAdmin)->getJson('/admin/entity-budgets')->assertForbidden();
        $this->actingAs($this->superAdmin)->getJson('/admin/entity-budgets/'.$budget->id.'/history')->assertForbidden();
        $this->actingAs($this->superAdmin)->getJson('/admin/reports/budget-commitment')->assertForbidden();
        $this->actingAs($this->superAdmin)->getJson('/admin/reports/procurement-spend')->assertForbidden();

        foreach ([$this->gm, $this->ceo, $this->accountant] as $executiveUser) {
            $this->actingAs($executiveUser)->getJson('/admin/entity-budgets')->assertOk();
            $this->actingAs($executiveUser)->getJson('/admin/entity-budgets/'.$budget->id.'/history')->assertOk();
            $this->actingAs($executiveUser)->getJson('/admin/reports/budget-commitment')->assertOk();
            $this->actingAs($executiveUser)->getJson('/admin/reports/procurement-spend')->assertOk();
        }
    }

    public function test_ceo_has_global_policy_access_and_can_open_every_dashboard_and_report(): void
    {
        $this->assertTrue(Gate::forUser($this->ceo)->allows('delete', $this->requester));

        foreach (['executive', 'operational', 'finance', 'auditor'] as $dashboard) {
            $this->actingAs($this->ceo)->getJson('/admin/dashboard/'.$dashboard)->assertOk();
        }

        $this->actingAs($this->ceo)
            ->getJson('/admin/dashboard/operational?business_entity_id='.$this->entity->id)
            ->assertOk()
            ->assertJsonPath('data.awaiting_requester_confirmation', 0);

        foreach ([
            'requisition-register',
            'requisition-approval-turnaround',
            'sourcing-quotation-comparison',
            'non-lowest-price-recommendation',
            'supplier-quotation-award',
            'purchase-order-register',
            'purchase-order-status',
            'grn-inspection',
            'supplier-invoice-variance',
            'payment-voucher-register',
            'outstanding-supplier-liabilities',
            'budget-commitment',
            'procurement-spend',
            'procurement-cycle-time',
            'supplier-performance',
            'closure-exception',
            'audit-timeline',
        ] as $report) {
            $this->actingAs($this->ceo)->getJson('/admin/reports/'.$report)->assertOk();
        }

        $this->actingAs($this->ceo)->getJson('/admin/users')->assertOk();
        $this->actingAs($this->ceo)->getJson('/admin/suppliers')->assertOk();
        $this->actingAs($this->ceo)->postJson('/admin/entities', [
            'name' => 'CEO Created Entity',
            'code' => 'CEO01',
            'is_active' => true,
        ])->assertCreated();
    }

    public function test_line_manager_can_create_and_submit_a_requisition_directly_to_gm(): void
    {
        $budget = $this->budget(10_000, 0, 0);

        $this->actingAs($this->lineManager)
            ->postJson('/admin/purchase-requisitions', [
                'business_entity_id' => $this->entity->id,
                'department_id' => $this->department->id,
                'supplier_category_id' => $this->category->id,
                'required_date' => now()->addWeek()->toDateString(),
                'purpose' => 'Line manager operational request.',
                'estimated_amount' => 2_000,
                'items' => [[
                    'item_name' => 'Operational equipment',
                    'specification' => 'Required specification',
                    'quantity' => 2,
                    'unit' => 'piece',
                    'estimated_unit_price' => 1_000,
                    'estimated_total' => 2_000,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', PurchaseRequisition::STATUS_DRAFT);

        $requisition = PurchaseRequisition::where('requester_id', $this->lineManager->id)->latest('id')->firstOrFail();
        $this->assertSame($this->lineManager->id, $requisition->line_manager_id);

        $this->actingAs($this->lineManager)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/submit')
            ->assertOk()
            ->assertJsonPath('data.status', PurchaseRequisition::STATUS_PENDING_GM_APPROVAL);

        $this->assertDatabaseHas('requisition_approvals', [
            'purchase_requisition_id' => $requisition->id,
            'actor_id' => $this->lineManager->id,
            'action' => 'line_manager_approved',
        ]);
        $this->assertSame('0.00', $budget->fresh()->committed_amount);

        $this->actingAs($this->gm)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Approved for sourcing.'])
            ->assertOk()
            ->assertJsonPath('data.status', PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING);

        $this->assertSame('2000.00', $budget->fresh()->committed_amount);
    }

    public function test_requester_routes_only_to_the_assigned_line_manager_in_the_same_department(): void
    {
        $hrDepartment = Department::create([
            'business_entity_id' => $this->entity->id,
            'name' => 'Human Resources',
            'code' => 'HR',
            'is_active' => true,
        ]);
        $hrManager = User::create([
            'name' => 'HR Line Manager',
            'first_name' => 'Hawa',
            'last_name' => 'HR',
            'email' => 'hr-manager@example.test',
            'department_id' => $hrDepartment->id,
            'job_title' => 'HR Line Manager',
            'is_line_manager' => true,
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);
        $hrManager->assignRole('line_manager');

        $this->requester->update(['line_manager_id' => $hrManager->id]);
        $this->actingAs($this->requester)->postJson('/admin/purchase-requisitions', $this->requisitionPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('line_manager_id');

        $this->requester->update(['line_manager_id' => $this->lineManager->id]);
        $this->actingAs($this->requester)->postJson('/admin/purchase-requisitions', $this->requisitionPayload())
            ->assertCreated();

        $requisition = PurchaseRequisition::where('requester_id', $this->requester->id)->latest('id')->firstOrFail();
        $this->assertSame($this->lineManager->id, $requisition->line_manager_id);
        $this->assertSame($this->department->id, $requisition->department_id);

        // Even a stale draft is corrected to the requester's current manager on submission.
        $requisition->update(['line_manager_id' => $hrManager->id]);
        $this->budget(10_000, 0, 0);
        $this->actingAs($this->requester)->postJson('/admin/purchase-requisitions/'.$requisition->id.'/submit')->assertOk();
        $this->assertSame($this->lineManager->id, $requisition->fresh()->line_manager_id);

        $this->actingAs($hrManager)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Wrong department.'])
            ->assertForbidden();
        $this->actingAs($this->lineManager)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/approve', ['comments' => 'Operations approved.'])
            ->assertOk();

        $managerOwnedRequest = PurchaseRequisition::create([
            'requisition_number' => 'PR-MANAGER-OWNER-'.uniqid(),
            'business_entity_id' => $this->entity->id,
            'department_id' => $this->department->id,
            'requester_id' => $hrManager->id,
            'line_manager_id' => $hrManager->id,
            'supplier_category_id' => $this->category->id,
            'required_date' => now()->addWeek(),
            'purpose' => 'Confirm ownership remains visible after a department change.',
            'estimated_amount' => 500,
            'committed_amount' => 0,
            'status' => PurchaseRequisition::STATUS_PENDING_GM_APPROVAL,
        ]);

        $this->actingAs($hrManager)
            ->getJson('/admin/purchase-requisitions')
            ->assertOk()
            ->assertJsonFragment(['requisition_number' => $managerOwnedRequest->requisition_number]);
        $this->actingAs($hrManager)
            ->getJson('/admin/purchase-requisitions/'.$managerOwnedRequest->id)
            ->assertOk();
        $this->actingAs($hrManager)
            ->postJson('/admin/purchase-requisitions/'.$managerOwnedRequest->id.'/approve', ['comments' => 'Self approval must remain disabled.'])
            ->assertForbidden();
    }

    public function test_registers_follow_owner_manager_and_entity_visibility_rules(): void
    {
        $secondEntity = BusinessEntity::create(['name' => 'Second Company', 'code' => 'SECOND', 'is_active' => true]);
        $secondDepartment = Department::create(['business_entity_id' => $secondEntity->id, 'name' => 'Second Operations', 'code' => 'OPS2', 'is_active' => true]);

        $otherManager = $this->user('other-manager@example.test', 'department_head');
        $otherManager->update(['is_line_manager' => true]);
        $otherRequester = $this->user('other-requester@example.test', 'requester', $otherManager->id);
        $secondManager = $this->entityUser('second-manager@example.test', 'department_head', $secondDepartment, true);
        $secondRequester = $this->entityUser('second-requester@example.test', 'requester', $secondDepartment, false, $secondManager->id);

        $assigned = $this->requisition(1_000);
        $managerOwned = $this->requisitionFor($this->lineManager, $this->lineManager, $this->department, $this->entity, 'PR-MANAGER-OWN');
        $otherAssigned = $this->requisitionFor($otherRequester, $otherManager, $this->department, $this->entity, 'PR-OTHER-MANAGER');
        $secondEntityRequest = $this->requisitionFor($secondRequester, $secondManager, $secondDepartment, $secondEntity, 'PR-SECOND-ENTITY');

        $this->actingAs($this->lineManager)->getJson('/admin/purchase-requisitions')
            ->assertOk()
            ->assertJsonFragment(['requisition_number' => $assigned->requisition_number])
            ->assertJsonFragment(['requisition_number' => $managerOwned->requisition_number])
            ->assertJsonMissing(['requisition_number' => $otherAssigned->requisition_number])
            ->assertJsonMissing(['requisition_number' => $secondEntityRequest->requisition_number]);

        $this->actingAs($this->requester)->getJson('/admin/purchase-requisitions')
            ->assertOk()
            ->assertJsonFragment(['requisition_number' => $assigned->requisition_number])
            ->assertJsonMissing(['requisition_number' => $managerOwned->requisition_number]);

        $this->actingAs($this->gm)->getJson('/admin/purchase-requisitions')
            ->assertOk()
            ->assertJsonFragment(['requisition_number' => $otherAssigned->requisition_number])
            ->assertJsonMissing(['requisition_number' => $secondEntityRequest->requisition_number]);
        $this->actingAs($this->gm)->getJson('/admin/purchase-requisitions/'.$secondEntityRequest->id)->assertForbidden();

        $this->actingAs($this->ceo)->getJson('/admin/purchase-requisitions?business_entity_id='.$secondEntity->id)
            ->assertOk()
            ->assertJsonFragment(['requisition_number' => $secondEntityRequest->requisition_number])
            ->assertJsonMissing(['requisition_number' => $assigned->requisition_number]);

        $supplier = Supplier::create(['name' => 'Scope Supplier', 'code' => 'SCOPE-SUP', 'is_active' => true]);
        foreach ([$assigned, $managerOwned, $otherAssigned, $secondEntityRequest] as $index => $requisition) {
            $quotation = SupplierQuotation::create([
                'purchase_requisition_id' => $requisition->id,
                'supplier_id' => $supplier->id,
                'prepared_by' => $this->accountant->id,
                'quotation_number' => 'PF-SCOPE-'.$index,
                'total_amount' => 1_000,
                'status' => SupplierQuotation::STATUS_ACTIVE,
            ]);
            $order = PurchaseOrder::create([
                'purchase_order_number' => 'LPO-SCOPE-'.$index,
                'purchase_requisition_id' => $requisition->id,
                'supplier_id' => $supplier->id,
                'selected_quotation_id' => $quotation->id,
                'business_entity_id' => $requisition->business_entity_id,
                'financial_year_id' => $this->financialYear->id,
                'status' => PurchaseOrder::STATUS_ISSUED,
                'order_date' => now()->toDateString(),
                'currency' => 'TZS',
                'subtotal' => 1_000,
                'total_amount' => 1_000,
            ]);
            SupplierInvoice::create([
                'invoice_number' => 'INV-SCOPE-'.$index,
                'supplier_id' => $supplier->id,
                'purchase_order_id' => $order->id,
                'business_entity_id' => $requisition->business_entity_id,
                'financial_year_id' => $this->financialYear->id,
                'invoice_date' => now()->toDateString(),
                'received_date' => now()->toDateString(),
                'currency' => 'TZS',
                'subtotal' => 1_000,
                'total_amount' => 1_000,
                'outstanding_amount' => 1_000,
                'status' => SupplierInvoice::STATUS_SUBMITTED,
            ]);
        }

        foreach ([
            '/admin/supplier-quotations' => ['PF-SCOPE-0', 'PF-SCOPE-1', 'PF-SCOPE-2', 'PF-SCOPE-3'],
            '/admin/purchase-orders' => ['LPO-SCOPE-0', 'LPO-SCOPE-1', 'LPO-SCOPE-2', 'LPO-SCOPE-3'],
            '/admin/supplier-invoices' => ['INV-SCOPE-0', 'INV-SCOPE-1', 'INV-SCOPE-2', 'INV-SCOPE-3'],
        ] as $endpoint => [$assignedReference, $ownedReference, $otherReference, $secondReference]) {
            $this->actingAs($this->lineManager)->getJson($endpoint)
                ->assertOk()->assertJsonFragment([$assignedReference])->assertJsonFragment([$ownedReference])
                ->assertJsonMissing([$otherReference])->assertJsonMissing([$secondReference]);
            $this->actingAs($this->requester)->getJson($endpoint)
                ->assertOk()->assertJsonFragment([$assignedReference])->assertJsonMissing([$ownedReference]);
            $this->actingAs($this->gm)->getJson($endpoint)
                ->assertOk()->assertJsonFragment([$otherReference])->assertJsonMissing([$secondReference]);
            $this->actingAs($this->ceo)->getJson($endpoint.'?business_entity_id='.$secondEntity->id)
                ->assertOk()->assertJsonFragment([$secondReference])->assertJsonMissing([$assignedReference]);
        }
    }

    private function entityUser(string $email, string $role, Department $department, bool $isLineManager, ?int $lineManagerId = null): User
    {
        $user = User::create([
            'name' => ucfirst($role), 'first_name' => ucfirst($role), 'last_name' => 'User', 'email' => $email,
            'department_id' => $department->id, 'line_manager_id' => $lineManagerId, 'job_title' => $role,
            'is_line_manager' => $isLineManager, 'is_active' => true, 'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function requisitionFor(User $requester, User $manager, Department $department, BusinessEntity $entity, string $number): PurchaseRequisition
    {
        return PurchaseRequisition::create([
            'requisition_number' => $number, 'business_entity_id' => $entity->id, 'department_id' => $department->id,
            'requester_id' => $requester->id, 'line_manager_id' => $manager->id, 'supplier_category_id' => $this->category->id,
            'required_date' => now()->addWeek(), 'purpose' => 'Entity visibility test', 'estimated_amount' => 1_000,
            'committed_amount' => 0, 'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
        ]);
    }

    private function requisitionPayload(): array
    {
        return [
            'business_entity_id' => $this->entity->id,
            'department_id' => $this->department->id,
            'supplier_category_id' => $this->category->id,
            'required_date' => now()->addWeek()->toDateString(),
            'purpose' => 'Department-specific approval routing test.',
            'estimated_amount' => 1_000,
            'items' => [[
                'item_name' => 'Department equipment',
                'specification' => 'Standard department equipment',
                'quantity' => 1,
                'unit' => 'piece',
                'estimated_unit_price' => 1_000,
                'estimated_total' => 1_000,
            ]],
        ];
    }

    private function user(string $email, string $role, ?int $lineManagerId = null): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $email,
            'department_id' => $this->department->id,
            'line_manager_id' => $lineManagerId,
            'job_title' => $role,
            'is_line_manager' => $role === 'line_manager',
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function budget(float $approved, float $committed, float $spent): EntityBudget
    {
        return EntityBudget::create([
            'business_entity_id' => $this->entity->id,
            'financial_year_id' => $this->financialYear->id,
            'proposed_amount' => $approved,
            'approved_amount' => $approved,
            'committed_amount' => $committed,
            'spent_amount' => $spent,
            'available_amount' => $approved - $committed - $spent,
            'status' => EntityBudget::STATUS_APPROVED,
            'proposed_by' => $this->gm->id,
            'approved_by' => $this->gm->id,
            'approved_at' => now(),
        ]);
    }

    private function requisition(float $estimated): PurchaseRequisition
    {
        return PurchaseRequisition::create([
            'requisition_number' => 'PR-FLOW-'.uniqid(),
            'business_entity_id' => $this->entity->id,
            'department_id' => $this->department->id,
            'requester_id' => $this->requester->id,
            'line_manager_id' => $this->lineManager->id,
            'required_date' => now()->addWeek(),
            'purpose' => 'Maintain operational continuity.',
            'estimated_amount' => $estimated,
            'committed_amount' => 0,
            'status' => PurchaseRequisition::STATUS_DRAFT,
        ]);
    }
}
