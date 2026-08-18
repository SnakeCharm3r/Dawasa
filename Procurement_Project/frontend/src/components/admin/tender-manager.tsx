"use client";

import { useAuth } from "@/components/auth-provider";
import { EmptyState, LoadingState, StatusBadge } from "@/components/ui/portal-ui";
import { api, ApiError, collectionFrom } from "@/lib/api";
import { formatDate } from "@/lib/formatters";
import type { Category } from "@/lib/portal-types";
import { ArrowRight, Plus, X } from "lucide-react";
import Link from "next/link";
import { FormEvent, useCallback, useEffect, useState } from "react";

type Tender = { id: number; tender_number: string; title: string; submission_deadline: string; status: string; responses_count?: number; submitted_responses_count?: number; category?: Category; requisition?: { requisition_number: string } };
type Req = { id: number; requisition_number: string; purpose: string };

export function TenderManager() {
  const { hasRole } = useAuth();
  const [rows, setRows] = useState<Tender[] | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [requisitions, setRequisitions] = useState<Req[]>([]);
  const [create, setCreate] = useState(false);
  const [message, setMessage] = useState("");
  const [currentTime, setCurrentTime] = useState(() => Date.now());
  const load = useCallback(() => api("admin/tenders").then((value) => setRows(collectionFrom(value).rows as unknown as Tender[])), []);

  useEffect(() => {
    void load();
    void api<{ data: Category[] }>("portal/supplier-categories").then((value) => setCategories(value.data));
    void api("admin/purchase-requisitions?status=approved_for_sourcing&per_page=50").then((value) => setRequisitions(collectionFrom(value).rows as unknown as Req[]));
  }, [load]);

  useEffect(() => {
    const timer = window.setInterval(() => setCurrentTime(Date.now()), 60_000);
    return () => window.clearInterval(timer);
  }, []);

  async function createTender(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    try {
      await api("admin/tenders", { method: "POST", body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget))) });
      setCreate(false);
      setMessage("Tender draft created using public-safe requisition items.");
      await load();
    } catch (caught) { setMessage(caught instanceof ApiError ? caught.message : "Could not create tender."); }
  }

  async function action(row: Tender, name: string) {
    let body: Record<string, string> = {};
    if (["publish", "cancel"].includes(name)) {
      const value = window.prompt(`${name === "cancel" ? "Reason" : "Publication comments"} (required)`);
      if (!value) return;
      body = name === "cancel" ? { reason: value } : { comments: value };
    }
    try {
      await api(`admin/tenders/${row.id}/${name}`, { method: "POST", body: JSON.stringify(body) });
      setMessage(`Tender ${name.replaceAll("-", " ")} completed.`);
      await load();
    } catch (caught) { setMessage(caught instanceof ApiError ? caught.message : "Action failed."); }
  }

  return <div className="page-stack">
    <header className="page-heading"><div><span className="eyebrow">Sourcing</span><h1>Tenders and RFQs</h1><p>Create public requests, approve publication, evaluate sealed bids, and allocate the winner.</p></div><button className="primary-button" onClick={() => setCreate(true)}><Plus size={17} />New tender</button></header>
    {message && <div className="inline-success">{message}</div>}
    <section className="table-card">{rows === null ? <LoadingState /> : rows.length === 0 ? <EmptyState title="No tenders" copy="Choose Other suppliers while creating a proforma, or create a tender here." /> : <div className="table-wrap"><table><thead><tr><th>Tender</th><th>Requisition</th><th>Category</th><th>Deadline</th><th>Bids</th><th>Status</th><th>Actions</th></tr></thead><tbody>{rows.map((row) => <tr key={row.id}><td><strong>{row.tender_number}</strong><small>{row.title}</small></td><td>{row.requisition?.requisition_number}</td><td>{row.category?.name}</td><td>{formatDate(row.submission_deadline)}</td><td><strong>{row.submitted_responses_count ?? 0}</strong><small>{row.responses_count ?? 0} including drafts</small></td><td><StatusBadge status={row.status} /></td><td><div className="admin-row-actions">
      {row.status === "draft" && <button onClick={() => void action(row, "submit-publication")}>Submit to GM</button>}
      {row.status === "pending_publication" && hasRole("gm", "super_admin") && <button onClick={() => void action(row, "publish")}>Approve & publish</button>}
      {row.status === "published" && new Date(row.submission_deadline).getTime() <= currentTime && <button onClick={() => void action(row, "close")}>Close bidding</button>}
      {["published", "closed", "evaluation_in_progress", "awarded"].includes(row.status) && <Link className="table-action" href={`/admin-tenders/${row.id}`}>Manage bids <ArrowRight size={13} /></Link>}
      {!['awarded', 'cancelled'].includes(row.status) && hasRole("gm", "super_admin") && <button className="danger" onClick={() => void action(row, "cancel")}>Cancel</button>}
    </div></td></tr>)}</tbody></table></div>}</section>
    {create && <TenderDialog categories={categories} requisitions={requisitions} close={() => setCreate(false)} submit={createTender} />}
  </div>;
}

function TenderDialog({ categories, requisitions, close, submit }: { categories: Category[]; requisitions: Req[]; close: () => void; submit: (event: FormEvent<HTMLFormElement>) => void }) {
  return <div className="modal-backdrop"><div className="modal large"><header className="modal-header"><div><span className="eyebrow">Public-safe sourcing</span><h2>Create tender draft</h2></div><button className="icon-button" onClick={close}><X /></button></header><form className="modal-body dialog-form" onSubmit={submit}><div className="portal-form-grid two"><label className="portal-field full"><span>Approved requisition</span><select name="purchase_requisition_id" required><option value="">Select requisition</option>{requisitions.map((req) => <option value={req.id} key={req.id}>{req.requisition_number} — {req.purpose}</option>)}</select></label><label className="portal-field"><span>Category</span><select name="supplier_category_id" required>{categories.map((category) => <option value={category.id} key={category.id}>{category.name}</option>)}</select></label><label className="portal-field"><span>Tender type</span><select name="tender_type"><option value="rfq">RFQ</option><option value="open_tender">Open tender</option><option value="restricted_rfq">Restricted RFQ</option></select></label><label className="portal-field"><span>Visibility</span><select name="visibility"><option value="public">Public</option><option value="invited_only">Invited suppliers only</option></select></label><label className="portal-field"><span>Submission deadline</span><input name="submission_deadline" type="datetime-local" required /></label><label className="portal-field full"><span>Title</span><input name="title" required /></label><label className="portal-field full"><span>Public summary</span><textarea name="public_summary" required /></label><label className="portal-field"><span>Expected delivery date</span><input type="date" name="expected_delivery_date" /></label><label className="portal-field"><span>Delivery location</span><input name="delivery_location" /></label><label className="portal-field"><span>Procurement contact email</span><input name="contact_email" type="email" required /></label><label className="portal-field"><span>Procurement contact phone</span><input name="contact_phone" /></label><label className="portal-field full"><span>Eligibility requirements</span><textarea name="eligibility_requirements" /></label><label className="portal-field full"><span>Submission instructions</span><textarea name="submission_instructions" /></label><label className="portal-field full"><span>Terms and conditions</span><textarea name="terms_and_conditions" /></label></div><div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button">Create draft</button></div></form></div></div>;
}
