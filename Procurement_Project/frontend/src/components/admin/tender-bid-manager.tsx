"use client";

import { EmptyState, LoadingState, StatusBadge } from "@/components/ui/portal-ui";
import { api, ApiError, collectionFrom } from "@/lib/api";
import type { JsonRecord } from "@/lib/types";
import { ArrowLeft, CheckCircle2, LockKeyhole } from "lucide-react";
import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";

export function TenderBidManager({ tenderId }: { tenderId: string }) {
  const [tender, setTender] = useState<JsonRecord | null>(null);
  const [responses, setResponses] = useState<JsonRecord[] | null>(null);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [currentTime, setCurrentTime] = useState(() => Date.now());

  const load = useCallback(async () => {
    setError("");
    try {
      const detail = await api<{ data: JsonRecord }>(`admin/tenders/${tenderId}`);
      setTender(detail.data);
      const deadlinePassed = new Date(String(detail.data.submission_deadline)).getTime() <= Date.now();
      if (deadlinePassed || !["draft", "pending_publication", "published"].includes(String(detail.data.status))) {
        const bidData = await api(`admin/tenders/${tenderId}/responses?per_page=100`);
        setResponses(collectionFrom(bidData).rows);
      } else {
        setResponses([]);
      }
    } catch (caught) { setError(caught instanceof ApiError ? caught.message : "Unable to load tender bids."); }
  }, [tenderId]);

  useEffect(() => { void load(); }, [load]);
  useEffect(() => {
    const timer = window.setInterval(() => setCurrentTime(Date.now()), 60_000);
    return () => window.clearInterval(timer);
  }, []);
  const deadlinePassed = useMemo(() => tender ? new Date(String(tender.submission_deadline)).getTime() <= currentTime : false, [currentTime, tender]);

  async function closeBidding() {
    try {
      await api(`admin/tenders/${tenderId}/close`, { method: "POST", body: "{}" });
      setMessage("Bidding closed. The submitted responses are ready for evaluation.");
      await load();
    } catch (caught) { setError(caught instanceof ApiError ? caught.message : "Could not close bidding."); }
  }

  async function review(response: JsonRecord, decision: "compliant" | "non_compliant") {
    const comments = window.prompt(`${decision === "compliant" ? "Compliance" : "Non-compliance"} comments (required)`);
    if (!comments) return;
    try {
      await api(`admin/tender-responses/${Number(response.id)}/compliance`, { method: "POST", body: JSON.stringify({ decision, comments }) });
      setMessage("Bid evaluation recorded and the requisition supplier option was updated.");
      await load();
    } catch (caught) { setError(caught instanceof ApiError ? caught.message : "Could not record the evaluation."); }
  }

  async function award(response: JsonRecord) {
    const comments = window.prompt("Award decision comments (required)");
    if (!comments || !window.confirm(`Select ${String((response.supplier as JsonRecord | undefined)?.name ?? "this supplier")} as the tender winner and notify them?`)) return;
    try {
      await api(`admin/tenders/${tenderId}/responses/${Number(response.id)}/award`, { method: "POST", body: JSON.stringify({ comments }) });
      setMessage("Winner selected and notified. The requisition is now available to the requester with all supplier options.");
      await load();
    } catch (caught) { setError(caught instanceof ApiError ? caught.message : "Could not award this bid."); }
  }

  if (!tender && !error) return <LoadingState label="Loading tender and bids" />;
  if (!tender) return <div className="page-stack"><Link className="detail-back-link" href="/admin-tenders"><ArrowLeft size={15} />Back to tenders</Link><div className="form-alert">{error}</div></div>;
  const requisition = (tender.requisition as JsonRecord | undefined) ?? {};

  return <div className="page-stack tender-bid-page">
    <Link className="detail-back-link" href="/admin-tenders"><ArrowLeft size={15} />Back to tenders</Link>
    <header className="page-heading"><div><span className="eyebrow">{String(tender.tender_number)}</span><h1>{String(tender.title)}</h1><p>Requisition {String(requisition.requisition_number ?? "-")} · deadline {formatDateTime(tender.submission_deadline)}</p></div><StatusBadge status={String(tender.status)} /></header>
    {message && <div className="inline-success">{message}</div>}{error && <div className="form-alert">{error}</div>}
    {!deadlinePassed ? <section className="tender-sealed-state"><LockKeyhole size={28} /><div><h2>Bids remain sealed</h2><p>Supplier prices and documents will be available after {formatDateTime(tender.submission_deadline)}.</p></div></section> : <>
      {String(tender.status) === "published" && <section className="tender-close-callout"><div><strong>The bid deadline has passed</strong><p>Close bidding to begin the formal evaluation.</p></div><button className="primary-button" onClick={() => void closeBidding()}>Close bidding</button></section>}
      <section className="table-card">{responses === null ? <LoadingState /> : responses.length === 0 ? <EmptyState title="No bids submitted" copy="No supplier submitted a locked response before the deadline." /> : <div className="table-wrap"><table><thead><tr><th>Supplier</th><th>Bid / receipt</th><th>Amount</th><th>Documents</th><th>Evaluation</th><th>Award</th><th>Actions</th></tr></thead><tbody>{responses.map((response) => {
        const supplier = (response.supplier as JsonRecord | undefined) ?? {};
        const documents = (response.documents as JsonRecord[] | undefined) ?? [];
        return <tr key={String(response.id)}><td><strong>{String(supplier.name ?? "Supplier")}</strong><small>{String(supplier.code ?? "-")} · {String(supplier.email ?? "")}</small></td><td><strong>{String(response.quotation_number ?? "-")}</strong><small>{String(response.receipt_number ?? "Draft")}</small></td><td><strong>{money(response.total_amount)}</strong><small>Delivery: {String(response.delivery_period_days ?? "-")} days</small></td><td>{documents.length}<small>{documents.map((document) => String(document.original_name)).join(", ") || "No files"}</small></td><td><StatusBadge status={String(response.status)} />{Boolean(response.compliance_comments) && <small>{String(response.compliance_comments)}</small>}</td><td><StatusBadge status={String(response.award_status ?? "pending")} /></td><td><div className="admin-row-actions">{response.status === "submitted" && ["closed", "evaluation_in_progress"].includes(String(tender.status)) && <><button onClick={() => void review(response, "compliant")}>Compliant</button><button className="danger" onClick={() => void review(response, "non_compliant")}>Non-compliant</button></>}{response.status === "compliant" && ["closed", "evaluation_in_progress"].includes(String(tender.status)) && <button onClick={() => void award(response)}><CheckCircle2 size={13} />Select winner</button>}</div></td></tr>;
      })}</tbody></table></div>}</section>
    </>}
  </div>;
}

function money(value: unknown) { return `TZS ${Number(value ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`; }
function formatDateTime(value: unknown) { const date = new Date(String(value)); return Number.isNaN(date.getTime()) ? String(value ?? "-") : date.toLocaleString(); }
