<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\SupplierQuotation;
use App\Models\Tender;
use App\Models\TenderResponse;
use App\Models\User;
use App\Notifications\TenderAwardedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertNotNull($supplier->user->email_verified_at);
        $this->assertAuthenticatedAs($supplier->user);
        $this->assertDatabaseHas('supplier_category_supplier', ['supplier_id' => $supplier->id, 'supplier_category_id' => $this->category->id]);
        $this->assertTrue(ActivityLog::where('action', 'supplier.application_submitted')->where('subject_id', $supplier->id)->exists());
        Notification::assertNothingSent();
    }

    public function test_pending_supplier_can_browse_and_respond_to_public_tenders_without_category_or_email_approval(): void
    {
        $user = $this->user('direct-access@example.test', 'supplier');
        $user->forceFill(['email_verified_at' => null])->save();
        $supplier = Supplier::create([
            'user_id' => $user->id, 'name' => 'Direct Access Supplier', 'code' => 'SUP-DIRECT',
            'email' => $user->email, 'portal_status' => 'pending_verification', 'is_active' => false,
        ]);
        $tender = $this->tender();

        $this->actingAs($user)
            ->getJson('/supplier-portal/tenders')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $tender->id);

        $this->actingAs($user)->postJson('/supplier-portal/tenders/'.$tender->id.'/responses', [
            'quotation_number' => 'DIRECT-01', 'quotation_date' => now()->toDateString(), 'currency' => 'TZS',
            'items' => [['tender_item_id' => $tender->items->first()->id, 'unit_price' => 100]],
        ])->assertCreated();

        $this->assertDatabaseHas('tender_responses', ['tender_id' => $tender->id, 'supplier_id' => $supplier->id]);
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

    public function test_other_suppliers_proforma_route_goes_to_gm_before_portal_publication(): void
    {
        $existingTender = $this->tender();
        $requisition = $existingTender->requisition;
        $existingTender->items()->delete();
        $existingTender->delete();
        $procurement = $this->user('public-rfq@example.test', 'procurement_officer');
        $procurement->update(['department_id' => $requisition->department_id]);

        $response = $this->actingAs($procurement)->postJson('/admin/purchase-requisitions/'.$requisition->id.'/other-suppliers-tender', [
            'title' => 'Public supply request',
            'public_summary' => 'Supply the listed products according to the published specification.',
            'submission_deadline' => now()->addDays(3)->toIso8601String(),
            'expected_delivery_date' => now()->addMonth()->toDateString(),
            'delivery_location' => 'Dar es Salaam',
            'contact_email' => 'public-rfq@example.test',
        ])->assertCreated()->assertJsonPath('data.status', Tender::STATUS_PENDING_PUBLICATION);

        $tender = Tender::findOrFail($response->json('data.id'));
        $this->getJson('/portal/tenders')->assertJsonMissing(['tender_number' => $tender->tender_number]);

        $gm = $this->user('rfq-gm@example.test', 'gm');
        $this->actingAs($gm)->postJson('/admin/tenders/'.$tender->id.'/publish', ['comments' => 'Approved for public quotation.'])->assertOk();
        $this->getJson('/portal/tenders')->assertJsonFragment(['tender_number' => $tender->tender_number]);
    }

    public function test_direct_supplier_proforma_number_is_generated_automatically(): void
    {
        $existingTender = $this->tender();
        $requisition = $existingTender->requisition;
        $existingTender->items()->delete();
        $existingTender->delete();
        [, $supplier] = $this->supplier('direct-proforma@example.test');
        $procurement = $this->user('proforma-creator@example.test', 'procurement_officer');
        $procurement->update(['department_id' => $requisition->department_id]);

        $response = $this->actingAs($procurement)->postJson('/admin/supplier-quotations', [
            'purchase_requisition_id' => $requisition->id,
            'supplier_id' => $supplier->id,
            'quotation_number' => 'USER-SUPPLIED-NUMBER',
            'valid_until' => now()->addMonth()->toDateString(),
            'items' => [[
                'item_name' => 'Attempted replacement item',
                'specification' => 'Attempted replacement specification',
                'quantity' => 999,
                'unit' => 'altered unit',
                'unit_price' => 250000,
            ]],
        ])->assertCreated();

        $number = $response->json('data.quotation_number');
        $this->assertMatchesRegularExpression('/^PRO-\d{4}-\d{6}$/', $number);
        $this->assertNotSame('USER-SUPPLIED-NUMBER', $number);
        $this->assertSame('Laptop', $response->json('data.items.0.item_name'));
        $this->assertSame('16GB RAM', $response->json('data.items.0.specification'));
        $this->assertEquals(2, (float) $response->json('data.items.0.quantity'));
        $this->assertSame('piece', $response->json('data.items.0.unit'));
        $this->assertDatabaseHas('supplier_quotations', ['id' => $response->json('data.id'), 'quotation_number' => $number]);
    }

    public function test_batch_proformas_use_guarded_requisition_items_and_supplier_prices(): void
    {
        $existingTender = $this->tender();
        $requisition = $existingTender->requisition;
        $requisitionItem = $requisition->items()->firstOrFail();
        $existingTender->items()->delete();
        $existingTender->delete();
        [, $firstSupplier] = $this->supplier('batch-one@example.test');
        [, $secondSupplier] = $this->supplier('batch-two@example.test');
        $procurement = $this->user('batch-creator@example.test', 'procurement_officer');
        $procurement->update(['department_id' => $requisition->department_id]);

        $this->actingAs($procurement)->getJson('/admin/purchase-requisitions/'.$requisition->id)
            ->assertOk()
            ->assertJsonPath('items.0.specification', $requisitionItem->specification)
            ->assertJsonMissingPath('items.0.estimated_unit_price')
            ->assertJsonMissingPath('items.0.estimated_total');

        $response = $this->actingAs($procurement)->postJson('/admin/supplier-quotations/batch', [
            'purchase_requisition_id' => $requisition->id,
            'offers' => [
                [
                    'supplier_id' => $firstSupplier->id,
                    'prices' => [['purchase_requisition_item_id' => $requisitionItem->id, 'unit_price' => 250000]],
                ],
                [
                    'supplier_id' => $secondSupplier->id,
                    'prices' => [['purchase_requisition_item_id' => $requisitionItem->id, 'unit_price' => 275000]],
                ],
            ],
        ])->assertCreated()->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $quotation) {
            $this->assertMatchesRegularExpression('/^PRO-\d{4}-\d{6}$/', $quotation['quotation_number']);
            $this->assertSame($requisitionItem->item_name, $quotation['items'][0]['item_name']);
            $this->assertSame($requisitionItem->specification, $quotation['items'][0]['specification']);
            $this->assertEquals((float) $requisitionItem->quantity, (float) $quotation['items'][0]['quantity']);
            $this->assertSame($requisitionItem->unit, $quotation['items'][0]['unit']);
        }

        $this->actingAs($procurement)->postJson('/admin/supplier-quotations/batch', [
            'purchase_requisition_id' => $requisition->id,
            'offers' => [['supplier_id' => $firstSupplier->id, 'prices' => []]],
        ])->assertUnprocessable();
    }

    public function test_procurement_can_close_only_after_deadline_and_award_a_compliant_bid(): void
    {
        Notification::fake();
        [$winnerUser, $winner] = $this->supplier('winner@example.test');
        [, $other] = $this->supplier('other-bidder@example.test');
        $reviewer = $this->user('award-reviewer@example.test', 'procurement_officer');
        $tender = $this->tender();
        $reviewer->update(['department_id' => $tender->requisition->department_id]);

        $this->actingAs($reviewer)->postJson('/admin/tenders/'.$tender->id.'/close')
            ->assertStatus(422)->assertJsonPath('message', 'Bidding cannot be closed before the submission deadline.');

        $tender->update(['submission_deadline' => now()->subMinute()]);
        $this->actingAs($reviewer)->postJson('/admin/tenders/'.$tender->id.'/close')->assertOk();

        $winnerResponse = $this->submittedResponse($tender, $winner, 'WIN-01', 500000);
        $otherResponse = $this->submittedResponse($tender, $other, 'OTHER-01', 600000);
        foreach ([$winnerResponse, $otherResponse] as $bid) {
            $this->actingAs($reviewer)->postJson('/admin/tender-responses/'.$bid->id.'/compliance', [
                'decision' => 'compliant', 'comments' => 'Mandatory bid requirements met.',
            ])->assertOk();
        }

        $this->actingAs($reviewer)->postJson('/admin/tenders/'.$tender->id.'/responses/'.$winnerResponse->id.'/award', [
            'comments' => 'Best evaluated responsive bid.',
        ])->assertOk();

        $this->assertDatabaseHas('tenders', ['id' => $tender->id, 'status' => Tender::STATUS_AWARDED, 'winning_tender_response_id' => $winnerResponse->id]);
        $this->assertDatabaseHas('tender_responses', ['id' => $winnerResponse->id, 'award_status' => 'winner']);
        $this->assertDatabaseHas('tender_responses', ['id' => $otherResponse->id, 'award_status' => 'unsuccessful']);
        $this->assertDatabaseHas('purchase_requisitions', ['id' => $tender->purchase_requisition_id, 'status' => PurchaseRequisition::STATUS_QUOTATIONS_READY]);
        Notification::assertSentTo($winnerUser, TenderAwardedNotification::class);

        $requester = $tender->requisition->requester;
        $this->actingAs($requester)->getJson('/admin/purchase-requisitions/'.$tender->purchase_requisition_id)
            ->assertOk()
            ->assertJsonCount(2, 'supplier_options')
            ->assertJsonFragment(['name' => $winner->name, 'is_tender_winner' => true, 'award_status' => 'winner'])
            ->assertJsonFragment(['name' => $other->name, 'award_status' => 'unsuccessful']);
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

    private function submittedResponse(Tender $tender, Supplier $supplier, string $quotationNumber, float $amount): TenderResponse
    {
        $response = TenderResponse::create([
            'tender_id' => $tender->id,
            'supplier_id' => $supplier->id,
            'receipt_number' => 'BID-'.uniqid(),
            'quotation_number' => $quotationNumber,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
            'currency' => 'TZS',
            'subtotal' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'status' => 'submitted',
            'submitted_at' => now()->subHour(),
        ]);
        $response->items()->create([
            'tender_item_id' => $tender->items->first()->id,
            'unit_price' => $amount / (float) $tender->items->first()->quantity,
            'line_total' => $amount,
        ]);

        return $response;
    }

    private function user(string $email, string $role): User
    {
        $user = User::firstOrCreate(['email' => $email], ['name' => $email, 'first_name' => 'Portal', 'last_name' => 'User', 'password' => 'password', 'is_active' => true]);
        $user->syncRoles([$role]);

        return $user;
    }
}
