<?php

namespace Tests\Feature;

use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\FinancialYear;
use App\Models\GoodsReceiptNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\SupplierDocument;
use App\Models\SupplierInvoice;
use App\Models\Tender;
use App\Models\User;
use App\Services\SupplierComplianceService;
use App\Services\SupplierPerformanceService;
use Database\Seeders\CurrentSupplierKycSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierCompliancePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private BusinessEntity $entity;

    private Department $department;

    private FinancialYear $year;

    private SupplierCategory $category;

    private User $procurement;

    private User $accountant;

    private User $supplierUser;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['supplier', 'procurement_officer', 'accountant', 'gm', 'ceo', 'auditor', 'super_admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->entity = BusinessEntity::create(['name' => 'Supplier Test Entity', 'code' => 'STE', 'is_active' => true]);
        $this->department = Department::create(['business_entity_id' => $this->entity->id, 'name' => 'Procurement', 'code' => 'PROC', 'is_active' => true]);
        $this->year = FinancialYear::create(['name' => 'FY-SUP', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'is_active' => true]);
        $this->category = SupplierCategory::create(['name' => 'ICT', 'code' => 'ICT-PERF', 'is_active' => true]);
        $this->procurement = $this->user('procurement@test.local', 'procurement_officer');
        $this->accountant = $this->user('accountant@test.local', 'accountant');
        $this->supplierUser = $this->user('supplier@test.local', 'supplier');
    }

    public function test_expired_mandatory_document_blocks_tender_invitation_and_award_eligibility(): void
    {
        $supplier = $this->supplier();
        $this->complianceDocuments($supplier, expiredType: 'business_license');
        $assessment = app(SupplierComplianceService::class)->assess($supplier);

        $this->assertSame('expired', $assessment['status']);
        $this->assertSame('blocked', $assessment['award_eligibility']);

        $tender = $this->tender();
        $this->actingAs($this->procurement)
            ->postJson('/admin/tenders/'.$tender->id.'/invite', ['supplier_ids' => [$supplier->id]])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Every invited supplier must be approved, active, category-eligible, and compliant.');
    }

    public function test_existing_legacy_supplier_data_remains_usable_by_the_extended_compliance_service(): void
    {
        $supplier = Supplier::create([
            'name' => 'Existing Legacy Supplier',
            'code' => 'SUP-LEGACY-001',
            'tax_number' => '100-200-300',
            'address' => 'Dar es Salaam',
            'portal_status' => 'approved',
            'is_active' => true,
        ]);

        $assessment = app(SupplierComplianceService::class)->assess($supplier);

        $this->assertSame('Existing Legacy Supplier', $supplier->fresh()->name);
        $this->assertSame('100-200-300', $supplier->fresh()->tax_number);
        $this->assertSame('complete', $assessment['status']);
        $this->assertSame('eligible', $assessment['award_eligibility']);
    }

    public function test_manual_and_portal_suppliers_use_the_same_mandatory_compliance_requirements(): void
    {
        $manual = $this->supplier(['name' => 'Manual Supplier', 'submitted_at' => now()]);
        $portal = $this->supplier(['name' => 'Portal Supplier', 'user_id' => $this->supplierUser->id, 'submitted_at' => now()]);

        $manualAssessment = app(SupplierComplianceService::class)->assess($manual);
        $portalAssessment = app(SupplierComplianceService::class)->assess($portal);

        $this->assertSame($manualAssessment['missing_documents'], $portalAssessment['missing_documents']);
        $this->assertSame(SupplierComplianceService::REQUIRED_DOCUMENTS, $manualAssessment['missing_documents']);
        $this->assertSame('restricted', $manualAssessment['award_eligibility']);
        $this->assertSame('restricted', $portalAssessment['award_eligibility']);
    }

    public function test_procurement_can_verify_and_activate_incomplete_kyc_with_audited_comment(): void
    {
        $supplier = $this->supplier([
            'portal_status' => 'pending_verification',
            'is_active' => false,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->procurement)
            ->patchJson('/admin/suppliers/'.$supplier->id.'/activate', [])
            ->assertUnprocessable();

        $this->actingAs($this->procurement)
            ->patchJson('/admin/suppliers/'.$supplier->id.'/activate', ['reason' => 'Approved for urgent sourcing while TRA documents are being renewed.'])
            ->assertOk()
            ->assertJsonPath('data.portal_status', 'approved')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.compliance_status', 'incomplete')
            ->assertJsonPath('data.award_eligibility', 'eligible');

        $this->assertDatabaseHas('supplier_performance_overrides', [
            'supplier_id' => $supplier->id,
            'eligibility' => 'eligible',
            'created_by' => $this->procurement->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Supplier::class,
            'subject_id' => $supplier->id,
            'actor_id' => $this->procurement->id,
            'action' => 'supplier.reactivated',
        ]);
    }

    public function test_current_general_supplier_profile_is_completed_with_verified_kyc_documents(): void
    {
        Storage::fake('local');
        $supplier = $this->supplier([
            'name' => 'General Suppliers Co LTD',
            'legal_name' => 'General Suppliers Co LTD',
            'code' => 'SUP-PENDING-000013',
            'portal_status' => 'approved',
            'is_active' => true,
            'submitted_at' => now(),
            'vat_registered' => true,
            'regulated_supplier' => true,
        ]);

        app(CurrentSupplierKycSeeder::class)->run();

        $assessment = app(SupplierComplianceService::class)->assess($supplier->fresh('documents'));
        $this->assertSame('complete', $assessment['status']);
        $this->assertSame('eligible', $assessment['award_eligibility']);
        $this->assertSame('SUP-'.str_pad((string) $supplier->id, 6, '0', STR_PAD_LEFT), $supplier->fresh()->code);
        $this->assertCount(6, $supplier->fresh('documents')->documents);
        foreach ($supplier->fresh('documents')->documents as $document) {
            $this->assertSame('verified', $document->verification_status);
            Storage::disk('local')->assertExists($document->storage_path);
        }
    }

    public function test_new_supplier_with_fewer_than_three_completed_orders_has_insufficient_data(): void
    {
        $supplier = $this->supplier();
        $this->complianceDocuments($supplier);

        $evaluation = app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);

        $this->assertSame('insufficient_data', $evaluation->grade);
        $this->assertSame(0, $evaluation->completed_purchase_orders_count);
    }

    public function test_late_delivery_reduces_delivery_score(): void
    {
        $supplier = $this->supplier();
        $this->complianceDocuments($supplier);
        foreach (range(1, 3) as $index) {
            $this->completedOrder($supplier, $index, lateDays: 5, accepted: 10, rejected: 0);
        }

        $evaluation = app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);

        $this->assertSame('75.00', $evaluation->delivery_score);
        $this->assertSame('100.00', $evaluation->quality_score);
        $this->assertNotSame('insufficient_data', $evaluation->grade);
    }

    public function test_rejected_grn_quantities_reduce_quality_score(): void
    {
        $supplier = $this->supplier();
        $this->complianceDocuments($supplier);
        foreach (range(10, 12) as $index) {
            $this->completedOrder($supplier, $index, lateDays: 0, accepted: 8, rejected: 2);
        }

        $evaluation = app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);

        $this->assertSame('100.00', $evaluation->delivery_score);
        $this->assertLessThan(100, (float) $evaluation->quality_score);
        $this->assertNotSame('insufficient_data', $evaluation->grade);
    }

    public function test_missing_compliance_documents_reduce_score_and_restrict_award(): void
    {
        $supplier = $this->supplier(['submitted_at' => now()]);

        $evaluation = app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);

        $this->assertSame('0.00', $evaluation->compliance_score);
        $this->assertSame('restricted', $supplier->fresh()->award_eligibility);
    }

    public function test_responsiveness_only_uses_invited_category_eligible_tenders(): void
    {
        $supplier = $this->supplier();
        $this->complianceDocuments($supplier);
        $invitedTender = $this->tender();
        DB::table('tender_invitations')->insert([
            'tender_id' => $invitedTender->id,
            'supplier_id' => $supplier->id,
            'invited_at' => now()->subDay(),
            'invited_by' => $this->procurement->id,
        ]);

        $unanswered = app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);
        $this->assertSame('0.00', $unanswered->responsiveness_score);

        $invitedTender->responses()->create([
            'supplier_id' => $supplier->id,
            'quotation_number' => 'Q-RESP-001',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $answered = app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);
        $this->assertGreaterThan(0, (float) $answered->responsiveness_score);

        $uninvited = $this->supplier(['name' => 'Uninvited Supplier']);
        $this->complianceDocuments($uninvited);
        $otherTender = $this->tender();
        $otherTender->responses()->create([
            'supplier_id' => $uninvited->id,
            'quotation_number' => 'Q-UNINVITED-001',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $notPenalised = app(SupplierPerformanceService::class)->calculate($uninvited, $this->entity->id, $this->accountant);
        $this->assertSame('100.00', $notPenalised->responsiveness_score);
    }

    public function test_invoice_variance_and_cancelled_order_reduce_commercial_reliability(): void
    {
        $supplier = $this->supplier();
        $this->complianceDocuments($supplier);
        $completed = $this->completedOrder($supplier, 40, lateDays: 0, accepted: 10, rejected: 0);
        PurchaseOrder::create([
            'purchase_order_number' => 'LPO-PERF-CANCELLED',
            'purchase_requisition_id' => $this->requisition('CANCELLED')->id,
            'supplier_id' => $supplier->id,
            'business_entity_id' => $this->entity->id,
            'financial_year_id' => $this->year->id,
            'status' => PurchaseOrder::STATUS_CANCELLED,
            'order_date' => now()->subMonth(),
            'currency' => 'TZS',
            'subtotal' => 500,
            'total_amount' => 500,
        ]);
        $invoice = SupplierInvoice::create([
            'invoice_number' => 'INV-PERF-001',
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $completed->id,
            'business_entity_id' => $this->entity->id,
            'financial_year_id' => $this->year->id,
            'invoice_date' => now()->subWeek(),
            'received_date' => now()->subWeek(),
            'currency' => 'TZS',
            'subtotal' => 1100,
            'total_amount' => 1100,
            'status' => SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE,
        ]);
        $invoice->matchRecords()->create([
            'purchase_order_id' => $completed->id,
            'goods_receipt_note_id' => GoodsReceiptNote::where('purchase_order_id', $completed->id)->value('id'),
            'match_status' => 'price_variance',
            'po_amount' => 1000,
            'grn_accepted_amount' => 1000,
            'invoice_amount' => 1100,
            'variance_amount' => 100,
            'variance_reason' => 'Invoice price exceeds order price.',
            'matched_by' => $this->accountant->id,
            'matched_at' => now(),
        ]);

        $evaluation = app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);

        $this->assertSame('77.00', $evaluation->commercial_reliability_score);
        $this->assertSame(1, $evaluation->cancelled_purchase_orders_count);
    }

    public function test_performance_snapshots_and_overrides_are_immutable(): void
    {
        $supplier = $this->supplier();
        $evaluation = app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);

        $this->expectException(\LogicException::class);
        $evaluation->update(['grade' => 'A']);
    }

    public function test_old_performance_evaluations_cannot_be_deleted(): void
    {
        $evaluation = app(SupplierPerformanceService::class)->calculate($this->supplier(), $this->entity->id, $this->accountant);

        $this->expectException(\LogicException::class);
        $evaluation->delete();
    }

    public function test_procurement_cannot_receive_financial_performance_fields_and_supplier_cannot_receive_internal_grade(): void
    {
        $supplier = $this->supplier(['user_id' => $this->supplierUser->id]);
        $this->complianceDocuments($supplier);
        app(SupplierPerformanceService::class)->calculate($supplier, $this->entity->id, $this->accountant);

        $this->actingAs($this->procurement)
            ->getJson('/admin/suppliers/'.$supplier->id.'/performance')
            ->assertOk()
            ->assertJsonMissingPath('data.current_evaluation.total_awarded_value')
            ->assertJsonMissingPath('data.current_evaluation.commercial_reliability_score');

        $this->actingAs($this->accountant)
            ->getJson('/admin/suppliers/'.$supplier->id.'/performance')
            ->assertOk()
            ->assertJsonPath('data.current_evaluation.total_awarded_value', '0.00');

        $this->actingAs($this->supplierUser)
            ->getJson('/supplier-portal/profile')
            ->assertOk()
            ->assertJsonMissingPath('data.current_performance')
            ->assertJsonMissingPath('data.performance_evaluations');
        $this->actingAs($this->supplierUser)->getJson('/admin/suppliers/'.$supplier->id.'/performance')->assertForbidden();
    }

    private function supplier(array $extra = []): Supplier
    {
        $supplier = Supplier::create(array_merge([
            'name' => 'Performance Supplier Ltd', 'legal_name' => 'Performance Supplier Ltd', 'code' => 'SUP-'.uniqid(),
            'portal_status' => 'approved', 'is_active' => true, 'submitted_at' => now(), 'tin_number' => 'TIN-'.uniqid(),
        ], $extra));
        $supplier->categories()->attach($this->category);

        return $supplier;
    }

    private function complianceDocuments(Supplier $supplier, ?string $expiredType = null): void
    {
        foreach (SupplierComplianceService::REQUIRED_DOCUMENTS as $type) {
            SupplierDocument::create([
                'supplier_id' => $supplier->id, 'document_type' => $type, 'original_name' => $type.'.pdf',
                'original_filename' => $type.'.pdf', 'storage_path' => 'test/'.$type.'.pdf', 'file_path' => 'test/'.$type.'.pdf',
                'mime_type' => 'application/pdf', 'size' => 100, 'status' => 'verified', 'verification_status' => 'verified',
                'expires_at' => $type === $expiredType ? now()->subDay() : now()->addYear(),
                'expiry_date' => $type === $expiredType ? now()->subDay() : now()->addYear(),
                'reviewed_by' => $this->accountant->id, 'verified_by' => $this->accountant->id, 'reviewed_at' => now(), 'verified_at' => now(),
            ]);
        }
    }

    private function completedOrder(Supplier $supplier, int $index, int $lateDays, float $accepted, float $rejected): PurchaseOrder
    {
        $requisition = $this->requisition('PO-'.$index);
        $order = PurchaseOrder::create([
            'purchase_order_number' => 'LPO-PERF-'.$index, 'purchase_requisition_id' => $requisition->id, 'supplier_id' => $supplier->id,
            'business_entity_id' => $this->entity->id, 'financial_year_id' => $this->year->id, 'status' => PurchaseOrder::STATUS_FULLY_RECEIVED,
            'order_date' => now()->subMonths(2), 'expected_delivery_date' => now()->subMonth(), 'currency' => 'TZS', 'subtotal' => 1000, 'total_amount' => 1000,
        ]);
        $item = $order->items()->create(['item_name' => 'Item '.$index, 'quantity_ordered' => 10, 'quantity_received' => $accepted, 'unit' => 'each', 'unit_price' => 100, 'line_total' => 1000]);
        $grn = GoodsReceiptNote::create([
            'grn_number' => 'GRN-PERF-'.$index, 'purchase_order_id' => $order->id, 'supplier_id' => $supplier->id,
            'business_entity_id' => $this->entity->id, 'received_by' => $this->procurement->id,
            'received_date' => now()->subMonth()->addDays($lateDays), 'status' => GoodsReceiptNote::STATUS_PARTIALLY_ACCEPTED,
        ]);
        $grn->items()->create([
            'purchase_order_item_id' => $item->id, 'item_name' => $item->item_name, 'quantity_ordered' => 10,
            'quantity_received' => 10, 'quantity_accepted' => $accepted, 'quantity_rejected' => $rejected,
            'unit' => 'each', 'condition_status' => 'partially_accepted',
        ]);

        return $order;
    }

    private function tender(): Tender
    {
        return Tender::create([
            'purchase_requisition_id' => $this->requisition('TENDER')->id, 'supplier_category_id' => $this->category->id,
            'tender_number' => 'RFQ-PERF-'.uniqid(), 'title' => 'Performance tender', 'public_summary' => 'Test',
            'tender_type' => 'restricted_rfq', 'visibility' => 'invited_only', 'submission_deadline' => now()->addWeek(),
            'contact_email' => 'procurement@test.local', 'status' => Tender::STATUS_PUBLISHED, 'created_by' => $this->procurement->id,
        ]);
    }

    private function requisition(string $suffix): PurchaseRequisition
    {
        return PurchaseRequisition::create([
            'requisition_number' => 'REQ-'.$suffix.'-'.uniqid(), 'business_entity_id' => $this->entity->id,
            'department_id' => $this->department->id, 'requester_id' => $this->procurement->id, 'line_manager_id' => $this->procurement->id,
            'required_date' => now()->addMonth(), 'purpose' => 'Supplier performance test', 'estimated_amount' => 1000,
            'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING, 'supplier_category_id' => $this->category->id,
        ]);
    }

    private function user(string $email, string $role): User
    {
        $user = User::create(['name' => ucfirst($role), 'first_name' => ucfirst($role), 'last_name' => 'User', 'email' => $email, 'department_id' => $role === 'supplier' ? null : $this->department->id, 'job_title' => $role === 'supplier' ? null : $role, 'password' => 'password', 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
