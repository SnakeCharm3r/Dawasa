"use client";

import { InvoiceDialog, ProformaDialog, ReceiptDialog, RequisitionDialog, SupplierDialog } from "@/components/create-dialogs";
import { useAuth } from "@/components/auth-provider";
import { useEntityScope } from "@/components/entity-scope-provider";
import { PaymentVoucherModal } from "@/components/payment-voucher-modal";
import { ProformaWorkspaceModal } from "@/components/proforma-workspace-modal";
import { RequisitionWorkspaceModal } from "@/components/requisition-workspace-modal";
import { api, ApiError, collectionFrom, valueAt } from "@/lib/api";
import { modules, type ActionSpec, type Column, type ModuleConfig } from "@/lib/modules";
import type { JsonRecord, Pagination } from "@/lib/types";
import { AlertCircle, Check, ChevronLeft, ChevronRight, Eye, Filter, LoaderCircle, Plus, RefreshCw, Search, SlidersHorizontal, X } from "lucide-react";
import { useRouter } from "next/navigation";
import { FormEvent, useCallback, useEffect, useMemo, useState } from "react";

export function ResourcePage({ moduleKey }: { moduleKey: string }) {
  const config = modules[moduleKey];
  const router = useRouter();
  const { user } = useAuth();
  const { selectedEntityId } = useEntityScope();
  const [rows, setRows] = useState<JsonRecord[]>([]);
  const [pagination, setPagination] = useState<Pagination>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [query, setQuery] = useState("");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<JsonRecord | null>(null);
  const [createOpen, setCreateOpen] = useState(false);
  const [editRequisition, setEditRequisition] = useState<JsonRecord | null>(null);
  const [pendingAction, setPendingAction] = useState<{ action: ActionSpec; item: JsonRecord } | null>(null);

  const load = useCallback(async () => {
    if (!config) return;
    setLoading(true);
    setError("");
    const params = new URLSearchParams({ page: String(page), per_page: "15" });
    if (search) params.set("search", search);
    if (status) params.set("status", status);
    if (selectedEntityId) params.set("business_entity_id", selectedEntityId);
    try {
      const payload = await api<JsonRecord>(`${config.endpoint}?${params}`);
      const collection = collectionFrom(payload);
      setRows(collection.rows);
      setPagination(collection.pagination);
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to load records.");
      setRows([]);
    } finally {
      setLoading(false);
    }
  }, [config, page, search, selectedEntityId, status]);

  useEffect(() => { setPage(1); setSelected(null); }, [selectedEntityId]);

  useEffect(() => { void load(); }, [load]);

  const canCreate = useMemo(() => {
    if (!user || !config?.create) return false;
    if (user.roles.includes("ceo")) return true;
    if (["supplier", "proforma"].includes(config.create)) return user.roles.some((role) => ["super_admin", "procurement_officer"].includes(role));
    if (config.create === "receipt") return user.roles.some((role) => ["super_admin", "procurement_officer", "storekeeper", "receiving_officer"].includes(role));
    if (config.create === "invoice") return user.roles.some((role) => ["super_admin", "accountant"].includes(role));
    return user.roles.some((role) => ["requester", "line_manager", "department_head"].includes(role));
  }, [config, user]);

  if (!config) return <div className="not-found"><h1>Module not found</h1><p>This workspace module is not configured.</p></div>;
  if (config.roles && user && !user.roles.includes("ceo") && !config.roles.some((role) => user.roles.includes(role))) return <div className="not-found"><h1>Access restricted</h1><p>Your role does not include access to this register.</p></div>;

  function submitSearch(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    setSearch(query.trim());
  }

  function completed(message: string) {
    setNotice(message);
    setTimeout(() => setNotice(""), 4000);
    void load();
  }

  function openRecord(item: JsonRecord) {
    if (moduleKey === "suppliers") {
      router.push(`/suppliers/${String(item.id)}`);
      return;
    }
    setSelected(item);
  }

  return (
    <div className="page-stack">
      <section className="page-heading"><div><p className="eyebrow">Procurement register</p><h1>{config.title}</h1><p>{config.description}</p></div>{canCreate && <button className="primary-button" onClick={() => setCreateOpen(true)}><Plus size={17} />{{ supplier: "Add supplier", proforma: "New proforma", receipt: "Record delivery", invoice: "New invoice", requisition: "New requisition" }[config.create!]}</button>}</section>
      {notice && <div className="inline-success"><Check size={18} />{notice}<button className="icon-button" onClick={() => setNotice("")} title="Dismiss"><X size={15} /></button></div>}
      {error && <div className="inline-error"><AlertCircle size={18} />{error}<button className="secondary-button compact" onClick={() => void load()}>Try again</button></div>}
      <section className="register-band">
        <div className="register-toolbar">
          <form className="search-box" onSubmit={submitSearch}><Search size={17} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={config.searchPlaceholder} /><button type="submit" title="Search">Search</button></form>
          <div className="toolbar-actions">
            {config.statusOptions && <label className="select-control"><Filter size={16} /><select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}><option value="">All statuses</option>{config.statusOptions.map((option) => <option key={option} value={option}>{humanize(option)}</option>)}</select></label>}
            <button className="icon-button bordered" onClick={() => void load()} title="Refresh records"><RefreshCw size={17} className={loading ? "spin" : ""} /></button>
          </div>
        </div>
        <DataTable config={config} rows={rows} loading={loading} select={openRecord} />
        <footer className="table-footer"><span>{pagination.total !== undefined ? `${pagination.total.toLocaleString()} records` : `${rows.length} records`}</span><div className="pagination"><button className="icon-button bordered" disabled={page <= 1 || loading} onClick={() => setPage((value) => value - 1)} title="Previous page"><ChevronLeft size={17} /></button><span>Page {pagination.current_page ?? page}{pagination.last_page ? ` of ${pagination.last_page}` : ""}</span><button className="icon-button bordered" disabled={loading || (pagination.last_page !== undefined && page >= pagination.last_page)} onClick={() => setPage((value) => value + 1)} title="Next page"><ChevronRight size={17} /></button></div></footer>
      </section>
      {selected && moduleKey === "payments" && <PaymentVoucherModal item={selected} actions={config.actions ?? []} close={() => setSelected(null)} act={(action, item) => { setSelected(null); setPendingAction({ action, item }); }} />}
      {selected && moduleKey === "requisitions" && <RequisitionWorkspaceModal item={selected} actions={config.actions ?? []} close={() => setSelected(null)} edit={(item) => { setSelected(null); setEditRequisition(item); }} act={(action, item) => { setSelected(null); setPendingAction({ action, item }); }} />}
      {selected && moduleKey === "quotations" && <ProformaWorkspaceModal item={selected} actions={config.actions ?? []} close={() => setSelected(null)} act={(action, item) => { setSelected(null); setPendingAction({ action, item }); }} />}
      {selected && !["payments", "requisitions", "quotations", "suppliers"].includes(moduleKey) && <DetailDrawer item={selected} config={config} close={() => setSelected(null)} act={(action) => setPendingAction({ action, item: selected })} />}
      {pendingAction && <ActionDialog state={pendingAction} close={() => setPendingAction(null)} completed={(message) => { setPendingAction(null); setSelected(null); completed(message); }} />}
      {config.create === "supplier" && <SupplierDialog open={createOpen} close={() => setCreateOpen(false)} completed={completed} />}
      {config.create === "proforma" && <ProformaDialog open={createOpen} close={() => setCreateOpen(false)} completed={completed} />}
      {config.create === "receipt" && <ReceiptDialog open={createOpen} close={() => setCreateOpen(false)} completed={completed} />}
      {config.create === "invoice" && <InvoiceDialog open={createOpen} close={() => setCreateOpen(false)} completed={completed} />}
      {config.create === "requisition" && <RequisitionDialog open={createOpen || editRequisition !== null} requisition={editRequisition} close={() => { setCreateOpen(false); setEditRequisition(null); }} completed={(message) => { setEditRequisition(null); completed(message); }} />}
    </div>
  );
}

function DataTable({ config, rows, loading, select }: { config: ModuleConfig; rows: JsonRecord[]; loading: boolean; select: (item: JsonRecord) => void }) {
  return <div className="table-wrap"><table><thead><tr>{config.columns.map((column) => <th key={column.key}>{column.label}</th>)}<th className="action-column"><SlidersHorizontal size={15} /></th></tr></thead><tbody>
    {loading ? Array.from({ length: 7 }).map((_, index) => <tr key={index} className="skeleton-row">{config.columns.map((column) => <td key={column.key}><span /></td>)}<td><span /></td></tr>) : rows.map((item) => <tr key={String(item.id ?? JSON.stringify(item))} onClick={() => select(item)}>{config.columns.map((column) => <td key={column.key}>{renderCell(item, column)}</td>)}<td className="action-column"><button className="icon-button" onClick={(event) => { event.stopPropagation(); select(item); }} title="View record"><Eye size={17} /></button></td></tr>)}
    {!loading && rows.length === 0 && <tr><td colSpan={config.columns.length + 1}><div className="empty-state"><Search size={23} /><h3>No records found</h3><p>Try adjusting the filters or search terms.</p></div></td></tr>}
  </tbody></table></div>;
}

function renderCell(item: JsonRecord, column: Column) {
  const value = valueAt(item, column.key);
  if (column.format === "status") return <span className={`status status-${String(value).replaceAll("_", "-")}`}>{humanize(String(value ?? "unknown"))}</span>;
  if (column.format === "money") return <span className="money">{value == null ? "-" : `TZS ${Number(value).toLocaleString(undefined, { minimumFractionDigits: 2 })}`}</span>;
  if (column.format === "date") return value ? new Date(String(value)).toLocaleDateString(undefined, { day: "2-digit", month: "short", year: "numeric" }) : "-";
  if (column.format === "boolean") return <span className={value ? "availability active" : "availability inactive"}><span />{value ? "Active" : "Inactive"}</span>;
  const text = value == null || value === "" ? "-" : String(value);
  return text.length > 64 ? `${text.slice(0, 61)}...` : text;
}

function DetailDrawer({ item, config, close, act }: { item: JsonRecord; config: ModuleConfig; close: () => void; act: (action: ActionSpec) => void }) {
  const { user } = useAuth();
  const actions = user ? config.actions?.filter((action) => action.visible(item, user)) ?? [] : [];
  const details = Object.entries(item).filter(([, value]) => value == null || ["string", "number", "boolean"].includes(typeof value)).slice(0, 24);
  return <><div className="drawer-overlay" onClick={close} /><aside className="detail-drawer"><header><div><p className="eyebrow">Record detail</p><h2>{String(item.requisition_number ?? item.purchase_order_number ?? item.invoice_number ?? item.voucher_number ?? item.grn_number ?? item.name ?? config.title)}</h2></div><button className="icon-button" onClick={close} title="Close details"><X size={19} /></button></header>{actions.length > 0 && <div className="drawer-actions">{actions.map((action) => <button key={action.label} className={action.tone === "primary" ? "primary-button compact" : action.tone === "danger" ? "danger-button compact" : "secondary-button compact"} onClick={() => act(action)}>{action.label}</button>)}</div>}<div className="detail-list">{details.map(([key, value]) => <div key={key}><span>{humanize(key)}</span><strong>{key.includes("amount") || key.includes("total") ? `TZS ${Number(value ?? 0).toLocaleString()}` : typeof value === "boolean" ? (value ? "Yes" : "No") : String(value ?? "-")}</strong></div>)}</div><section className="related-summary"><h3>Related information</h3>{Object.entries(item).filter(([, value]) => value && typeof value === "object").slice(0, 8).map(([key, value]) => <div key={key}><span>{humanize(key)}</span><strong>{Array.isArray(value) ? `${value.length} records` : displayObject(value as JsonRecord)}</strong></div>)}</section></aside></>;
}

function ActionDialog({ state, close, completed }: { state: { action: ActionSpec; item: JsonRecord }; close: () => void; completed: (message: string) => void }) {
  const [input, setInput] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  async function submit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError("");
    try {
      const response = await api<JsonRecord>(state.action.path(state.item), { method: state.action.method ?? "POST", body: JSON.stringify(state.action.body ? state.action.body(input, state.item) : {}) });
      completed(String(response.message ?? `${state.action.label} completed.`));
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to complete action.");
    } finally { setSubmitting(false); }
  }
  return <div className="modal-backdrop"><section className="modal action-modal" role="dialog" aria-modal="true"><header className="modal-header"><div><p className="eyebrow">Workflow action</p><h2>{state.action.label}</h2><p>{state.action.prompt ?? "Confirm this action for the selected record."}</p></div><button className="icon-button" onClick={close} title="Close"><X size={19} /></button></header><form className="modal-body dialog-form" onSubmit={submit}>{error && <div className="form-alert">{error}</div>}{state.action.inputLabel && <label className="field"><span>{state.action.inputLabel}</span><textarea value={input} onChange={(event) => setInput(event.target.value)} rows={4} required={state.action.requiredInput || state.action.tone === "danger" || state.action.label === "Return"} autoFocus /></label>}<div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className={state.action.tone === "danger" ? "danger-button" : "primary-button"} disabled={submitting}>{submitting && <LoaderCircle className="spin" size={16} />}{state.action.label}</button></div></form></section></div>;
}

function displayObject(value: JsonRecord) {
  return String(value.name ?? value.requisition_number ?? value.purchase_order_number ?? value.invoice_number ?? value.code ?? "Available");
}

function humanize(value: string) {
  return value.replaceAll("_", " ").replace(/\b\w/g, (character) => character.toUpperCase());
}
