<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase6PurchaseOrderTest extends TestCase
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
            'code' => 'SUP-PHASE6',
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

    private function buildRequisition(float $budgetApproved = 5000.00, array $quoteAmounts = [900.00, 1000.00, 1100.00], float $estimated = 1000.00): PurchaseRequisition
    {
        EntityBudget::updateOrCreate(
            ['business_entity_id' => $this->entity->id, 'financial_year_id' => $this->financialYear->id],
            [
                'proposed_amount' => $budgetApproved,
                'approved_amount' => $budgetApproved,
                'committed_amount' => $estimated,
                'spent_amount' => 0,
                'available_amount' => $budgetApproved - $estimated,
                'status' => EntityBudget::STATUS_APPROVED,
                'proposed_by' => $this->superAdmin->id,
                'approved_by' => $this->superAdmin->id,
                'approved_at' => now(),
            ]
        );

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
            $quotation = SupplierQuotation::create([
                'purchase_requisition_id' => $requisition->id,
                'supplier_id' => $this->supplier->id,
                'prepared_by' => $this->procurementOfficer->id,
                'quotation_number' => 'Q-'.$counter++,
                'valid_until' => now()->addDays(20),
                'total_amount' => $amount,
                'status' => 'active',
                'submitted_at' => now(),
            ]);

            foreach (['A', 'B'] as $suffix) {
                SupplierQuotationItem::create([
                    'supplier_quotation_id' => $quotation->id,
                    'item_name' => 'Item '.$suffix,
                    'specification' => 'Specification '.$suffix,
                    'quantity' => 5,
                    'unit' => 'pcs',
                    'unit_price' => $amount / 10,
                    'total_price' => $amount / 2,
                ]);
            }
        }

        return $requisition;
    }

    private function markReady(PurchaseRequisition $requisition): void
    {
        $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/quotations-ready')
            ->assertStatus(200);
    }

    private function lowestQuote(PurchaseRequisition $requisition): SupplierQuotation
    {
        return SupplierQuotation::where('purchase_requisition_id', $requisition->id)->valid()->orderBy('total_amount')->first();
    }

    private function highestQuote(PurchaseRequisition $requisition): SupplierQuotation
    {
        return SupplierQuotation::where('purchase_requisition_id', $requisition->id)->valid()->orderByDesc('total_amount')->first();
    }

    private function buildApprovedForPurchase(array $quoteAmounts = [900.00, 1000.00, 1100.00], float $estimated = 1000.00, float $budgetApproved = 5000.00): array
    {
        $requisition = $this->buildRequisition($budgetApproved, $quoteAmounts, $estimated);
        $this->markReady($requisition);

        $lowest = $this->lowestQuote($requisition);
        $highest = $this->highestQuote($requisition);

        $this->actingAs($this->requester)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/quotation-recommendations', [
                'selected_quotation_id' => $lowest->id,
                'reason_for_selection' => 'Lowest price',
            ])->assertStatus(201);

        $recommendation = QuotationRecommendation::where('purchase_requisition_id', $requisition->id)->first();

        $this->actingAs($this->requester)
            ->postJson('/admin/purchase-requisitions/'.$requisition->id.'/quotation-recommendations/'.$recommendation->id.'/submit')
            ->assertStatus(200);

        $this->actingAs($this->gm)
            ->postJson('/admin/quotation-recommendations/'.$recommendation->id.'/approve', ['comments' => 'Approved'])
            ->assertStatus(200);

        $requisition->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_APPROVED_FOR_PURCHASE, $requisition->status);

        return ['requisition' => $requisition, 'lowest' => $lowest, 'highest' => $highest, 'recommendation' => $recommendation->fresh()];
    }

    private function createPo(PurchaseRequisition $requisition): TestResponse
    {
        return $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/purchase-orders', ['purchase_requisition_id' => $requisition->id]);
    }

    private function submitPo(PurchaseOrder $po): TestResponse
    {
        return $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/purchase-orders/'.$po->id.'/submit');
    }

    private function issuePo(PurchaseOrder $po): TestResponse
    {
        return $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/purchase-orders/'.$po->id.'/issue');
    }

    private function confirmPo(PurchaseOrder $po, ?string $comments = 'Confirmed'): TestResponse
    {
        return $this->actingAs($this->accountant)
            ->postJson('/admin/purchase-orders/'.$po->id.'/confirm', ['comments' => $comments]);
    }

    private function cancelPo(PurchaseOrder $po, string $reason = 'No longer required'): TestResponse
    {
        return $this->actingAs($this->procurementOfficer)
            ->postJson('/admin/purchase-orders/'.$po->id.'/cancel', ['cancellation_reason' => $reason]);
    }

    /** Test 1: Create a PO only from a GM-approved requisition. */
    public function test_create_po_only_from_gm_approved_requisition(): void
    {
        $sourcing = $this->buildRequisition();
        $this->createPo($sourcing)->assertStatus(422);

        $scenario = $this->buildApprovedForPurchase();
        $this->createPo($scenario['requisition'])->assertStatus(201);
    }

    /** Test 2: Supplier, quantities, and prices are populated from the selected approved quotation. */
    public function test_po_populated_from_selected_quotation(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $response = $this->createPo($scenario['requisition'])->assertStatus(201);
        $data = $response->json('data');

        $this->assertEquals($scenario['lowest']->supplier_id, $data['supplier']['id']);
        $this->assertEquals($scenario['lowest']->total_amount, $data['total_amount']);
        $this->assertCount($scenario['lowest']->items->count(), $data['items']);

        foreach ($scenario['lowest']->items as $index => $qItem) {
            $this->assertEquals($qItem->item_name, $data['items'][$index]['item_name']);
            $this->assertEquals($qItem->unit_price, $data['items'][$index]['unit_price']);
            $this->assertEquals($qItem->quantity, $data['items'][$index]['quantity_ordered']);
        }
    }

    /** Test 3: A second PO cannot be created for the same requisition. */
    public function test_second_po_for_same_requisition_is_blocked(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $this->createPo($scenario['requisition'])->assertStatus(201);
        $this->createPo($scenario['requisition'])->assertStatus(422);

        $this->assertDatabaseCount('purchase_orders', 1);
    }

    /** Test 4: Accountant cannot confirm their own prepared PO. */
    public function test_accountant_cannot_confirm_own_prepared_po(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $requisition = $scenario['requisition'];
        $budget = EntityBudget::where('business_entity_id', $requisition->business_entity_id)->first();

        $po = PurchaseOrder::create([
            'purchase_requisition_id' => $requisition->id,
            'supplier_id' => $scenario['lowest']->supplier_id,
            'quotation_recommendation_id' => $scenario['recommendation']->id,
            'selected_quotation_id' => $scenario['lowest']->id,
            'business_entity_id' => $requisition->business_entity_id,
            'financial_year_id' => $budget->financial_year_id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => now()->toDateString(),
            'currency' => 'TZS',
            'subtotal' => 900,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 900,
        ]);

        PurchaseOrderApproval::create([
            'purchase_order_id' => $po->id,
            'action' => PurchaseOrderApproval::ACTION_CREATED,
            'actor_id' => $this->accountant->id,
            'comments' => null,
            'action_at' => now(),
        ]);

        $po->update(['status' => PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION]);

        $this->actingAs($this->accountant)
            ->postJson('/admin/purchase-orders/'.$po->id.'/confirm', ['comments' => 'ok'])
            ->assertStatus(403);

        $this->assertEquals(PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION, $po->fresh()->status);
    }

    /** Test 5: Only a confirmed PO can be issued. */
    public function test_only_confirmed_po_can_be_issued(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $po = PurchaseOrder::find($this->createPo($scenario['requisition'])->json('data.id'));

        $this->issuePo($po)->assertStatus(403);

        $this->submitPo($po)->assertStatus(200);
        $this->confirmPo($po)->assertStatus(200);
        $this->issuePo($po)->assertStatus(200);

        $this->assertEquals(PurchaseOrder::STATUS_ISSUED, $po->fresh()->status);
        $this->assertNotNull($po->fresh()->purchase_order_number);
    }

    /** Test 6: Issued PO financial values cannot be edited. */
    public function test_issued_po_financial_values_cannot_be_edited(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $po = PurchaseOrder::find($this->createPo($scenario['requisition'])->json('data.id'));

        $this->submitPo($po)->assertStatus(200);
        $this->confirmPo($po)->assertStatus(200);
        $this->issuePo($po)->assertStatus(200);

        $this->actingAs($this->procurementOfficer)
            ->patchJson('/admin/purchase-orders/'.$po->id, ['notes' => 'Trying to edit issued PO'])
            ->assertStatus(403);
    }

    /** Test 7: Procurement does not receive original estimates or budget balances. */
    public function test_procurement_does_not_see_estimates_or_budget(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $po = PurchaseOrder::find($this->createPo($scenario['requisition'])->json('data.id'));

        $response = $this->actingAs($this->procurementOfficer)
            ->getJson('/admin/purchase-orders/'.$po->id)
            ->assertStatus(200);

        $requisition = $response->json('data.requisition');
        $this->assertArrayNotHasKey('estimated_amount', $requisition);
        $this->assertArrayNotHasKey('committed_amount', $requisition);
    }

    /** Test 8: A cancelled PO retains its complete approval and activity history. */
    public function test_cancelled_po_retains_full_history(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $po = PurchaseOrder::find($this->createPo($scenario['requisition'])->json('data.id'));

        $this->submitPo($po)->assertStatus(200);
        $this->confirmPo($po)->assertStatus(200);
        $this->issuePo($po)->assertStatus(200);
        $this->cancelPo($po)->assertStatus(200);

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATUS_CANCELLED, $po->status);
        $this->assertNotNull($po->cancelled_at);
        $this->assertNotNull($po->cancellation_reason);

        $this->assertDatabaseCount('purchase_order_approvals', 5);
        $this->assertDatabaseHas('purchase_order_approvals', ['purchase_order_id' => $po->id, 'action' => PurchaseOrderApproval::ACTION_CREATED]);
        $this->assertDatabaseHas('purchase_order_approvals', ['purchase_order_id' => $po->id, 'action' => PurchaseOrderApproval::ACTION_SUBMITTED_FOR_CONFIRMATION]);
        $this->assertDatabaseHas('purchase_order_approvals', ['purchase_order_id' => $po->id, 'action' => PurchaseOrderApproval::ACTION_ACCOUNTANT_CONFIRMED]);
        $this->assertDatabaseHas('purchase_order_approvals', ['purchase_order_id' => $po->id, 'action' => PurchaseOrderApproval::ACTION_ISSUED]);
        $this->assertDatabaseHas('purchase_order_approvals', ['purchase_order_id' => $po->id, 'action' => PurchaseOrderApproval::ACTION_CANCELLED]);

        $this->assertGreaterThanOrEqual(5, ActivityLog::where('subject_type', PurchaseOrder::class)->where('subject_id', $po->id)->count());
    }

    /** Extra: Accountant can return a pending PO to procurement with comments. */
    public function test_accountant_returns_po_to_procurement(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $po = PurchaseOrder::find($this->createPo($scenario['requisition'])->json('data.id'));

        $this->submitPo($po)->assertStatus(200);

        $this->actingAs($this->accountant)
            ->postJson('/admin/purchase-orders/'.$po->id.'/return', ['comments' => 'Revise delivery terms'])
            ->assertStatus(200);

        $this->assertEquals(PurchaseOrder::STATUS_DRAFT, $po->fresh()->status);
    }

    public function test_accountant_can_reject_an_lpo_before_it_is_issued(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $po = PurchaseOrder::find($this->createPo($scenario['requisition'])->json('data.id'));
        $this->submitPo($po)->assertOk();

        $this->actingAs($this->accountant)
            ->postJson('/admin/purchase-orders/'.$po->id.'/reject', [
                'comments' => 'Payment commitment cannot be made.',
            ])
            ->assertOk();

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATUS_REJECTED, $po->status);
        $this->assertEquals($this->accountant->id, $po->rejected_by);
        $this->assertDatabaseHas('purchase_order_approvals', [
            'purchase_order_id' => $po->id,
            'action' => PurchaseOrderApproval::ACTION_ACCOUNTANT_REJECTED,
        ]);
    }

    public function test_supplier_invoice_is_blocked_until_store_accepts_delivery(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $po = PurchaseOrder::find($this->createPo($scenario['requisition'])->json('data.id'));
        $this->submitPo($po)->assertOk();
        $this->confirmPo($po)->assertOk();
        $this->issuePo($po)->assertOk();
        $poItem = $po->items()->firstOrFail();

        $this->actingAs($this->accountant)
            ->postJson('/admin/supplier-invoices', [
                'invoice_number' => 'INV-BEFORE-RECEIPT',
                'purchase_order_id' => $po->id,
                'invoice_date' => now()->toDateString(),
                'received_date' => now()->toDateString(),
                'currency' => 'TZS',
                'subtotal' => $poItem->line_total,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $poItem->line_total,
                'items' => [[
                    'purchase_order_item_id' => $poItem->id,
                    'quantity_invoiced' => $poItem->quantity_ordered,
                    'unit_price' => $poItem->unit_price,
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'An invoice can only be recorded after the store or warehouse accepts a delivery against the LPO.');

        $this->assertDatabaseCount('supplier_invoices', 0);
    }

    public function test_supplier_invoice_totals_are_calculated_from_its_lines(): void
    {
        $scenario = $this->buildApprovedForPurchase();
        $po = PurchaseOrder::find($this->createPo($scenario['requisition'])->json('data.id'));
        $this->submitPo($po)->assertOk();
        $this->confirmPo($po)->assertOk();
        $this->issuePo($po)->assertOk();
        $poItem = $po->items()->firstOrFail();
        $poItem->update(['quantity_received' => 5]);

        $receipt = GoodsReceiptNote::create([
            'grn_number' => 'GRN-TOTALS',
            'purchase_order_id' => $po->id,
            'supplier_id' => $po->supplier_id,
            'business_entity_id' => $po->business_entity_id,
            'received_by' => $this->procurementOfficer->id,
            'received_date' => now()->toDateString(),
            'delivery_note_number' => 'DN-TOTALS',
            'status' => GoodsReceiptNote::STATUS_ACCEPTED,
        ]);

        GoodsReceiptNoteItem::create([
            'goods_receipt_note_id' => $receipt->id,
            'purchase_order_item_id' => $poItem->id,
            'item_name' => $poItem->item_name,
            'quantity_ordered' => $poItem->quantity_ordered,
            'quantity_received' => 5,
            'quantity_accepted' => 5,
            'quantity_rejected' => 0,
            'unit' => $poItem->unit,
            'condition_status' => GoodsReceiptNoteItem::CONDITION_ACCEPTED,
        ]);

        $response = $this->actingAs($this->accountant)
            ->postJson('/admin/supplier-invoices', [
                'invoice_number' => 'INV-SERVER-TOTALS',
                'purchase_order_id' => $po->id,
                'invoice_date' => now()->toDateString(),
                'received_date' => now()->toDateString(),
                'currency' => 'TZS',
                'subtotal' => 999999,
                'discount_amount' => 10,
                'tax_amount' => 20,
                'total_amount' => 999999,
                'items' => [[
                    'purchase_order_item_id' => $poItem->id,
                    'quantity_invoiced' => 2,
                    'unit_price' => 123.45,
                ]],
            ])
            ->assertCreated();

        $invoice = SupplierInvoice::findOrFail($response->json('data.id'));
        $this->assertEquals(246.90, (float) $invoice->subtotal);
        $this->assertEquals(256.90, (float) $invoice->total_amount);
        $this->assertEquals(256.90, (float) $invoice->outstanding_amount);
        $this->assertEquals(246.90, (float) $invoice->items()->firstOrFail()->line_total);
    }
}
