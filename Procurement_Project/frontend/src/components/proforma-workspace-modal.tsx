"use client";

import { useAuth } from "@/components/auth-provider";
import { api, ApiError } from "@/lib/api";
import type { ActionSpec } from "@/lib/modules";
import type { JsonRecord } from "@/lib/types";
import { CalendarDays, Check, CircleDot, Clock3, FileText, LoaderCircle, Printer, UserRound, X } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

type Props = {
  item: JsonRecord;
  actions: ActionSpec[];
  close: () => void;
  act: (action: ActionSpec, item: JsonRecord) => void;
};

export function ProformaWorkspaceModal({ item, actions, close, act }: Props) {
  const { user } = useAuth();
  const [proforma, setProforma] = useState(item);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError("");
    api<JsonRecord>(`admin/supplier-quotations/${Number(item.id)}`)
      .then((response) => {
        if (active) setProforma((response.data as JsonRecord | undefined) ?? response);
      })
      .catch((caught) => {
        if (active) setError(caught instanceof ApiError ? caught.message : "Unable to load the proforma workspace.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [item]);

  const workspaceActions = useMemo(
    () => (user ? actions.filter((action) => action.appliesTo ? action.appliesTo(proforma) : action.visible(proforma, user)) : []),
    [actions, proforma, user],
  );

  const lineItems = (proforma.items as JsonRecord[] | undefined) ?? [];
  const activities = (proforma.activity_logs as JsonRecord[] | undefined) ?? [];
  const supplier = objectAt(proforma, "supplier");
  const requisition = objectAt(proforma, "requisition");
  const approvalRecommendation = objectAt(proforma, "approval_recommendation");
  const procurementApprovals = (approvalRecommendation.procurement_approvals as JsonRecord[] | undefined) ?? [];
  const recommendedBy = objectAt(approvalRecommendation, "recommended_by");
  const subtotal = lineItems.reduce((sum, line) => sum + Number(line.total_price ?? 0), 0);
  const stage = currentStage(String(proforma.status ?? "draft"), approvalRecommendation);

  return (
    <div className="requisition-workspace-backdrop" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) close(); }}>
      <section className="requisition-workspace-modal" role="dialog" aria-modal="true" aria-labelledby="proforma-workspace-title">
        <header className="requisition-workspace-toolbar">
          <div><span>Supplier proforma</span><strong>{String(proforma.quotation_number ?? `Proforma #${proforma.id}`)}</strong></div>
          <div>
            <button className="icon-button bordered" onClick={() => window.print()} title="Print proforma"><Printer size={17} /></button>
            <button className="icon-button" onClick={close} title="Close proforma"><X size={19} /></button>
          </div>
        </header>

        {loading ? <div className="requisition-workspace-loading"><LoaderCircle className="spin" size={22} />Loading proforma workspace...</div> : error ? <div className="requisition-workspace-error">{error}</div> : (
          <div className="requisition-workspace-content">
            <main className="requisition-document">
              <header className="requisition-document-header">
                <div>
                  <p className="eyebrow">Supplier offer</p>
                  <h1 id="proforma-workspace-title">Supplier proforma</h1>
                  <span className={`status status-${String(proforma.status ?? "draft").replaceAll("_", "-")}`}>{humanize(String(proforma.status ?? "draft"))}</span>
                </div>
                <div className="requisition-number-block"><span>Proforma number</span><strong>{String(proforma.quotation_number ?? "Draft")}</strong></div>
              </header>

              <section className="requisition-facts">
                <Fact icon={<UserRound size={16} />} label="Supplier" value={String(supplier.name ?? "-")} detail={String(supplier.code ?? "Supplier code")} />
                <Fact icon={<FileText size={16} />} label="Requisition" value={String(requisition.requisition_number ?? "-")} detail={String(requisition.purpose ?? "Linked requisition")} />
                <Fact icon={<CalendarDays size={16} />} label="Valid until" value={formatDate(proforma.valid_until)} detail={`Created ${formatDate(proforma.created_at)}`} />
                <Fact icon={<FileText size={16} />} label="Offered total" value={money(Number(proforma.total_amount ?? subtotal))} detail={`${lineItems.length} line item${lineItems.length === 1 ? "" : "s"}`} />
              </section>

              <section className="requisition-items-section">
                <div className="requisition-section-heading"><h2>Offered line items</h2><span>{lineItems.length} item{lineItems.length === 1 ? "" : "s"}</span></div>
                <div className="requisition-items-table"><table><thead><tr><th>#</th><th>Product</th><th>Description / specification</th><th>Quantity</th><th>Unit</th><th className="numeric">Offered price</th><th className="numeric">Subtotal</th></tr></thead><tbody>
                  {lineItems.map((line, index) => <tr key={String(line.id ?? index)}><td>{index + 1}</td><td><strong>{String(line.item_name ?? "Item")}</strong></td><td><span className="requisition-product-description">{String(line.specification ?? "No description provided")}</span>{Boolean(line.notes) && <small className="requisition-product-notes">Note: {String(line.notes)}</small>}</td><td>{formatQuantity(line.quantity)}</td><td>{String(line.unit ?? "-")}</td><td className="numeric">{line.unit_price == null ? "Restricted" : money(Number(line.unit_price))}</td><td className="numeric"><strong>{line.total_price == null ? "Restricted" : money(Number(line.total_price))}</strong></td></tr>)}
                  {lineItems.length === 0 && <tr><td colSpan={7} className="requisition-empty-row">No line items recorded.</td></tr>}
                </tbody>{lineItems.length > 0 && <tfoot><tr><td colSpan={6}>Offered total</td><td className="numeric">{money(Number(proforma.total_amount ?? subtotal))}</td></tr></tfoot>}</table></div>
              </section>

              {Boolean(proforma.notes) && <section className="requisition-purpose">
                <span>Proforma notes</span>
                <strong>{String(proforma.notes)}</strong>
              </section>}

              <section className="requisition-supporting-row">
                <div><Clock3 size={16} /><span>Last updated</span><strong>{formatDateTime(proforma.updated_at)}</strong></div>
                <div><UserRound size={16} /><span>Prepared by</span><strong>{String(objectAt(proforma, "preparer").name ?? "System")}</strong></div>
                <div><CircleDot size={16} /><span>Requisition status</span><strong><span className={`status status-${String(requisition.status ?? "draft").replaceAll("_", "-")}`}>{humanize(String(requisition.status ?? "draft"))}</span></strong></div>
              </section>
            </main>

            <aside className="requisition-approval-panel">
              {Boolean(approvalRecommendation.id) && <section className="requisition-budget-card approved">
                <div className="budget-card-heading"><Check size={20} /><div><span>Procurement recommendation</span><strong>{humanize(String(approvalRecommendation.status ?? "draft"))}</strong></div></div>
                {Boolean(approvalRecommendation.reason_for_selection) && <div className="budget-card-numbers"><div><span>Reason for selection</span><strong>{String(approvalRecommendation.reason_for_selection)}</strong></div></div>}
                {Boolean(approvalRecommendation.non_lowest_price_reason) && <div className="budget-card-numbers"><div><span>Non-lowest price reason</span><strong>{String(approvalRecommendation.non_lowest_price_reason)}</strong></div></div>}
                <p>Recommended by {String(recommendedBy.name ?? "Procurement")} on {formatDateTime(approvalRecommendation.recommended_at)}</p>
              </section>}

              <div className="approval-panel-heading"><div><p className="eyebrow">Pending request</p><h2>Approval route</h2></div><span className={`status status-${stage.tone}`}>{stage.label}</span></div>
              <div className="current-approval-stage">
                <div className="approval-avatar">{initials(stage.owner)}</div>
                <div><span>Current owner</span><strong>{stage.owner}</strong><p>{stage.description}</p></div>
              </div>
              {workspaceActions.length > 0 && <div className="requisition-approval-actions">{workspaceActions.map((action) => {
                const permitted = Boolean(user && action.visible(proforma, user));
                return <button key={action.label} disabled={!permitted} aria-disabled={!permitted} title={permitted ? action.label : `Only ${stage.owner} can perform this action at the current stage.`} className={action.tone === "danger" ? "danger-button compact" : action.tone === "primary" ? "primary-button compact" : "secondary-button compact"} onClick={() => permitted && act(action, proforma)}>{action.label}</button>;
              })}{user && workspaceActions.every((action) => !action.visible(proforma, user)) && <p className="approval-actions-readonly">View only — awaiting action from {stage.owner}.</p>}</div>}
              <div className="approval-route-list">
                <ApprovalRoute label="Proforma created" person={String(supplier.name ?? "Supplier")} state="complete" date={proforma.created_at} />
                {Boolean(proforma.submitted_at) && <ApprovalRoute label="Submitted for sourcing" person={String(objectAt(proforma, "preparer").name ?? "Procurement")} state="complete" date={proforma.submitted_at} />}
                {procurementApprovals.map((approval) => <ApprovalRoute key={String(approval.id)} label={humanize(String(approval.action ?? "Reviewed"))} person={String(objectAt(approval, "actor").name ?? "System user")} state={approvalState(String(approval.action ?? ""))} date={approval.action_at} comments={approval.comments} />)}
                {!isTerminal(String(proforma.status ?? "")) && <ApprovalRoute label={stage.label} person={stage.owner} state="pending" />}
              </div>
            </aside>

            <section className="requisition-activity-log">
              <div className="requisition-section-heading"><h2>Activity log</h2><span>{activities.length} recorded event{activities.length === 1 ? "" : "s"}</span></div>
              <div className="requisition-activity-table"><table><thead><tr><th>Date/time</th><th>Activity</th><th>Status</th></tr></thead><tbody>
                {activities.map((activity) => <tr key={String(activity.id)}><td>{formatDateTime(activity.created_at)}</td><td>{humanize(String(activity.action ?? "Activity").replace("supplier quotation ", ""))}</td><td><span className={`status status-${activityStatus(activity)}`}>{activityStatusLabel(activity)}</span></td></tr>)}
                {activities.length === 0 && <tr><td colSpan={3} className="requisition-empty-row">No activity has been recorded yet.</td></tr>}
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

function currentStage(status: string, recommendation: JsonRecord) {
  const recStatus = String(recommendation.status ?? "draft");
  const reqStatus = String((recommendation.requisition as JsonRecord | undefined)?.status ?? "draft");
  const stages: Record<string, { label: string; owner: string; description: string; tone: string }> = {
    draft: { label: "Draft preparation", owner: "Procurement officer", description: "Complete and submit this proforma.", tone: "draft" },
    active: { label: "Sourcing review", owner: "Procurement team", description: "Proforma is under sourcing review.", tone: "active" },
    withdrawn: { label: "Withdrawn", owner: "Request closed", description: "The proforma has been withdrawn.", tone: "cancelled" },
    expired: { label: "Expired", owner: "Request closed", description: "The proforma validity has expired.", tone: "rejected" },
    rejected: { label: "Rejected", owner: "Request closed", description: "The proforma was rejected.", tone: "rejected" },
  };
  if (reqStatus === "pending_requester_approval") return { label: "Requester review", owner: "Requester", description: "Requester is reviewing supplier prices.", tone: "submitted" };
  if (reqStatus === "pending_line_manager_approval") return { label: "Line manager review", owner: "Line manager", description: "Line manager is reviewing the requester's selection.", tone: "submitted" };
  if (recStatus === "submitted" && reqStatus === "pending_final_approval") return { label: "GM final approval", owner: "General Manager", description: "Review the recommended supplier proforma.", tone: "submitted" };
  if (recStatus === "approved") return { label: "Approved for purchase", owner: "Procurement team", description: "The requisition is ready for LPO creation.", tone: "approved" };
  if (recStatus === "returned") return { label: "Sourcing revision", owner: "Procurement team", description: "Correct the proforma recommendation.", tone: "returned" };
  if (recStatus === "rejected") return { label: "Rejected", owner: "Request closed", description: "The recommendation was rejected.", tone: "rejected" };
  return stages[status] ?? { label: humanize(status), owner: "Workflow owner", description: "Review the current proforma state.", tone: "pending" };
}

function approvalState(action: string): "complete" | "pending" | "rejected" {
  if (["rejected", "cancelled", "returned"].some((value) => action.includes(value))) return "rejected";
  return "complete";
}

function isTerminal(status: string) {
  return ["approved_for_purchase", "rejected", "cancelled", "withdrawn", "expired", "returned_to_sourcing"].includes(status);
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
