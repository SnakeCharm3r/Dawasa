<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierDocument;

class SupplierComplianceService
{
    public const REQUIRED_DOCUMENTS = [
        'certificate_of_incorporation_or_business_registration',
        'business_license',
        'tin_certificate',
        'tax_clearance_certificate',
    ];

    private const DOCUMENT_ALIASES = [
        'certificate_of_incorporation_or_business_registration' => ['certificate_of_incorporation_or_business_registration', 'incorporation_certificate'],
        'business_license' => ['business_license', 'business_licence'],
        'tin_certificate' => ['tin_certificate'],
        'vat_certificate' => ['vat_certificate'],
        'tax_clearance_certificate' => ['tax_clearance_certificate', 'tax_clearance'],
        'tmda_or_regulatory_license' => ['tmda_or_regulatory_license'],
    ];

    public function assess(Supplier $supplier, bool $persist = true): array
    {
        $supplier->loadMissing('documents');

        // Existing manually-maintained suppliers pre-date the portal document workflow.
        // Keep those approved records operational until they are resubmitted for verification.
        if ($supplier->portal_status === 'approved' && $supplier->is_active && ! $supplier->submitted_at && $supplier->documents->isEmpty()) {
            $legacy = [
                'status' => 'complete', 'award_eligibility' => 'eligible', 'reason' => null,
                'required_count' => 0, 'valid_count' => 0, 'score' => 100,
                'missing_documents' => [], 'expired_documents' => [], 'rejected_documents' => [],
                'expiring_documents' => ['30' => [], '60' => [], '90' => []],
            ];
            if ($persist) {
                $supplier->forceFill(['compliance_status' => 'complete', 'award_eligibility' => 'eligible', 'restriction_reason' => null])->saveQuietly();
            }
            return $legacy;
        }

        $required = self::REQUIRED_DOCUMENTS;

        if ($supplier->vat_registered) {
            $required[] = 'vat_certificate';
        }

        if ($supplier->regulated_supplier) {
            $required[] = 'tmda_or_regulatory_license';
        }

        $missing = [];
        $expired = [];
        $rejected = [];
        $expiring = ['30' => [], '60' => [], '90' => []];
        $valid = 0;

        foreach ($required as $type) {
            $documents = $supplier->documents->filter(fn (SupplierDocument $document) => in_array($document->document_type, self::DOCUMENT_ALIASES[$type] ?? [$type], true));
            if ($documents->isEmpty()) {
                $missing[] = $type;
                continue;
            }

            $verified = $documents->filter(fn (SupplierDocument $document) => $this->verificationStatus($document) === 'verified');
            if ($verified->isEmpty()) {
                if ($documents->contains(fn (SupplierDocument $document) => $this->verificationStatus($document) === 'rejected')) {
                    $rejected[] = $type;
                } else {
                    $missing[] = $type;
                }
                continue;
            }

            $usable = $verified->first(function (SupplierDocument $document) {
                $expiry = $document->expiry_date ?? $document->expires_at;
                return ! $expiry || $expiry->endOfDay()->isFuture();
            });

            if (! $usable) {
                $expired[] = $type;
                continue;
            }

            $valid++;
            $expiry = $usable->expiry_date ?? $usable->expires_at;
            if ($expiry && $expiry->lte(now()->addDays(90))) {
                $days = now()->startOfDay()->diffInDays($expiry, false);
                $bucket = $days <= 30 ? '30' : ($days <= 60 ? '60' : '90');
                $expiring[$bucket][] = $type;
            }
        }

        $hasExpiring = collect($expiring)->flatten()->isNotEmpty();
        $status = $rejected ? 'rejected_document' : ($expired ? 'expired' : ($missing ? 'incomplete' : ($hasExpiring ? 'expiring_soon' : 'complete')));
        $eligibility = 'eligible';
        $reason = null;

        if ($supplier->portal_status !== 'approved' || ! $supplier->is_active) {
            $eligibility = 'restricted';
            $reason = 'Supplier is not approved and active.';
        } elseif ($expired || $rejected || ($supplier->regulated_supplier && in_array('tmda_or_regulatory_license', $missing, true))) {
            $eligibility = 'blocked';
            $reason = 'Mandatory supplier compliance evidence is expired, rejected, or missing.';
        } elseif ($missing) {
            $eligibility = 'restricted';
            $reason = 'Mandatory supplier compliance evidence is incomplete.';
        }

        $override = $supplier->performanceOverrides()->where('expires_at', '>', now())->latest()->first();
        if ($override) {
            $eligibility = $override->eligibility;
            $reason = $override->reason;
        }

        $result = [
            'status' => $status,
            'award_eligibility' => $eligibility,
            'reason' => $reason,
            'required_count' => count($required),
            'valid_count' => $valid,
            'score' => count($required) ? round(($valid / count($required)) * 100, 2) : 100,
            'missing_documents' => $missing,
            'expired_documents' => $expired,
            'rejected_documents' => $rejected,
            'expiring_documents' => $expiring,
        ];

        if ($persist) {
            $supplier->forceFill([
                'compliance_status' => $status,
                'award_eligibility' => $eligibility,
                'restriction_reason' => $reason,
                'restriction_expires_at' => $override?->expires_at,
            ])->saveQuietly();
        }

        return $result;
    }

    public function canParticipate(Supplier $supplier): bool
    {
        return $this->assess($supplier)['award_eligibility'] === 'eligible';
    }

    private function verificationStatus(SupplierDocument $document): string
    {
        return $document->verification_status ?: ($document->status === 'pending_verification' ? 'pending' : $document->status);
    }
}
