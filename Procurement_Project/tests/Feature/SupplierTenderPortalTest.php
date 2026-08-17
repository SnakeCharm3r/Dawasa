<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\Tender;
use App\Models\TenderResponse;
use App\Models\SupplierQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierTenderPortalTest extends TestCase
{
    use RefreshDatabase;

    protected SupplierCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['supplier', 'procurement_officer', 'gm', 'super_admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->category = SupplierCategory::create(['name' => 'ICT', 'code' => 'ICT', 'is_active' => true]);
    }

    public function test_public_tender_payload_never_contains_internal_requisition_or_finance_data(): void
    {
        $tender = $this->tender();

        $response = $this->getJson('/portal/tenders/'.$tender->tender_number)->assertOk();

        $response->assertJsonMissingPath('data.purchase_requisition_id')
            ->assertJsonMissingPath('data.created_by')
            ->assertJsonMissingPath('data.requisition')
            ->assertJsonMissingPath('data.items.0.estimated_unit_price')
            ->assertJsonMissingPath('data.items.0.estimated_total');
    }

    public function test_supplier_cannot_read_another_suppliers_response(): void
    {
        [$owner, $ownerSupplier] = $this->supplier('owner@example.test');
        [$intruder] = $this->supplier('intruder@example.test');
        $response = TenderResponse::create([
            'tender_id' => $this->tender()->id, 'supplier_id' => $ownerSupplier->id,
            'quotation_number' => 'Q-01', 'quotation_date' => now()->toDateString(),
            'currency' => 'TZS', 'subtotal' => 100, 'tax_amount' => 0, 'total_amount' => 100, 'status' => 'draft',
        ]);

        $this->actingAs($intruder)->getJson('/supplier-portal/tender-responses/'.$response->id)->assertForbidden();
        $this->actingAs($owner)->getJson('/supplier-portal/tender-responses/'.$response->id)->assertOk();
    }

    public function test_tender_submission_is_blocked_after_deadline(): void
    {
        [$user] = $this->supplier('late@example.test');
        $tender = $this->tender(['submission_deadline' => now()->subMinute()]);

        $this->actingAs($user)->postJson('/supplier-portal/tenders/'.$tender->id.'/responses', [
            'quotation_number' => 'LATE-01', 'quotation_date' => now()->toDateString(), 'currency' => 'TZS',
            'items' => [['tender_item_id' => $tender->items->first()->id, 'unit_price' => 100]],
        ])->assertStatus(422)->assertJsonPath('message', 'The submission deadline has passed.');
    }

    public function test_only_authorised_approver_can_publish_a_tender(): void
    {
        $tender = $this->tender(['status' => Tender::STATUS_PENDING_PUBLICATION]);
        $procurement = $this->user('procurement@example.test', 'procurement_officer');
        $gm = $this->user('gm@example.test', 'gm');

        $this->actingAs($procurement)->postJson('/admin/tenders/'.$tender->id.'/publish', ['comments' => 'Publish'])->assertForbidden();
        $this->actingAs($gm)->postJson('/admin/tenders/'.$tender->id.'/publish', ['comments' => 'Approved for publication'])->assertOk();
        $this->assertDatabaseHas('tenders', ['id' => $tender->id, 'status' => Tender::STATUS_PUBLISHED, 'published_by' => $gm->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'tender.published', 'actor_id' => $gm->id]);
    }

    public function test_supplier_onboarding_creates_owned_pending_application_and_audit_record(): void
    {
        Notification::fake();
        $payload = [
            'email' => 'new@supplier.test', 'password' => 'StrongPass123', 'password_confirmation' => 'StrongPass123',
            'legal_name' => 'New Supplier Limited', 'registration_number' => 'REG-100', 'tin_number' => 'TIN-100',
            'supplier_type' => 'company', 'address' => 'Dar es Salaam', 'region' => 'Dar es Salaam', 'country' => 'Tanzania',
            'contact_name' => 'Asha Buyer', 'contact_position' => 'Director', 'phone' => '+255700000001',
            'category_ids' => [$this->category->id], 'products_services' => 'Computers and support',
            'declaration_accurate' => true, 'agree_terms' => true, 'agree_privacy' => true,
        ];

        $this->postJson('/portal/supplier-registration', $payload)->assertCreated()
            ->assertJsonPath('data.status', 'pending_verification');

        $supplier = Supplier::where('email', 'new@supplier.test')->firstOrFail();
        $this->assertNotNull($supplier->user_id);
        $this->assertTrue($supplier->user->hasRole('supplier'));
        $this->assertDatabaseHas('supplier_category_supplier', ['supplier_id' => $supplier->id, 'supplier_category_id' => $this->category->id]);
        $this->assertTrue(ActivityLog::where('action', 'supplier.application_submitted')->where('subject_id', $supplier->id)->exists());
        Notification::assertSentTo($supplier->user, VerifyEmail::class);
    }

    public function test_compliant_tender_response_enters_existing_supplier_quotation_workflow(): void
    {
        [, $supplier] = $this->supplier('bidder@example.test');
        $reviewer = $this->user('reviewer@example.test', 'procurement_officer');
        $tender = $this->tender(['status' => Tender::STATUS_CLOSED, 'submission_deadline' => now()->subMinute()]);
        $response = TenderResponse::create([
            'tender_id' => $tender->id, 'supplier_id' => $supplier->id, 'receipt_number' => 'BID-2026-00001',
            'quotation_number' => 'PF-100', 'quotation_date' => now()->toDateString(), 'valid_until' => now()->addMonth(),
            'currency' => 'TZS', 'subtotal' => 500000, 'tax_amount' => 0, 'total_amount' => 500000,
            'status' => 'submitted', 'submitted_at' => now()->subHour(),
        ]);
        $response->items()->create([
            'tender_item_id' => $tender->items->first()->id, 'unit_price' => 250000,
            'line_total' => 500000, 'brand_make' => 'Test brand', 'offered_specification' => '16GB RAM',
        ]);

        $this->actingAs($reviewer)->postJson('/admin/tender-responses/'.$response->id.'/compliance', [
            'decision' => 'compliant', 'comments' => 'All mandatory requirements met.',
        ])->assertOk();

        $quotation = SupplierQuotation::where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame(SupplierQuotation::STATUS_ACTIVE, $quotation->status);
        $this->assertSame($tender->purchase_requisition_id, $quotation->purchase_requisition_id);
        $this->assertSame($quotation->id, $response->fresh()->supplier_quotation_id);
    }

    private function tender(array $overrides = []): Tender
    {
        $entity = BusinessEntity::firstOrCreate(['code' => 'HQ'], ['name' => 'HQ', 'is_active' => true]);
        $department = Department::firstOrCreate(['business_entity_id' => $entity->id, 'code' => 'IT'], ['name' => 'IT', 'is_active' => true]);
        $requester = $this->user('requester@example.test', 'super_admin');
        $requisition = PurchaseRequisition::create([
            'requisition_number' => 'PR-'.uniqid(), 'business_entity_id' => $entity->id,
            'department_id' => $department->id, 'requester_id' => $requester->id, 'line_manager_id' => $requester->id,
            'required_date' => now()->addMonth(), 'purpose' => 'Confidential internal purpose',
            'estimated_amount' => 999999, 'committed_amount' => 999999,
            'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING, 'supplier_category_id' => $this->category->id,
        ]);
        $item = PurchaseRequisitionItem::create([
            'purchase_requisition_id' => $requisition->id, 'item_name' => 'Laptop', 'specification' => '16GB RAM',
            'quantity' => 2, 'unit' => 'piece', 'estimated_unit_price' => 999999, 'estimated_total' => 1999998,
        ]);
        $tender = Tender::create(array_merge([
            'purchase_requisition_id' => $requisition->id, 'supplier_category_id' => $this->category->id,
            'tender_number' => 'RFQ-'.uniqid(), 'title' => 'Supply laptops', 'public_summary' => 'Supply suitable equipment',
            'tender_type' => 'rfq', 'visibility' => 'public', 'publication_at' => now()->subMinute(),
            'submission_deadline' => now()->addDay(), 'contact_email' => 'procurement@example.test',
            'status' => Tender::STATUS_PUBLISHED, 'created_by' => $requester->id,
        ], $overrides));
        $tender->items()->create([
            'purchase_requisition_item_id' => $item->id, 'item_name' => $item->item_name,
            'specification' => $item->specification, 'quantity' => $item->quantity, 'unit' => $item->unit,
        ]);
        return $tender->fresh('items');
    }

    private function supplier(string $email): array
    {
        $user = $this->user($email, 'supplier');
        $supplier = Supplier::create([
            'user_id' => $user->id, 'name' => $email, 'code' => 'SUP-'.uniqid(), 'email' => $email,
            'portal_status' => 'approved', 'is_active' => true,
        ]);
        $supplier->categories()->attach($this->category);
        return [$user, $supplier];
    }

    private function user(string $email, string $role): User
    {
        $user = User::firstOrCreate(['email' => $email], ['name' => $email, 'first_name' => 'Portal', 'last_name' => 'User', 'password' => 'password', 'is_active' => true]);
        $user->syncRoles([$role]);
        return $user;
    }
}
