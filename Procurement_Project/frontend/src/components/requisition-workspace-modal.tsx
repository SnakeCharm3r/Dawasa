"use client";

import { useAuth } from "@/components/auth-provider";
import { api, ApiError } from "@/lib/api";
import type { ActionSpec } from "@/lib/modules";
import type { JsonRecord } from "@/lib/types";
import { AlertTriangle, Building2, CalendarDays, Check, CircleDot, Clock3, FileText, LoaderCircle, Pencil, PiggyBank, Printer, UserRound, X } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

type Props = {
  item: JsonRecord;
  actions: ActionSpec[];
  close: () => void;
  act: (action: ActionSpec, item: JsonRecord) => void;
  edit: (item: JsonRecord) => void;
};

export function RequisitionWorkspaceModal({ item, actions, close, act, edit }: Props) {
  const { user } = useAuth();
  const [requisition, setRequisition] = useState(item);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError("");
    api<JsonRecord>(`admin/purchase-requisitions/${Number(item.id)}`)
      .then((response) => { if (active) setRequisition((response.data as JsonRecord | undefined) ?? response); })
      .catch((caught) => { if (active) setError(caught instanceof ApiError ? caught.message : "Unable to load the requisition workspace."); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [item]);

  const workspaceActions = useMemo(
    () => user ? actions.filter((action) => action.appliesTo ? action.appliesTo(requisition) : action.visible(requisition, user)) : [],
    [actions, requisition, user],
  );
  const items = (requisition.items as JsonRecord[] | undefined) ?? [];
  const approvals = (requisition.approvals as JsonRecord[] | undefined) ?? [];
  const activities = (requisition.activity_logs as JsonRecord[] | undefined) ?? [];
  const supplierOptions = (requisition.supplier_options as JsonRecord[] | undefined) ?? [];
  const tenderSummary = objectAt(requisition, "tender_summary");
  const requester = objectAt(requisition, "requester");
  const manager = objectAt(requisition, "line_manager");
  const entity = objectAt(requisition, "business_entity");
  const department = objectAt(requisition, "department");
  const estimatedTotal = Number(requisition.estimated_amount ?? items.reduce((sum, line) => sum + Number(line.estimated_total ?? 0), 0));
  const budget = objectAt(requisition, "budget_check");
  const hasFullBudget = Object.prototype.hasOwnProperty.call(budget, "total_allocated_budget");
  const stage = currentStage(String(requisition.status ?? "draft"), manager);
  const canEdit = ["draft", "returned"].includes(String(requisition.status)) && Number(requester.id) === user?.id;

  return (
    <div className="requisition-workspace-backdrop" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) close(); }}>
      <section className="requisition-workspace-modal" role="dialog" aria-modal="true" aria-labelledby="requisition-workspace-title">
        <header className="requisition-workspace-toolbar">
          <div><span>Purchase requisition</span><strong>{String(requisition.requisition_number ?? `Request #${requisition.id}`)}</strong></div>
          <div>
            {canEdit && <button className="secondary-button compact" onClick={() => edit(requisition)}><Pencil size={15} />Edit draft</button>}
            <button className="icon-button bordered" onClick={() => window.print()} title="Print purchase requisition"><Printer size={17} /></button>
            <button className="icon-button" onClick={close} title="Close requisition"><X size={19} /></button>
          </div>
        </header>

        {loading ? <div className="requisition-workspace-loading"><LoaderCircle className="spin" size={22} />Loading requisition workspace...</div> : error ? <div className="requisition-workspace-error">{error}</div> : (
          <div className="requisition-workspace-content">
            <main className="requisition-document">
              <header className="requisition-document-header">
                <div>
                  <p className="eyebrow">Internal purchase request</p>
                  <h1 id="requisition-workspace-title">Purchase requisition</h1>
                  <span className={`status status-${String(requisition.status ?? "draft").replaceAll("_", "-")}`}>{humanize(String(requisition.status ?? "draft"))}</span>
                </div>
                <div className="requisition-number-block"><span>Requisition number</span><strong>{String(requisition.requisition_number ?? "Draft")}</strong></div>
              </header>

              <section className="requisition-facts">
                <Fact icon={<Building2 size={16} />} label="Business entity" value={String(entity.name ?? "-")} detail={String(department.name ?? "Department not set")} />
                <Fact icon={<UserRound size={16} />} label="Requested by" value={String(requester.name ?? "-")} detail="Request owner" />
                <Fact icon={<UserRound size={16} />} label="Line manager" value={String(manager.name ?? "Not assigned")} detail="First approval authority" />
                <Fact icon={<CalendarDays size={16} />} label="Required date" value={formatDate(requisition.required_date)} detail={`Created ${formatDate(requisition.created_at)}`} />
              </section>

              <section className="requisition-purpose">
                <span>Business purpose</span>
                <strong>{String(requisition.purpose ?? "No purpose supplied")}</strong>
                {Boolean(requisition.estimate_difference_reason) && <p>{String(requisition.estimate_difference_reason)}</p>}
              </section>

              <section className="requisition-items-section">
                <div className="requisition-section-heading"><h2>Requested items</h2><span>{items.length} line item{items.length === 1 ? "" : "s"}</span></div>
                <div className="requisition-items-table"><table><thead><tr><th>#</th><th>Product</th><th>Product description</th><th>U.O.M</th><th className="numeric">Quantity</th><th className="numeric">Unit estimate</th><th className="numeric">Subtotal</th></tr></thead><tbody>
                  {items.map((line, index) => <tr key={String(line.id ?? index)}><td>{index + 1}</td><td><strong>{String(line.item_name ?? "Item")}</strong></td><td><span className="requisition-product-description">{String(line.specification ?? "No product description provided")}</span>{Boolean(line.notes) && <small className="requisition-product-notes">Note: {String(line.notes)}</small>}</td><td>{String(line.unit ?? "-")}</td><td className="numeric">{formatQuantity(line.quantity)}</td><td className="numeric">{line.estimated_unit_price == null ? "Restricted" : money(Number(line.estimated_unit_price))}</td><td className="numeric"><strong>{line.estimated_total == null ? "Restricted" : money(Number(line.estimated_total))}</strong></td></tr>)}
                  {items.length === 0 && <tr><td colSpan={7} className="requisition-empty-row">No requested items recorded.</td></tr>}
                </tbody>{requisition.estimated_amount != null && <tfoot><tr><td colSpan={6}>Estimated request total</td><td className="numeric">{money(estimatedTotal)}</td></tr></tfoot>}</table></div>
              </section>

              {(supplierOptions.length > 0 || Object.keys(tenderSummary).length > 0) && <section className="requisition-supplier-options">
                <div className="requisition-section-heading"><div><h2>Supplier proformas and bids</h2>{Object.keys(tenderSummary).length > 0 && <p>{String(tenderSummary.tender_number ?? "Public RFQ")} · {humanize(String(tenderSummary.status ?? "pending"))}</p>}</div><span>{supplierOptions.length} option{supplierOptions.length === 1 ? "" : "s"}</span></div>
                {supplierOptions.length === 0 ? <div className="requisition-options-empty">The public RFQ is in progress. Supplier bids will remain sealed until the deadline and appear here after procurement review.</div> : <div className="requisition-option-grid">{supplierOptions.map((option) => {
                  const supplier = objectAt(option, "supplier");
                  return <article className={`requisition-option-card ${option.is_tender_winner || option.is_selected ? "selected" : ""}`} key={String(option.id)}>
                    <header><div><span>{option.source === "supplier_portal_bid" ? "Portal bid" : "Direct proforma"}</span><h3>{String(supplier.name ?? "Supplier")}</h3></div>{Boolean(option.is_tender_winner) ? <b>Tender winner</b> : Boolean(option.is_selected) ? <b>Selected</b> : <span className={`status status-${String(option.status ?? "draft").replaceAll("_", "-")}`}>{humanize(String(option.status ?? "draft"))}</span>}</header>
                    <dl><div><dt>Proforma</dt><dd>{String(option.quotation_number ?? "-")}</dd></div><div><dt>Offered amount</dt><dd>{money(Number(option.total_amount ?? 0))}</dd></div><div><dt>Supplier code</dt><dd>{String(supplier.code ?? "-")}</dd></div><div><dt>Valid until</dt><dd>{formatDate(option.valid_until)}</dd></div></dl>
                    <footer><span>{String(supplier.contact_person ?? "Supplier contact")}</span><span>{String(supplier.phone ?? supplier.email ?? "Contact not provided")}</span>{Boolean(option.bid_receipt_number) && <span>Receipt {String(option.bid_receipt_number)}</span>}</footer>
                  </article>;
                })}</div>}
              </section>}

              <section className="requisition-supporting-row">
                <div><FileText size={16} /><span>Attachments</span><strong>{Array.isArray(requisition.attachments) ? requisition.attachments.length : 0}</strong></div>
                <div><Clock3 size={16} /><span>Last updated</span><strong>{formatDateTime(requisition.updated_at)}</strong></div>
                <div><CircleDot size={16} /><span>Committed amount</span><strong>{requisition.committed_amount == null ? "Restricted" : money(Number(requisition.committed_amount))}</strong></div>
              </section>
            </main>

            <aside className="requisition-approval-panel">
              {Object.keys(budget).length > 0 && <section className={`requisition-budget-card ${budget.sufficient ? "sufficient" : "shortfall"}`}>
                <div className="budget-card-heading">{budget.sufficient ? <PiggyBank size={20} /> : <AlertTriangle size={20} />}<div><span>Organisation budget check</span><strong>{budget.sufficient ? "Budget available" : "Funding shortfall recorded"}</strong></div></div>
                {hasFullBudget && <div className="budget-card-numbers executive"><div><span>Total allocated budget</span><strong>{budget.total_allocated_budget == null ? "Not configured" : money(Number(budget.total_allocated_budget))}</strong></div><div><span>Total used so far</span><strong>{budget.total_used_amount == null ? "—" : money(Number(budget.total_used_amount))}</strong></div><div><span>Available amount</span><strong>{budget.available_amount == null ? "—" : money(Number(budget.available_amount))}</strong></div></div>}
                <p>{String(budget.message ?? "Budget position checked against the active financial year.")}</p>
                {Boolean(requisition.budget_shortfall_reason) && <div className="budget-justification"><span>Funding / loan justification</span><p>{String(requisition.budget_shortfall_reason)}</p></div>}
              </section>}
              <div className="approval-panel-heading"><div><p className="eyebrow">Pending request</p><h2>Approval route</h2></div><span className={`status status-${stage.tone}`}>{stage.label}</span></div>
              <div className="current-approval-stage">
                <div className="approval-avatar">{initials(stage.owner)}</div>
                <div><span>Current owner</span><strong>{stage.owner}</strong><p>{stage.description}</p></div>
              </div>
              {workspaceActions.length > 0 && <div className="requisition-approval-actions">{workspaceActions.map((action) => {
                const permitted = Boolean(user && action.visible(requisition, user));
                return <button key={action.label} disabled={!permitted} aria-disabled={!permitted} title={permitted ? action.label : `Only ${stage.owner} can perform this action at the current stage.`} className={action.tone === "danger" ? "danger-button compact" : action.tone === "primary" ? "primary-button compact" : "secondary-button compact"} onClick={() => permitted && act(action, requisition)}>{action.label}</button>;
              })}{user && workspaceActions.every((action) => !action.visible(requisition, user)) && <p className="approval-actions-readonly">View only — awaiting action from {stage.owner}.</p>}</div>}
              <div className="approval-route-list">
                <ApprovalRoute label="Request created" person={String(requester.name ?? "Requester")} state="complete" date={requisition.created_at} />
                {approvals.map((approval) => <ApprovalRoute key={String(approval.id)} label={humanize(String(approval.action ?? "Reviewed"))} person={String(objectAt(approval, "actor").name ?? "System user")} state={approvalState(String(approval.action ?? ""))} date={approval.action_at} comments={approval.comments} />)}
                {!isTerminal(String(requisition.status ?? "")) && <ApprovalRoute label={stage.label} person={stage.owner} state="pending" />}
              </div>
            </aside>

            <section className="requisition-activity-log">
              <div className="requisition-section-heading"><h2>Activity log</h2><span>{activities.length} recorded event{activities.length === 1 ? "" : "s"}</span></div>
              <div className="requisition-activity-table"><table><thead><tr><th>Date/time</th><th>User</th><th>Activity</th><th>Status</th></tr></thead><tbody>
                {activities.map((activity) => <tr key={String(activity.id)}><td>{formatDateTime(activity.created_at)}</td><td>{String(objectAt(activity, "actor").name ?? "System user")}</td><td>{humanize(String(activity.action ?? "Activity").replace("purchase requisition ", ""))}</td><td><span className={`status status-${activityStatus(activity)}`}>{activityStatusLabel(activity)}</span></td></tr>)}
                {activities.length === 0 && <tr><td colSpan={4} className="requisition-empty-row">No activity has been recorded yet.</td></tr>}
              </tbody></table></div>
            </section>
          </div>
        )}
      </section>
    </div>
  );
}

function Fact({ icon, label, value, detail }: { icon: React.ReactNode; label: string; value: string; detail: string }) {
  return <div className="requisition-fact">{icon}<div><span>{label}</span><strong>{value}</strong><p>{detail}</p></div></div>;
}

function ApprovalRoute({ label, person, state, date, comments }: { label: string; person: string; state: "complete" | "pending" | "rejected"; date?: unknown; comments?: unknown }) {
  return <div className={`approval-route-item ${state}`}><div className="approval-route-marker">{state === "complete" ? <Check size={13} /> : <span />}</div><div><span>{label}</span><strong>{person}</strong>{Boolean(comments) && <p>{String(comments)}</p>}<time>{date ? formatDateTime(date) : "Awaiting action"}</time></div></div>;
}

function objectAt(record: JsonRecord, key: string): JsonRecord {
  const value = record[key];
  return value && typeof value === "object" && !Array.isArray(value) ? value as JsonRecord : {};
}

function currentStage(status: string, manager: JsonRecord) {
  const stages: Record<string, { label: string; owner: string; description: string; tone: string }> = {
    draft: { label: "Draft preparation", owner: "Requester", description: "Complete and submit this request.", tone: "draft" },
    submitted: { label: "Manager approval", owner: String(manager.name ?? "Line manager"), description: "Review budget need and business purpose.", tone: "submitted" },
    pending_gm_approval: { label: "GM requisition approval", owner: "General Manager", description: "Give final approval before the request is released to sourcing.", tone: "submitted" },
    returned: { label: "Corrections required", owner: "Requester", description: "Revise the request before resubmission.", tone: "returned" },
    approved_for_sourcing: { label: "Procurement sourcing", owner: "Procurement team", description: "Obtain and assess supplier proformas.", tone: "active" },
    quotations_ready: { label: "Proforma selection", owner: "Procurement team", description: "Select a proforma for final approval.", tone: "pending" },
    pending_requester_approval: { label: "Requester proforma review", owner: "Requester", description: "Review supplier prices and select a proforma.", tone: "submitted" },
    pending_line_manager_approval: { label: "Line manager review", owner: String(manager.name ?? "Line manager"), description: "Review the requester's selected proforma.", tone: "submitted" },
    pending_final_approval: { label: "Final approval", owner: "General Manager", description: "Review the recommended supplier proforma.", tone: "submitted" },
    approved_for_purchase: { label: "Approved", owner: "Procurement team", description: "The request is ready for LPO creation.", tone: "approved" },
    returned_to_sourcing: { label: "Sourcing revision", owner: "Procurement team", description: "Correct the proforma recommendation.", tone: "returned" },
    rejected: { label: "Rejected", owner: "Request closed", description: "The request was not approved.", tone: "rejected" },
    cancelled: { label: "Cancelled", owner: "Request closed", description: "The request was cancelled.", tone: "cancelled" },
  };
  return stages[status] ?? { label: humanize(status), owner: "Workflow owner", description: "Review the current request state.", tone: "pending" };
}

function approvalState(action: string): "complete" | "pending" | "rejected" {
  if (["rejected", "cancelled", "returned"].some((value) => action.includes(value))) return "rejected";
  return "complete";
}

function isTerminal(status: string) {
  return ["approved_for_purchase", "rejected", "cancelled", "returned_to_sourcing", "withdrawn", "expired"].includes(status);
}

function activityStatus(activity: JsonRecord) {
  const values = objectAt(activity, "new_values");
  return String(values.status ?? "active").replaceAll("_", "-");
}

function activityStatusLabel(activity: JsonRecord) {
  const values = objectAt(activity, "new_values");
  return humanize(String(values.status ?? "Recorded"));
}

function initials(name: string) {
  return name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join("") || "PR";
}

function money(value: number) {
  return `TZS ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatQuantity(value: unknown) {
  return Number(value ?? 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
}

function formatDate(value: unknown) {
  if (!value) return "Not set";
  const date = new Date(String(value));
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString(undefined, { day: "2-digit", month: "short", year: "numeric" });
}

function formatDateTime(value: unknown) {
  if (!value) return "Pending";
  const date = new Date(String(value));
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString(undefined, { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

function humanize(value: string) {
  return value.replaceAll(".", " ").replaceAll("_", " ").replace(/\b\w/g, (character) => character.toUpperCase());
}
