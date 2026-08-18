"use client";

import { useAuth } from "@/components/auth-provider";
import { EmptyState, LoadingState, StatusBadge } from "@/components/ui/portal-ui";
import { api, ApiError } from "@/lib/api";
import { formatDate, formatMoney, statusLabel } from "@/lib/formatters";
import { AlertTriangle, ArrowLeft, Building2, FileCheck2, Globe2, MapPin, ShieldCheck, Truck, UserRound } from "lucide-react";
import Link from "next/link";
import { useCallback, useEffect, useState } from "react";

type Category = { id: number; name: string; code: string };
type Document = { id: number; document_type: string; document_number: string | null; issue_date: string | null; expiry_date: string | null; expires_at: string | null; verification_status: string | null; status: string; verification_notes: string | null; original_filename: string | null; original_name: string };
type Evaluation = { grade: string; overall_score: string; delivery_score: string; quality_score: string; compliance_score: string; calculated_at: string };
type Incident = { id: number; incident_type: string; severity: string; description: string; occurred_at: string; resolved_at: string | null };
type Quotation = { id: number; quotation_number: string; total_amount: string; valid_until: string | null; status: string };
type Compliance = { status: string; award_eligibility: string; reason: string | null; score: number; missing_documents: string[]; expired_documents: string[]; rejected_documents: string[]; expiring_documents: Record<string, string[]> };
type Supplier = {
  id: number; name: string; legal_name: string | null; trading_name: string | null; code: string; application_reference: string | null;
  portal_status: string; compliance_status: string; award_eligibility: string; is_active: boolean; is_preferred: boolean; supplier_type: string | null;
  registration_number: string | null; brela_registration_number: string | null; incorporation_or_compliance_number: string | null;
  business_license_number: string | null; business_license_issuing_authority: string | null; business_license_expiry_date: string | null;
  tin_number: string | null; tax_number: string | null; vat_registered: boolean; vat_registration_number: string | null;
  tax_clearance_number: string | null; tax_clearance_expiry_date: string | null;
  primary_contact_name: string | null; contact_person: string | null; primary_contact_position: string | null; primary_contact_phone: string | null;
  primary_contact_email: string | null; alternate_contact_name: string | null; alternate_contact_phone: string | null;
  physical_office_address: string | null; building_plot_street: string | null; ward: string | null; district: string | null; region: string | null; country: string | null; postal_address: string | null; website: string | null;
  products_services: string | null; manufacturer_or_distributor_status: string | null; years_in_operation: number | null; delivery_coverage_areas: string | null; quality_management_notes: string | null; regulated_supplier: boolean;
  review_comments: string | null; submitted_at: string | null; verified_at: string | null; created_at: string; updated_at: string;
  categories: Category[]; documents: Document[]; current_performance: Evaluation | null; performance_incidents: Incident[]; quotations: Quotation[]; compliance_assessment: Compliance;
};

export function SupplierDetail({ supplierId }: { supplierId: string }) {
  const { hasRole } = useAuth();
  const [supplier, setSupplier] = useState<Supplier | null>(null);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError("");
    try {
      const response = await api<{ data: Supplier }>(`admin/suppliers/${supplierId}`);
      setSupplier(response.data);
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to load this supplier.");
    }
  }, [supplierId]);

  useEffect(() => { void load(); }, [load]);

  async function changeStatus() {
    if (!supplier) return;
    const action = supplier.is_active ? "deactivate" : "activate";
    const reason = window.prompt(`Enter the reason to ${supplier.is_active ? "suspend" : "reactivate"} this supplier.`);
    if (!reason?.trim()) return;
    setBusy(true);
    setNotice("");
    try {
      const result = await api<{ message: string }>(`admin/suppliers/${supplier.id}/${action}`, { method: "PATCH", body: JSON.stringify({ reason: reason.trim() }) });
      setNotice(result.message);
      await load();
    } catch (caught) {
      setNotice(caught instanceof ApiError ? caught.message : "The supplier status could not be changed.");
    } finally {
      setBusy(false);
    }
  }

  if (error && !supplier) return <div className="supplier-detail-state"><AlertTriangle size={28} /><h1>Supplier unavailable</h1><p>{error}</p><Link className="secondary-button" href="/suppliers"><ArrowLeft size={16} />Back to suppliers</Link></div>;
  if (!supplier) return <LoadingState label="Loading supplier details" />;

  const performance = supplier.current_performance;
  const compliance = supplier.compliance_assessment;
  const canActivate = hasRole("super_admin", "procurement_officer", "gm", "ceo");
  const canSuspend = hasRole("super_admin", "gm", "ceo");
  const canChangeStatus = supplier.is_active ? canSuspend : canActivate;

  return <div className="page-stack supplier-detail-page">
    <Link className="detail-back-link" href="/suppliers"><ArrowLeft size={16} />Back to suppliers</Link>
    <header className="supplier-detail-header">
      <div><span className="eyebrow">Supplier record</span><h1>{supplier.name}</h1><p>{supplier.code} · {supplier.application_reference ?? "No application reference"}</p></div>
      <div className="supplier-detail-header-actions"><StatusBadge status={supplier.portal_status} />{canChangeStatus && <button className={supplier.is_active ? "danger-button" : "primary-button"} disabled={busy} onClick={() => void changeStatus()}>{supplier.is_active ? "Suspend supplier" : "Verify & activate"}</button>}</div>
    </header>
    {notice && <div className="inline-success">{notice}</div>}

    <section className="supplier-detail-summary">
      <Summary label="Compliance" value={<StatusBadge status={supplier.compliance_status} />} icon={ShieldCheck} />
      <Summary label="Award eligibility" value={<StatusBadge status={supplier.award_eligibility} />} icon={FileCheck2} />
      <Summary label="Performance grade" value={<strong>{performance?.grade === "insufficient_data" || !performance ? "Insufficient data" : performance.grade}</strong>} icon={Truck} />
      <Summary label="Overall score" value={<strong>{performance ? `${performance.overall_score}%` : "—"}</strong>} icon={AlertTriangle} />
      <Summary label="Account state" value={<strong>{supplier.is_active ? "Active" : "Inactive"}</strong>} icon={UserRound} />
    </section>

    <div className="supplier-detail-columns">
      <div className="supplier-detail-main">
        <DetailSection title="Business identity" icon={Building2}>
          <DetailGrid items={[
            ["Legal name", supplier.legal_name ?? supplier.name], ["Trading name", supplier.trading_name], ["Supplier type", supplier.supplier_type ? statusLabel(supplier.supplier_type) : null],
            ["Registration number", supplier.registration_number], ["BRELA registration", supplier.brela_registration_number], ["Incorporation / compliance", supplier.incorporation_or_compliance_number],
            ["TIN", supplier.tin_number ?? supplier.tax_number], ["VAT registered", supplier.vat_registered ? "Yes" : "No"], ["VAT number", supplier.vat_registration_number],
            ["Business licence", supplier.business_license_number], ["Licence authority", supplier.business_license_issuing_authority], ["Licence expiry", formatDate(supplier.business_license_expiry_date)],
            ["Tax clearance", supplier.tax_clearance_number], ["Tax clearance expiry", formatDate(supplier.tax_clearance_expiry_date)], ["Preferred supplier", supplier.is_preferred ? "Yes" : "No"],
          ]} />
        </DetailSection>

        <DetailSection title="Contacts and location" icon={MapPin}>
          <DetailGrid items={[
            ["Primary contact", supplier.primary_contact_name ?? supplier.contact_person], ["Position", supplier.primary_contact_position], ["Phone", supplier.primary_contact_phone],
            ["Email", supplier.primary_contact_email], ["Alternate contact", supplier.alternate_contact_name], ["Alternate phone", supplier.alternate_contact_phone],
            ["Office address", supplier.physical_office_address], ["Building / plot / street", supplier.building_plot_street], ["Ward", supplier.ward],
            ["District", supplier.district], ["Region", supplier.region], ["Country", supplier.country], ["Postal address", supplier.postal_address], ["Website", supplier.website],
          ]} />
        </DetailSection>

        <DetailSection title="Capability and approved categories" icon={Truck}>
          <DetailGrid items={[
            ["Products and services", supplier.products_services], ["Manufacturer / distributor", supplier.manufacturer_or_distributor_status], ["Years in operation", supplier.years_in_operation],
            ["Delivery coverage", supplier.delivery_coverage_areas], ["Regulated supplier", supplier.regulated_supplier ? "Yes" : "No"], ["Quality management", supplier.quality_management_notes],
          ]} />
          <div className="supplier-detail-categories">{supplier.categories.length ? supplier.categories.map((category) => <span key={category.id}>{category.name}<small>{category.code}</small></span>) : <p>No supplier categories selected.</p>}</div>
        </DetailSection>

        <DetailSection title="Compliance documents" icon={FileCheck2}>
          {supplier.documents.length === 0 ? <EmptyState title="No documents uploaded" copy="Supplier evidence will appear here after upload." /> : <div className="portal-table-wrap"><table className="portal-table"><thead><tr><th>Document</th><th>Number</th><th>Issue date</th><th>Expiry</th><th>Status</th><th>Review note</th></tr></thead><tbody>{supplier.documents.map((document) => <tr key={document.id}><td><a className="record-link" href={`/backend/admin/supplier-documents/${document.id}/download`}>{statusLabel(document.document_type)}<small>{document.original_filename ?? document.original_name}</small></a></td><td>{document.document_number ?? "—"}</td><td>{formatDate(document.issue_date)}</td><td>{formatDate(document.expiry_date ?? document.expires_at)}</td><td><StatusBadge status={document.verification_status ?? document.status} /></td><td>{document.verification_notes ?? "—"}</td></tr>)}</tbody></table></div>}
        </DetailSection>

        <DetailSection title="Sourcing history" icon={Globe2}>
          {supplier.quotations.length === 0 ? <EmptyState title="No proformas recorded" copy="Proformas associated with this supplier will appear here." /> : <div className="portal-table-wrap"><table className="portal-table"><thead><tr><th>Proforma</th><th>Valid until</th><th>Total</th><th>Status</th></tr></thead><tbody>{supplier.quotations.map((quotation) => <tr key={quotation.id}><td>{quotation.quotation_number}</td><td>{formatDate(quotation.valid_until)}</td><td>{formatMoney(quotation.total_amount)}</td><td><StatusBadge status={quotation.status} /></td></tr>)}</tbody></table></div>}
        </DetailSection>
      </div>

      <aside className="supplier-detail-aside">
        <section className="supplier-detail-card"><h2>Compliance readiness</h2><div className="supplier-detail-score"><strong>{compliance.score}%</strong><span>verified and valid</span></div><div className="supplier-compliance-meter"><i style={{ width: `${compliance.score}%` }} /></div><dl><ComplianceLine label="Missing" values={compliance.missing_documents} /><ComplianceLine label="Expired" values={compliance.expired_documents} /><ComplianceLine label="Rejected" values={compliance.rejected_documents} /><div><dt>Restriction reason</dt><dd>{compliance.reason ?? "No restriction"}</dd></div></dl></section>
        <section className="supplier-detail-card"><h2>Performance</h2>{performance ? <dl><div><dt>Grade</dt><dd>{statusLabel(performance.grade)}</dd></div><div><dt>Overall</dt><dd>{performance.overall_score}%</dd></div><div><dt>Delivery</dt><dd>{performance.delivery_score}%</dd></div><div><dt>Quality</dt><dd>{performance.quality_score}%</dd></div><div><dt>Compliance</dt><dd>{performance.compliance_score}%</dd></div><div><dt>Calculated</dt><dd>{formatDate(performance.calculated_at)}</dd></div></dl> : <p>No performance evaluation has been calculated.</p>}</section>
        <section className="supplier-detail-card"><h2>Open incidents</h2>{supplier.performance_incidents.filter((incident) => !incident.resolved_at).length === 0 ? <p>No open incidents.</p> : <div className="supplier-detail-incidents">{supplier.performance_incidents.filter((incident) => !incident.resolved_at).slice(0, 8).map((incident) => <article key={incident.id}><StatusBadge status={incident.severity} /><strong>{statusLabel(incident.incident_type)}</strong><p>{incident.description}</p><small>{formatDate(incident.occurred_at)}</small></article>)}</div>}</section>
        <section className="supplier-detail-card"><h2>Record timeline</h2><dl><div><dt>Submitted</dt><dd>{formatDate(supplier.submitted_at)}</dd></div><div><dt>Verified</dt><dd>{formatDate(supplier.verified_at)}</dd></div><div><dt>Created</dt><dd>{formatDate(supplier.created_at)}</dd></div><div><dt>Updated</dt><dd>{formatDate(supplier.updated_at)}</dd></div>{supplier.review_comments && <div><dt>Latest review note</dt><dd>{supplier.review_comments}</dd></div>}</dl></section>
      </aside>
    </div>
  </div>;
}

function Summary({ label, value, icon: Icon }: { label: string; value: React.ReactNode; icon: React.ComponentType<{ size?: number }> }) {
  return <article><Icon size={18} /><div><span>{label}</span>{value}</div></article>;
}

function DetailSection({ title, icon: Icon, children }: { title: string; icon: React.ComponentType<{ size?: number }>; children: React.ReactNode }) {
  return <section className="supplier-detail-card supplier-detail-section"><header><Icon size={19} /><h2>{title}</h2></header>{children}</section>;
}

function DetailGrid({ items }: { items: Array<[string, string | number | null]> }) {
  return <dl className="supplier-detail-grid">{items.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value === null || value === "" ? "—" : value}</dd></div>)}</dl>;
}

function ComplianceLine({ label, values }: { label: string; values: string[] }) {
  return <div><dt>{label}</dt><dd>{values.length ? values.map(statusLabel).join(", ") : "None"}</dd></div>;
}
