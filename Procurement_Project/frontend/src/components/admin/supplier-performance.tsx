"use client";

import { useAuth } from "@/components/auth-provider";
import { useEntityScope } from "@/components/entity-scope-provider";
import { EmptyState, LoadingState, StatusBadge } from "@/components/ui/portal-ui";
import { api, collectionFrom } from "@/lib/api";
import { formatDate, statusLabel } from "@/lib/formatters";
import type { JsonRecord } from "@/lib/types";
import { AlertTriangle, BarChart3, RefreshCw, Search, ShieldCheck, Truck } from "lucide-react";
import { useCallback, useEffect, useState } from "react";

type Evaluation = { id: number; grade: string; overall_score: string; delivery_score: string; quality_score: string; compliance_score: string; responsiveness_score: string; commercial_reliability_score?: string; completed_purchase_orders_count: number; cancelled_purchase_orders_count: number; calculated_at: string; total_awarded_value?: string };
type SupplierRow = { id: number; name: string; code: string; portal_status: string; compliance_status: string; award_eligibility: string; current_performance: Evaluation | null; open_incidents_count: number };
type Incident = { id: number; incident_type: string; severity: string; description: string; occurred_at: string; resolved_at: string | null };
type Performance = { supplier: SupplierRow & { restriction_reason?: string }; compliance: JsonRecord; current_evaluation: Evaluation | null; evaluation_history: Evaluation[]; incidents: Incident[]; procurement_history: Record<string, unknown[]> };
type ComplianceAlert = { id: number; name: string; code: string; compliance_status: string; award_eligibility: string; missing_documents: string[]; expired_documents: string[]; expiring_documents: Record<string, string[]> };

export function SupplierPerformance() {
  const { user } = useAuth();
  const { selectedEntityId } = useEntityScope();
  const [suppliers, setSuppliers] = useState<SupplierRow[] | null>(null);
  const [alerts, setAlerts] = useState<ComplianceAlert[] | null>(null);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [data, setData] = useState<Performance | null>(null);
  const [search, setSearch] = useState("");
  const [message, setMessage] = useState("");

  const loadSuppliers = useCallback(() => api("admin/suppliers?per_page=100").then((value) => setSuppliers(collectionFrom(value).rows as SupplierRow[])), []);
  const loadAlerts = useCallback(() => api<{ data: ComplianceAlert[] }>("admin/supplier-compliance-alerts").then((value) => setAlerts(value.data)), []);
  const loadPerformance = useCallback(async (id: number) => {
    const suffix = selectedEntityId ? `?business_entity_id=${selectedEntityId}` : "";
    const response = await api<{ data: Performance }>(`admin/suppliers/${id}/performance${suffix}`);
    setData(response.data);
  }, [selectedEntityId]);

  useEffect(() => { void Promise.all([loadSuppliers(), loadAlerts()]); }, [loadAlerts, loadSuppliers]);
  useEffect(() => { if (selectedId) void loadPerformance(selectedId); }, [loadPerformance, selectedId]);

  async function calculate() {
    if (!selectedId) return;
    const suffix = selectedEntityId ? `?business_entity_id=${selectedEntityId}` : "";
    try {
      await api(`admin/suppliers/${selectedId}/performance/calculate${suffix}`, { method: "POST", body: "{}" });
      setMessage("A new immutable performance snapshot was calculated.");
      await Promise.all([loadPerformance(selectedId), loadSuppliers(), loadAlerts()]);
    } catch (caught) {
      setMessage(caught instanceof Error ? caught.message : "Calculation failed.");
    }
  }

  const visible = suppliers?.filter((supplier) => `${supplier.name} ${supplier.code}`.toLowerCase().includes(search.toLowerCase())) ?? [];
  const canCalculate = user?.roles.some((role) => ["super_admin", "gm", "ceo"].includes(role));

  return <div className="page-stack">
    <header className="page-heading"><div><span className="eyebrow">Calculated procurement outcomes</span><h1>Supplier compliance & performance</h1><p>Delivery, quality, compliance, responsiveness, and commercial reliability calculated from operational history.</p></div>{canCalculate && selectedId && <button className="primary-button" onClick={() => void calculate()}><RefreshCw size={16} />Calculate snapshot</button>}</header>
    {message && <div className="inline-success">{message}</div>}
    {alerts && alerts.length > 0 && <ComplianceAlerts alerts={alerts} select={setSelectedId} />}
    <div className="supplier-performance-layout">
      <aside className="supplier-performance-list"><div className="search-box"><Search size={16} /><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Find supplier" /></div>{suppliers === null ? <LoadingState /> : visible.map((supplier) => <button className={selectedId === supplier.id ? "active" : ""} onClick={() => setSelectedId(supplier.id)} key={supplier.id}><Truck size={17} /><span><strong>{supplier.name}</strong><small>{supplier.code} · {supplier.current_performance?.grade ?? "Insufficient data"}</small></span><StatusBadge status={supplier.award_eligibility} /></button>)}</aside>
      <main className="supplier-performance-detail">{!selectedId ? <EmptyState title="Select a supplier" copy="Open a supplier to review compliance, grade history, incidents, and procurement outcomes." /> : !data ? <LoadingState /> : <PerformanceDetail data={data} />}</main>
    </div>
  </div>;
}

function ComplianceAlerts({ alerts, select }: { alerts: ComplianceAlert[]; select: (id: number) => void }) {
  return <section className="supplier-panel"><div className="panel-heading"><div><h2>Compliance alerts</h2><p>Missing, expired, and 30/60/90-day document warnings requiring attention.</p></div><AlertTriangle size={20} /></div><div className="portal-table-wrap"><table className="portal-table"><thead><tr><th>Supplier</th><th>Status</th><th>Missing</th><th>Expired</th><th>Expiring 30/60/90</th></tr></thead><tbody>{alerts.slice(0, 12).map((alert) => <tr key={alert.id} onClick={() => select(alert.id)}><td><button className="record-link">{alert.name}</button><small>{alert.code}</small></td><td><StatusBadge status={alert.compliance_status} /></td><td>{list(alert.missing_documents)}</td><td>{list(alert.expired_documents)}</td><td>{["30", "60", "90"].map((bucket) => `${bucket}: ${alert.expiring_documents[bucket]?.length ?? 0}`).join(" / ")}</td></tr>)}</tbody></table></div></section>;
}

function PerformanceDetail({ data }: { data: Performance }) {
  const evaluation = data.current_evaluation;
  const scores = evaluation ? [["Delivery", evaluation.delivery_score], ["Quality", evaluation.quality_score], ["Compliance", evaluation.compliance_score], ["Responsiveness", evaluation.responsiveness_score], ...(evaluation.commercial_reliability_score !== undefined ? [["Commercial reliability", evaluation.commercial_reliability_score]] : [])] : [];
  const expiring = data.compliance.expiring_documents as Record<string, string[]> | undefined;
  const history = Object.entries(data.procurement_history);

  return <div className="performance-stack">
    <section className="performance-summary"><div><span>Supplier status</span><StatusBadge status={data.supplier.portal_status} /></div><div><span>Compliance</span><StatusBadge status={data.supplier.compliance_status} /></div><div><span>Award eligibility</span><StatusBadge status={data.supplier.award_eligibility} /></div><div><span>Current grade</span><strong className="performance-grade">{evaluation?.grade === "insufficient_data" || !evaluation ? "Insufficient data" : evaluation.grade}</strong></div><div><span>Overall score</span><strong>{evaluation ? `${evaluation.overall_score}%` : "—"}</strong></div><div><span>Last evaluated</span><strong>{formatDate(evaluation?.calculated_at)}</strong></div></section>
    <section className="supplier-panel"><div className="panel-heading"><div><h2>Performance breakdown</h2><p>Calculated from procurement history. Scores cannot be manually altered.</p></div><BarChart3 size={20} /></div>{scores.length === 0 ? <EmptyState title="No evaluation yet" copy="Calculate a snapshot after procurement activity is available." /> : <div className="score-grid">{scores.map(([name, score]) => <div key={name}><span>{name}</span><strong>{score}%</strong><div><i style={{ width: `${Math.min(100, Number(score))}%` }} /></div></div>)}</div>}</section>
    <section className="supplier-panel"><div className="panel-heading"><div><h2>Compliance evidence</h2><p>Mandatory evidence and expiry monitoring.</p></div><ShieldCheck size={20} /></div><div className="compliance-summary"><div><span>Missing</span><strong>{list(data.compliance.missing_documents)}</strong></div><div><span>Expired</span><strong>{list(data.compliance.expired_documents)}</strong></div><div><span>Rejected</span><strong>{list(data.compliance.rejected_documents)}</strong></div><div><span>Expiring 30/60/90</span><strong>{["30", "60", "90"].map((bucket) => `${bucket}: ${expiring?.[bucket]?.length ?? 0}`).join(" / ")}</strong></div><div><span>Reason</span><strong>{String(data.compliance.reason ?? "No restriction")}</strong></div></div></section>
    <section className="supplier-panel"><h2>Evaluation history</h2><div className="portal-table-wrap"><table className="portal-table"><thead><tr><th>Calculated</th><th>Grade</th><th>Overall</th><th>Completed LPOs</th><th>Cancelled LPOs</th></tr></thead><tbody>{data.evaluation_history.map((item) => <tr key={item.id}><td>{formatDate(item.calculated_at)}</td><td>{statusLabel(item.grade)}</td><td>{item.overall_score}%</td><td>{item.completed_purchase_orders_count}</td><td>{item.cancelled_purchase_orders_count}</td></tr>)}</tbody></table></div></section>
    <section className="supplier-panel"><div className="panel-heading"><div><h2>Procurement history</h2><p>Entity-scoped tender, proforma, LPO, GRN, and invoice-match records.</p></div><Truck size={20} /></div><div className="score-grid procurement-history-counts">{history.map(([name, rows]) => <div key={name}><span>{statusLabel(name)}</span><strong>{rows.length}</strong></div>)}</div></section>
    <section className="supplier-panel"><div className="panel-heading"><div><h2>Performance incidents</h2><p>Operational exceptions and their resolution state.</p></div><AlertTriangle size={20} /></div>{data.incidents.length === 0 ? <EmptyState title="No incidents" copy="No supplier performance incidents are recorded." /> : <div className="incident-list">{data.incidents.map((incident) => <article key={incident.id}><StatusBadge status={incident.severity} /><div><strong>{statusLabel(incident.incident_type)}</strong><p>{incident.description}</p><small>{formatDate(incident.occurred_at)} · {incident.resolved_at ? "Resolved" : "Open"}</small></div></article>)}</div>}</section>
  </div>;
}

function list(value: unknown) {
  return Array.isArray(value) && value.length ? value.map((item) => statusLabel(String(item))).join(", ") : "None";
}
