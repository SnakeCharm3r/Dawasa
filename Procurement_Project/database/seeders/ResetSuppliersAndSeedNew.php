<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\User;
use App\Services\SupplierComplianceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetSuppliersAndSeedNew extends Seeder
{
    private const SUPPLIERS = [
        [
            'code' => 'SUP-2026-001',
            'application_reference' => 'SUP-APP-2026-001',
            'name' => 'Mlimani Digital Solutions Limited',
            'trading_name' => 'Mlimani Digital',
            'email' => 'portal@mlimanidigital.co.tz',
            'phone' => '+255 754 210 101',
            'website' => 'https://mlimanidigital.co.tz',
            'registration_number' => 'BRELA-154210001',
            'tin_number' => '154-210-001',
            'vat_number' => '40-154210-A',
            'business_license_number' => 'BL-DSM-2026-1001',
            'tax_clearance_number' => 'TCC-2026-1001',
            'address' => 'Plot 42, Ali Hassan Mwinyi Road, Dar es Salaam',
            'building_plot_street' => 'Plot 42, Ali Hassan Mwinyi Road',
            'ward' => 'Kivukoni',
            'district' => 'Ilala',
            'region' => 'Dar es Salaam',
            'postal_address' => 'P.O. Box 14820, Dar es Salaam',
            'contact_name' => 'Neema Mwakalinga',
            'contact_position' => 'Commercial Director',
            'alternate_contact_name' => 'Baraka Mushi',
            'alternate_phone' => '+255 713 210 102',
            'products_services' => 'Computers, servers, networking equipment, software licensing, installation and ICT support.',
            'manufacturer_status' => 'Authorised distributor and systems integrator',
            'years_in_operation' => 11,
            'coverage' => 'Tanzania mainland and Zanzibar',
            'quality_notes' => 'Documented warranty, installation, escalation and after-sales support procedures.',
            'categories' => ['ICT', 'OFFICE'],
        ],
        [
            'code' => 'SUP-2026-002',
            'application_reference' => 'SUP-APP-2026-002',
            'name' => 'Twiga General Traders Limited',
            'trading_name' => 'Twiga General Traders',
            'email' => 'portal@twigatraders.co.tz',
            'phone' => '+255 755 320 201',
            'website' => 'https://twigatraders.co.tz',
            'registration_number' => 'BRELA-164320002',
            'tin_number' => '164-320-002',
            'vat_number' => '40-164320-B',
            'business_license_number' => 'BL-DSM-2026-2002',
            'tax_clearance_number' => 'TCC-2026-2002',
            'address' => 'Nyerere Road, Vingunguti Industrial Area, Dar es Salaam',
            'building_plot_street' => 'Warehouse 18, Nyerere Road',
            'ward' => 'Vingunguti',
            'district' => 'Ilala',
            'region' => 'Dar es Salaam',
            'postal_address' => 'P.O. Box 7824, Dar es Salaam',
            'contact_name' => 'Asha Kweka',
            'contact_position' => 'Head of Sales and Distribution',
            'alternate_contact_name' => 'Joseph Mrema',
            'alternate_phone' => '+255 716 320 202',
            'products_services' => 'General consumables, office furniture, cleaning materials, safety supplies, transport and nationwide delivery.',
            'manufacturer_status' => 'Wholesaler and authorised distributor',
            'years_in_operation' => 15,
            'coverage' => 'All regions of Tanzania',
            'quality_notes' => 'Batch inspection, delivery tracking and documented replacement procedures.',
            'categories' => ['GOODS', 'OFFICE', 'LOGISTICS'],
        ],
        [
            'code' => 'SUP-2026-003',
            'application_reference' => 'SUP-APP-2026-003',
            'name' => 'Ujenzi na Ushauri Partners Limited',
            'trading_name' => 'Ujenzi na Ushauri Partners',
            'email' => 'portal@uup.co.tz',
            'phone' => '+255 756 430 301',
            'website' => 'https://uup.co.tz',
            'registration_number' => 'BRELA-174430003',
            'tin_number' => '174-430-003',
            'vat_number' => '40-174430-C',
            'business_license_number' => 'BL-DOD-2026-3003',
            'tax_clearance_number' => 'TCC-2026-3003',
            'address' => 'Chimwaga Road, Area C, Dodoma',
            'building_plot_street' => 'Plot 27, Chimwaga Road',
            'ward' => 'Area C',
            'district' => 'Dodoma Urban',
            'region' => 'Dodoma',
            'postal_address' => 'P.O. Box 2216, Dodoma',
            'contact_name' => 'Rehema Msuya',
            'contact_position' => 'Managing Partner',
            'alternate_contact_name' => 'Daniel Ngowi',
            'alternate_phone' => '+255 718 430 302',
            'products_services' => 'Building maintenance, minor works, engineering design, project supervision and procurement consultancy.',
            'manufacturer_status' => 'Professional services and works contractor',
            'years_in_operation' => 9,
            'coverage' => 'Central, Northern and Lake zones, with nationwide consulting coverage',
            'quality_notes' => 'Project quality plans, site safety controls and documented professional review procedures.',
            'categories' => ['WORKS', 'CONSULT'],
        ],
    ];

    public function run(): void
    {
        $reviewer = User::role('procurement_officer')->where('is_active', true)->first()
            ?? User::role('super_admin')->where('is_active', true)->firstOrFail();

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            $this->truncateSupplierRelatedTables();
            $this->removeSupplierUsers();
            Supplier::query()->forceDelete();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        DB::transaction(function () use ($reviewer): void {
            foreach (self::SUPPLIERS as $index => $profile) {
                $user = User::updateOrCreate(
                    ['email' => $profile['email']],
                    [
                        'name' => $profile['contact_name'],
                        'first_name' => str($profile['contact_name'])->before(' ')->toString(),
                        'last_name' => str($profile['contact_name'])->afterLast(' ')->toString(),
                        'job_title' => $profile['contact_position'],
                        'phone' => $profile['phone'],
                        'is_active' => true,
                        'email_verified_at' => now(),
                        'password' => 'password',
                    ],
                );
                $user->syncRoles(['supplier']);

                $supplier = Supplier::withTrashed()->firstOrNew(['code' => $profile['code']]);
                if ($supplier->trashed()) {
                    $supplier->restore();
                }

                $supplier->fill([
                    'user_id' => $user->id,
                    'application_reference' => $profile['application_reference'],
                    'name' => $profile['name'],
                    'legal_name' => $profile['name'],
                    'trading_name' => $profile['trading_name'],
                    'contact_person' => $profile['contact_name'],
                    'contact_position' => $profile['contact_position'],
                    'email' => $profile['email'],
                    'phone' => $profile['phone'],
                    'alternate_phone' => $profile['alternate_phone'],
                    'address' => $profile['address'],
                    'physical_office_address' => $profile['address'],
                    'building_plot_street' => $profile['building_plot_street'],
                    'ward' => $profile['ward'],
                    'district' => $profile['district'],
                    'region' => $profile['region'],
                    'country' => 'Tanzania',
                    'postal_address' => $profile['postal_address'],
                    'website' => $profile['website'],
                    'supplier_type' => 'limited_company',
                    'registration_number' => $profile['registration_number'],
                    'brela_registration_number' => $profile['registration_number'],
                    'incorporation_or_compliance_number' => $profile['registration_number'],
                    'tax_number' => $profile['tin_number'],
                    'tin_number' => $profile['tin_number'],
                    'vat_registered' => true,
                    'vat_number' => $profile['vat_number'],
                    'vat_registration_number' => $profile['vat_number'],
                    'business_license_number' => $profile['business_license_number'],
                    'business_license_issuing_authority' => 'Business Registrations and Licensing Agency',
                    'business_license_expiry_date' => now()->addYears(2)->toDateString(),
                    'tax_clearance_number' => $profile['tax_clearance_number'],
                    'tax_clearance_expiry_date' => now()->addYear()->toDateString(),
                    'primary_contact_name' => $profile['contact_name'],
                    'primary_contact_position' => $profile['contact_position'],
                    'primary_contact_phone' => $profile['phone'],
                    'primary_contact_email' => $profile['email'],
                    'alternate_contact_name' => $profile['alternate_contact_name'],
                    'alternate_contact_phone' => $profile['alternate_phone'],
                    'products_services' => $profile['products_services'],
                    'manufacturer_details' => $profile['manufacturer_status'],
                    'manufacturer_or_distributor_status' => $profile['manufacturer_status'],
                    'years_in_operation' => $profile['years_in_operation'],
                    'delivery_coverage_areas' => $profile['coverage'],
                    'quality_management_notes' => $profile['quality_notes'],
                    'regulated_supplier' => false,
                    'portal_status' => 'approved',
                    'review_comments' => 'Demo supplier profile and KYC reviewed and approved by Procurement.',
                    'submitted_at' => now()->subDays(10 + $index),
                    'verified_at' => now(),
                    'verified_by' => $reviewer->id,
                    'status_changed_by' => $reviewer->id,
                    'status_changed_at' => now(),
                    'is_active' => true,
                    'is_preferred' => $index === 0,
                ]);
                $supplier->save();

                $categoryIds = SupplierCategory::query()
                    ->whereIn('code', $profile['categories'])
                    ->pluck('id');
                if ($categoryIds->count() !== count($profile['categories'])) {
                    throw new \RuntimeException('A required supplier category is missing for '.$supplier->name.'.');
                }
                $supplier->categories()->sync($categoryIds);

                $this->seedVerifiedDocuments($supplier, $profile, $reviewer);
                $assessment = app(SupplierComplianceService::class)->assess($supplier->fresh('documents'));
                if ($assessment['status'] !== 'complete' || $assessment['award_eligibility'] !== 'eligible') {
                    throw new \RuntimeException($supplier->name.' was not seeded as eligible: '.json_encode($assessment));
                }
            }
        });
    }

    private function truncateSupplierRelatedTables(): void
    {
        $tables = [
            'supplier_performance_overrides',
            'supplier_performance_incidents',
            'supplier_performance_evaluations',
            'tender_response_documents',
            'tender_response_items',
            'tender_responses',
            'tender_invitations',
            'tender_items',
            'tenders',
            'supplier_documents',
            'supplier_category_supplier',
            'supplier_quotation_items',
            'supplier_quotations',
            'supplier_invoice_items',
            'supplier_invoices',
            'payment_approvals',
            'payment_vouchers',
            'goods_receipt_note_items',
            'goods_receipt_notes',
            'purchase_order_items',
            'purchase_order_approvals',
            'purchase_orders',
            'suppliers',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
    }

    private function removeSupplierUsers(): void
    {
        User::role('supplier')->where('is_active', true)->each(function (User $user): void {
            $user->delete();
        });
    }

    private function seedVerifiedDocuments(Supplier $supplier, array $profile, User $reviewer): void
    {
        $documents = [
            'certificate_of_incorporation_or_business_registration' => [$profile['registration_number'], null],
            'business_license' => [$profile['business_license_number'], now()->addYears(2)->toDateString()],
            'tin_certificate' => [$profile['tin_number'], null],
            'vat_certificate' => [$profile['vat_number'], null],
            'tax_clearance_certificate' => [$profile['tax_clearance_number'], now()->addYear()->toDateString()],
        ];

        foreach ($documents as $type => [$number, $expiryDate]) {
            $path = 'supplier-documents/'.$supplier->id.'/verified-'.$type.'.pdf';
            Storage::disk('local')->put($path, $this->pdf('Verified supplier document', $type.' - '.$number));
            $supplier->documents()->updateOrCreate(
                ['document_type' => $type],
                [
                    'document_number' => $number,
                    'issue_date' => now()->subYear()->toDateString(),
                    'expiry_date' => $expiryDate,
                    'expires_at' => $expiryDate,
                    'original_name' => $type.'.pdf',
                    'original_filename' => $type.'.pdf',
                    'storage_path' => $path,
                    'file_path' => $path,
                    'mime_type' => 'application/pdf',
                    'size' => Storage::disk('local')->size($path),
                    'status' => 'verified',
                    'verification_status' => 'verified',
                    'review_comments' => 'Verified by Procurement for the demo sourcing register.',
                    'verification_notes' => 'Verified by Procurement for the demo sourcing register.',
                    'reviewed_by' => $reviewer->id,
                    'verified_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'verified_at' => now(),
                ],
            );
        }
    }

    private function pdf(string $title, string $line): string
    {
        $escape = fn (string $value) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
        $stream = 'BT /F1 14 Tf 72 740 Td ('.$escape($title).') Tj 0 -24 Td /F1 10 Tf ('.$escape($line).') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream).' >> stream'."\n".$stream."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
