<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoTenderPortalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $requester = User::where('email', 'requester@hq.test')->firstOrFail();
            $procurement = User::where('email', 'procurement@hq.test')->firstOrFail();
            $lineManager = $requester->lineManager()->firstOrFail();
            $ict = SupplierCategory::where('code', 'ICT')->firstOrFail();
            $office = SupplierCategory::where('code', 'OFFICE')->firstOrFail();

            $requisition = PurchaseRequisition::updateOrCreate(
                ['requisition_number' => 'PR-DEMO-2026-000001'],
                [
                    'business_entity_id' => $requester->department->business_entity_id,
                    'department_id' => $requester->department_id,
                    'requester_id' => $requester->id,
                    'line_manager_id' => $lineManager->id,
                    'supplier_category_id' => $ict->id,
                    'required_date' => now()->addDays(35)->toDateString(),
                    'purpose' => 'Replace end-of-life workstations for the operations team.',
                    'estimated_amount' => 24800000,
                    'committed_amount' => 24800000,
                    'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                    'submitted_at' => now()->subDays(7),
                    'approved_at' => now()->subDays(5),
                ],
            );

            $items = collect([
                ['item_name' => 'Business laptop', 'specification' => 'Intel Core i7 or equivalent, 16 GB RAM, 512 GB SSD, 14-inch display, Windows 11 Pro, three-year warranty', 'quantity' => 8, 'unit' => 'piece', 'estimated_unit_price' => 2450000],
                ['item_name' => 'USB-C docking station', 'specification' => 'Dual-display support, minimum 90W power delivery, Ethernet and USB ports', 'quantity' => 8, 'unit' => 'piece', 'estimated_unit_price' => 420000],
                ['item_name' => 'Line-interactive UPS', 'specification' => 'Minimum 1200VA, automatic voltage regulation and replaceable battery', 'quantity' => 8, 'unit' => 'piece', 'estimated_unit_price' => 230000],
            ])->map(function (array $item) use ($requisition) {
                return $requisition->items()->updateOrCreate(
                    ['item_name' => $item['item_name']],
                    [...$item, 'estimated_total' => $item['quantity'] * $item['estimated_unit_price']],
                );
            });

            $tender = Tender::updateOrCreate(
                ['tender_number' => 'RFQ-2026-DEMO001'],
                [
                    'purchase_requisition_id' => $requisition->id,
                    'supplier_category_id' => $ict->id,
                    'title' => 'Supply and Delivery of Business Laptops and Accessories',
                    'public_summary' => 'Qualified ICT suppliers are invited to quote for business laptops, docking stations and power-protection equipment, including delivery and manufacturer warranty.',
                    'tender_type' => 'rfq',
                    'visibility' => 'public',
                    'publication_at' => now()->subDay(),
                    'submission_deadline' => now()->addDays(14)->setTime(16, 0),
                    'expected_delivery_date' => now()->addDays(35)->toDateString(),
                    'delivery_location' => 'Head Office, Dar es Salaam',
                    'contact_email' => 'procurement@example.co.tz',
                    'contact_phone' => '+255 22 000 0000',
                    'eligibility_requirements' => "Valid business licence and TIN certificate\nManufacturer authorisation or distributor evidence\nAbility to provide a minimum three-year warranty\nDelivery within 21 calendar days",
                    'submission_instructions' => "Log in to the Supplier Portal\nComplete pricing for every listed item\nUpload a signed proforma or quotation\nSubmit before the stated deadline",
                    'terms_and_conditions' => 'Prices must be quoted in TZS and remain valid for at least 30 days. The organisation may accept or reject any quotation according to the approved procurement process.',
                    'status' => Tender::STATUS_PUBLISHED,
                    'created_by' => $procurement->id,
                    'published_by' => User::where('email', 'gm@hq.test')->value('id'),
                    'published_at' => now()->subDay(),
                ],
            );

            foreach ($items as $item) {
                $tender->items()->updateOrCreate(
                    ['purchase_requisition_item_id' => $item->id],
                    ['item_name' => $item->item_name, 'specification' => $item->specification, 'quantity' => $item->quantity, 'unit' => $item->unit],
                );
            }

            PurchaseRequisition::updateOrCreate(
                ['requisition_number' => 'PR-DEMO-2026-000002'],
                [
                    'business_entity_id' => $requester->department->business_entity_id,
                    'department_id' => $requester->department_id,
                    'requester_id' => $requester->id,
                    'line_manager_id' => $lineManager->id,
                    'supplier_category_id' => $office->id,
                    'required_date' => now()->addDays(45)->toDateString(),
                    'purpose' => 'Furnish the new customer service workspace.',
                    'estimated_amount' => 12500000,
                    'committed_amount' => 12500000,
                    'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                    'submitted_at' => now()->subDays(4),
                    'approved_at' => now()->subDays(2),
                ],
            )->items()->updateOrCreate(
                ['item_name' => 'Ergonomic office chair'],
                ['specification' => 'Adjustable lumbar support, arm rests and five-star base', 'quantity' => 20, 'unit' => 'piece', 'estimated_unit_price' => 625000, 'estimated_total' => 12500000],
            );

            $supplierUser = User::updateOrCreate(
                ['email' => 'demo.supplier@example.com'],
                [
                    'name' => 'Demo Supplier', 'first_name' => 'Demo', 'last_name' => 'Supplier',
                    'job_title' => 'Sales Manager', 'phone' => '+255 713 555 010',
                    'email_verified_at' => now(), 'is_active' => true,
                    'password' => Hash::make('Supplier@123'),
                ],
            );
            Role::findOrCreate('supplier', 'web');
            $supplierUser->syncRoles(['supplier']);

            $supplier = Supplier::updateOrCreate(
                ['code' => 'SUP-DEMO-001'],
                [
                    'user_id' => $supplierUser->id,
                    'application_reference' => 'SUP-APP-2026-DEMO1',
                    'name' => 'TechSource Tanzania Limited',
                    'trading_name' => 'TechSource TZ',
                    'contact_person' => 'Demo Supplier',
                    'contact_position' => 'Sales Manager',
                    'email' => 'demo.supplier@example.com',
                    'phone' => '+255 713 555 010',
                    'address' => 'Mikocheni, Dar es Salaam',
                    'region' => 'Dar es Salaam',
                    'country' => 'Tanzania',
                    'tax_number' => 'TIN-DEMO-001',
                    'registration_number' => 'BRELA-DEMO-001',
                    'supplier_type' => 'distributor',
                    'products_services' => 'Business computers, accessories, networking equipment and technical support.',
                    'portal_status' => 'approved',
                    'submitted_at' => now()->subMonths(2),
                    'verified_at' => now()->subMonth(),
                    'verified_by' => User::where('email', 'gm@hq.test')->value('id'),
                    'is_active' => true,
                ],
            );
            $supplier->categories()->syncWithoutDetaching([$ict->id, $office->id]);

            if (! ActivityLog::where('action', 'demo_tender.seeded')->where('subject_type', Tender::class)->where('subject_id', $tender->id)->exists()) {
                ActivityLog::record($procurement, 'demo_tender.seeded', $tender, [], ['tender_number' => $tender->tender_number]);
            }
        });

        $this->command?->info('Demo tender portal data is ready.');
    }
}
