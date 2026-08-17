"use client";

import { useAuth } from "@/components/auth-provider";
import { useEntityScope } from "@/components/entity-scope-provider";
import { api, collectionFrom, valueAt } from "@/lib/api";
import type { JsonRecord } from "@/lib/types";
import { AlertCircle, ArrowRight, Banknote, Building2, CheckCircle2, Clock3, FileText, RefreshCw, ShoppingCart } from "lucide-react";
import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";

type Metric = { label: string; value: string; tone: string };

function dashboardEndpoint(roles: string[]) {
  if (roles.includes("ceo")) return "executive";
  if (roles.includes("storekeeper") || roles.includes("receiving_officer")) return "requester";
  if (roles.includes("procurement_officer")) return "operational";
  if (roles.includes("accountant")) return "finance";
  if (roles.includes("gm")) return "executive";
  if (roles.includes("requester") || roles.includes("department_head") || roles.includes("line_manager")) return "requester";
  if (roles.includes("auditor") || roles.includes("super_admin")) return "auditor";
  return "requester";
}

function label(key: string) {
  return key.replaceAll("_", " ").replace(/\b\w/g, (value) => value.toUpperCase());
}

function scalarMetrics(data: JsonRecord): Metric[] {
  return Object.entries(data)
    .filter(([, value]) => typeof value === "number" || typeof value === "string")
    .slice(0, 8)
    .map(([key, value], index) => ({ label: label(key), value: typeof value === "number" ? value.toLocaleString() : String(value), tone: ["green", "blue", "amber", "red"][index % 4] }));
}

function sum(records: unknown, key: string) {
  if (!Array.isArray(records)) return 0;
  return records.reduce((total, record) => {
    if (!record || typeof record !== "object") return total;
    return total + Number((record as JsonRecord)[key] ?? 0);
  }, 0);
}

function dashboardMetrics(data: JsonRecord, endpoint: string): Metric[] {
  const scalars = scalarMetrics(data);
  if (scalars.length > 0) return scalars;

  if (endpoint === "executive") {
    const unpaid = data.unpaid_invoices as JsonRecord | undefined;
    return [
      { label: "Requisitions", value: sum(data.requisitions_by_status, "count").toLocaleString(), tone: "green" },
      { label: "Approved budget", value: `TZS ${sum(data.budget_summaries, "approved_amount").toLocaleString()}`, tone: "blue" },
      { label: "Unpaid invoices", value: Number(unpaid?.count ?? 0).toLocaleString(), tone: "amber" },
      { label: "Payments recorded", value: `TZS ${sum(data.payment_totals, "total").toLocaleString()}`, tone: "red" },
    ];
  }

  const workflow = data.workflow_counts as JsonRecord | undefined;
  if (endpoint === "auditor" && workflow) {
    return Object.entries(workflow).slice(0, 4).map(([key, value], index) => ({
      label: label(key),
      value: Number(value ?? 0).toLocaleString(),
      tone: ["green", "blue", "amber", "red"][index],
    }));
  }

  return [];
}

export function DashboardOverview() {
  const { user } = useAuth();
  const { entities, selectedEntityId } = useEntityScope();
  const [dashboard, setDashboard] = useState<JsonRecord>({});
  const [requisitions, setRequisitions] = useState<JsonRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const endpoint = useMemo(() => dashboardEndpoint(user?.roles ?? []), [user]);

  const load = useCallback(async () => {
    if (!user) return;
    setLoading(true);
    setError("");
    try {
      const [summary, recent] = await Promise.all([
        api<JsonRecord>(`admin/dashboard/${endpoint}${selectedEntityId ? `?business_entity_id=${selectedEntityId}` : ""}`),
        api<JsonRecord>(`admin/purchase-requisitions?per_page=6${selectedEntityId ? `&business_entity_id=${selectedEntityId}` : ""}`),
      ]);
      setDashboard((summary.data as JsonRecord) ?? summary);
      setRequisitions(collectionFrom(recent).rows);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "Unable to load dashboard.");
    } finally {
      setLoading(false);
    }
  }, [endpoint, selectedEntityId, user]);

  useEffect(() => { void load(); }, [load]);
  const metrics = useMemo(() => dashboardMetrics(dashboard, endpoint), [dashboard, endpoint]);
  const metricSlots: Array<Metric | null> = loading && metrics.length === 0
    ? Array.from({ length: 4 }, () => null)
    : metrics.slice(0, 4);
  const firstName = user?.first_name ?? user?.name.split(" ")[0] ?? "there";
  const isCeo = user?.roles.includes("ceo") ?? false;
  const currentEntity = isCeo
    ? selectedEntityId
      ? entities.find((entity) => String(entity.id) === selectedEntityId)?.name ?? "Selected business entity"
      : "All business entities"
    : user?.department?.business_entity?.name ?? "Entity not assigned";

  return (
    <div className="page-stack">
      <section className="page-heading dashboard-heading">
        <div><p className="eyebrow">Today&apos;s control desk</p><h1>Good day, {firstName}</h1><p>Here is the work currently moving through your procurement responsibilities.</p></div>
        <div className="dashboard-heading-actions">
          <div className="dashboard-entity-context"><Building2 size={18} /><span><small>Current entity view</small><strong>{currentEntity}</strong></span></div>
          <button className="secondary-button" onClick={() => void load()} disabled={loading}><RefreshCw size={16} className={loading ? "spin" : ""} />Refresh</button>
        </div>
      </section>
      {error && <div className="inline-error"><AlertCircle size={18} />{error}</div>}
      <section className="kpi-grid" aria-label="Procurement overview">
        {metricSlots.map((metric, index) => metric ? <div className="kpi" key={metric.label}><span className={`kpi-icon ${metric.tone}`}>{[FileText, Clock3, Banknote, CheckCircle2].map((Icon, iconIndex) => iconIndex === index ? <Icon key={iconIndex} size={19} /> : null)}</span><span><small>{metric.label}</small><strong>{metric.value}</strong></span></div> : <div className="kpi skeleton" key={index} />)}
      </section>
      <section className="dashboard-band">
        <div className="section-heading"><div><h2>Recent requisitions</h2><p>The latest requests visible to your role.</p></div><Link className="text-link" href="/requisitions">Open register <ArrowRight size={15} /></Link></div>
        <div className="table-wrap compact-table">
          <table><thead><tr><th>Requisition</th><th>Purpose</th><th>Requester</th><th>Required</th><th>Status</th></tr></thead><tbody>
            {requisitions.map((item) => <tr key={String(item.id)}><td><Link className="record-link" href="/requisitions">{String(item.requisition_number ?? "Draft")}</Link></td><td>{String(item.purpose ?? "-")}</td><td>{String(valueAt(item, "requester.name") ?? "-")}</td><td>{String(item.required_date ?? "-")}</td><td><span className={`status status-${String(item.status).replaceAll("_", "-")}`}>{label(String(item.status))}</span></td></tr>)}
            {!loading && requisitions.length === 0 && <tr><td colSpan={5}><EmptyRows /></td></tr>}
          </tbody></table>
        </div>
      </section>
      <section className="quick-links"><Link href="/requisitions"><FileText size={19} /><span><strong>Requisition queue</strong><small>Review requests and approvals</small></span><ArrowRight size={17} /></Link><Link href="/purchase-orders"><ShoppingCart size={19} /><span><strong>Purchase orders</strong><small>Confirm and track delivery</small></span><ArrowRight size={17} /></Link><Link href="/payments"><Banknote size={19} /><span><strong>Payment control</strong><small>Review approved liabilities</small></span><ArrowRight size={17} /></Link></section>
    </div>
  );
}

function EmptyRows() {
  return <div className="empty-inline"><CheckCircle2 size={19} /><span>No requisitions are currently in this queue.</span></div>;
}
