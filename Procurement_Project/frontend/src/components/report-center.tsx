"use client";

import { useAuth } from "@/components/auth-provider";
import { useEntityScope } from "@/components/entity-scope-provider";
import { api, ApiError, collectionFrom, valueAt } from "@/lib/api";
import type { JsonRecord } from "@/lib/types";
import { BarChart3, Download, LoaderCircle, RefreshCw } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";

type ReportDefinition = {
  label: string;
  endpoint: string;
  columns: Array<{ label: string; key: string; format?: "money" | "date" | "status" }>;
};

const reports: ReportDefinition[] = [
  { label: "Requisition register", endpoint: "requisition-register", columns: [{ label: "Requisition", key: "requisition_number" }, { label: "Entity", key: "business_entity.name" }, { label: "Department", key: "department.name" }, { label: "Requester", key: "requester.name" }, { label: "Created", key: "created_at", format: "date" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Requisition approval turnaround", endpoint: "requisition-approval-turnaround", columns: [{ label: "Requisition", key: "requisition_number" }, { label: "Submitted", key: "submitted_at", format: "date" }, { label: "Approved", key: "approved_at", format: "date" }, { label: "Turnaround days", key: "turnaround_days" }] },
  { label: "Sourcing quotation comparison", endpoint: "sourcing-quotation-comparison", columns: [{ label: "Quotation", key: "quotation_number" }, { label: "Supplier", key: "supplier.name" }, { label: "Requisition", key: "purchase_requisition.requisition_number" }, { label: "Total", key: "total_amount", format: "money" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Non-lowest-price recommendations", endpoint: "non-lowest-price-recommendation", columns: [{ label: "Requisition", key: "purchase_requisition.requisition_number" }, { label: "Selected supplier", key: "selected_quotation.supplier.name" }, { label: "Reason", key: "non_lowest_price_reason" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Supplier quotation awards", endpoint: "supplier-quotation-award", columns: [{ label: "LPO", key: "purchase_order_number" }, { label: "Supplier", key: "supplier.name" }, { label: "Requisition", key: "requisition.requisition_number" }, { label: "Total", key: "total_amount", format: "money" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Purchase order register", endpoint: "purchase-order-register", columns: [{ label: "LPO", key: "purchase_order_number" }, { label: "Supplier", key: "supplier.name" }, { label: "Entity", key: "business_entity.name" }, { label: "Total", key: "total_amount", format: "money" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Purchase order status", endpoint: "purchase-order-status", columns: [{ label: "LPO", key: "purchase_order_number" }, { label: "Supplier", key: "supplier.name" }, { label: "Expected delivery", key: "expected_delivery_date", format: "date" }, { label: "Total", key: "total_amount", format: "money" }, { label: "Status", key: "status", format: "status" }] },
  { label: "GRN inspection", endpoint: "grn-inspection", columns: [{ label: "GRN", key: "grn_number" }, { label: "LPO", key: "purchase_order.purchase_order_number" }, { label: "Supplier", key: "supplier.name" }, { label: "Inspector", key: "inspected_by.name" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Supplier invoice variance", endpoint: "supplier-invoice-variance", columns: [{ label: "Invoice", key: "invoice_number" }, { label: "Supplier", key: "supplier.name" }, { label: "LPO", key: "purchase_order.purchase_order_number" }, { label: "Total", key: "total_amount", format: "money" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Payment voucher register", endpoint: "payment-voucher-register", columns: [{ label: "Voucher", key: "voucher_number" }, { label: "Supplier", key: "supplier.name" }, { label: "Prepared by", key: "prepared_by.name" }, { label: "Amount", key: "amount", format: "money" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Outstanding supplier liabilities", endpoint: "outstanding-supplier-liabilities", columns: [{ label: "Invoice", key: "invoice_number" }, { label: "Supplier", key: "supplier.name" }, { label: "Due", key: "due_date", format: "date" }, { label: "Outstanding", key: "outstanding_amount", format: "money" }, { label: "Status", key: "status", format: "status" }] },
  { label: "Budget commitments", endpoint: "budget-commitment", columns: [{ label: "Entity", key: "business_entity.name" }, { label: "Financial year", key: "financial_year.name" }, { label: "Type", key: "transaction_type", format: "status" }, { label: "Amount", key: "amount", format: "money" }, { label: "Recorded", key: "created_at", format: "date" }] },
  { label: "Procurement spend", endpoint: "procurement-spend", columns: [{ label: "Entity", key: "business_entity.name" }, { label: "Supplier", key: "supplier.name" }, { label: "Financial year", key: "financial_year.name" }, { label: "Total spend", key: "total", format: "money" }] },
  { label: "Procurement cycle time", endpoint: "procurement-cycle-time", columns: [{ label: "Requisition", key: "requisition_number" }, { label: "Submitted", key: "submitted_at", format: "date" }, { label: "Approved", key: "approved_at", format: "date" }, { label: "LPO issued", key: "purchase_order.issued_at", format: "date" }] },
  { label: "Supplier performance", endpoint: "supplier-performance", columns: [{ label: "Supplier", key: "supplier.name" }, { label: "LPOs", key: "total_pos" }, { label: "Total value", key: "total_value", format: "money" }, { label: "Completed", key: "completed_pos" }, { label: "Completion rate", key: "completion_rate" }] },
  { label: "Closure exceptions", endpoint: "closure-exception", columns: [{ label: "Requisition", key: "purchase_requisition.requisition_number" }, { label: "LPO", key: "purchase_order.purchase_order_number" }, { label: "Reason", key: "exception_reason" }, { label: "Closed by", key: "closed_by.name" }, { label: "Closed", key: "closed_at", format: "date" }] },
  { label: "Audit timeline", endpoint: "audit-timeline", columns: [{ label: "Event type", key: "type", format: "status" }, { label: "Reference", key: "model.requisition_number" }, { label: "Action", key: "model.action" }, { label: "Recorded", key: "model.created_at", format: "date" }] },
];

export function ReportCenter() {
  const { user } = useAuth();
  const { selectedEntityId } = useEntityScope();
  const [selected, setSelected] = useState(reports[0].endpoint);
  const [rows, setRows] = useState<JsonRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const report = useMemo(() => reports.find((item) => item.endpoint === selected) ?? reports[0], [selected]);
  const allowed = Boolean(user?.roles.includes("ceo") || user?.roles.some((role) => ["super_admin", "gm", "accountant", "procurement_officer", "auditor"].includes(role)));

  const load = useCallback(async () => {
    setLoading(true); setError("");
    try {
      const payload = await api<JsonRecord>(`admin/reports/${report.endpoint}?per_page=50${selectedEntityId ? `&business_entity_id=${selectedEntityId}` : ""}`);
      setRows(collectionFrom(payload).rows);
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to load this report."); setRows([]);
    } finally { setLoading(false); }
  }, [report, selectedEntityId]);

  useEffect(() => { if (allowed) void load(); }, [allowed, load]);
  if (!allowed) return <div className="not-found"><h1>Access restricted</h1><p>Your role does not include reporting access.</p></div>;

  return <div className="page-stack">
    <section className="page-heading"><div><p className="eyebrow">Organisation-wide oversight</p><h1>Procurement reports</h1><p>Open any operational, financial, supplier-performance, exception, or audit report.</p></div><button className="secondary-button" onClick={() => window.print()}><Download size={16} />Print / export PDF</button></section>
    <section className="register-band">
      <div className="register-toolbar"><label className="select-control"><BarChart3 size={16} /><select value={selected} onChange={(event) => setSelected(event.target.value)}>{reports.map((item) => <option value={item.endpoint} key={item.endpoint}>{item.label}</option>)}</select></label><button className="icon-button bordered" onClick={() => void load()} title="Refresh report"><RefreshCw size={17} className={loading ? "spin" : ""} /></button></div>
      {error && <div className="inline-error">{error}</div>}
      <div className="table-wrap"><table><thead><tr>{report.columns.map((column) => <th key={column.key}>{column.label}</th>)}</tr></thead><tbody>
        {loading ? <tr><td colSpan={report.columns.length}><div className="empty-state"><LoaderCircle className="spin" /><p>Loading {report.label.toLowerCase()}…</p></div></td></tr> : rows.map((row, index) => <tr key={String(row.id ?? index)}>{report.columns.map((column) => <td key={column.key}>{formatCell(valueAt(row, column.key), column.format)}</td>)}</tr>)}
        {!loading && rows.length === 0 && <tr><td colSpan={report.columns.length}><div className="empty-state"><BarChart3 size={23} /><h3>No report records</h3><p>No records currently match this report.</p></div></td></tr>}
      </tbody></table></div><footer className="table-footer"><span>{rows.length.toLocaleString()} records shown</span><strong>{report.label}</strong></footer>
    </section>
  </div>;
}

function formatCell(value: unknown, format?: "money" | "date" | "status") {
  if (value == null || value === "") return "-";
  if (format === "money") return `TZS ${Number(value).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
  if (format === "date") return new Date(String(value)).toLocaleDateString(undefined, { day: "2-digit", month: "short", year: "numeric" });
  if (format === "status") return <span className={`status status-${String(value).replaceAll("_", "-")}`}>{String(value).replaceAll("_", " ")}</span>;
  return String(value);
}
