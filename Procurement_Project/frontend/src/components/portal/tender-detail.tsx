"use client";

import { useAuth } from "@/components/auth-provider";
import { LoadingState, StatusBadge } from "@/components/ui/portal-ui";
import { api } from "@/lib/api";
import { formatDate, statusLabel } from "@/lib/formatters";
import type { Tender } from "@/lib/portal-types";
import { CalendarClock, MapPin, ShieldCheck } from "lucide-react";
import Link from "next/link";
import { useEffect, useState } from "react";

export function TenderDetail({ tenderNumber }: { tenderNumber: string }) {
  const { user } = useAuth(); const [tender, setTender] = useState<Tender | null>(null); const [error, setError] = useState("");
  useEffect(() => { void api<{ data: Tender }>(`portal/tenders/${tenderNumber}`).then((value) => setTender(value.data)).catch((caught) => setError(caught instanceof Error ? caught.message : "Tender unavailable.")); }, [tenderNumber]);
  if (error) return <div className="portal-state"><h2>Tender unavailable</h2><p>{error}</p></div>; if (!tender) return <LoadingState label="Loading tender" />;
  const closed = tender.status === "closed" || new Date(tender.submission_deadline) <= new Date();
  return <article className="tender-detail"><header><div><div className="detail-badges"><StatusBadge status={closed ? "closed" : tender.status} /><span>{statusLabel(tender.tender_type)}</span></div><p className="tender-number">{tender.tender_number}</p><h1>{tender.title}</h1><p>{tender.public_summary}</p></div><aside><CalendarClock size={20} /><span>Submission deadline</span><strong>{formatDate(tender.submission_deadline)}</strong>{tender.delivery_location && <small><MapPin size={14} /> {tender.delivery_location}</small>}</aside></header>
    <section><h2>Items and specifications</h2><div className="portal-table-wrap"><table className="portal-table"><thead><tr><th>Item</th><th>Specification</th><th>Quantity</th><th>Unit</th></tr></thead><tbody>{tender.items?.map((item) => <tr key={item.id}><td><strong>{item.item_name}</strong></td><td>{item.specification ?? "—"}</td><td>{item.quantity}</td><td>{item.unit}</td></tr>)}</tbody></table></div></section>
    <div className="detail-columns"><section><h2>Eligibility requirements</h2><p className="preline">{tender.eligibility_requirements || "Standard supplier verification requirements apply."}</p></section><section><h2>Submission instructions</h2><p className="preline">{tender.submission_instructions || "Register or sign in to complete the secure quotation form."}</p></section></div>
    {tender.terms_and_conditions && <section><h2>Terms and conditions</h2><p className="preline">{tender.terms_and_conditions}</p></section>}
    <footer><ShieldCheck size={20} /><div><strong>Secure supplier submission</strong><p>Your response is private and locked after submission.</p></div>{closed ? <button className="portal-button disabled" disabled>Tender closed</button> : user?.roles.includes("supplier") ? <Link className="portal-button primary" href={`/supplier-tenders/${tender.id}`}>Submit quotation</Link> : <><Link className="portal-button secondary" href="/supplier-registration">Register to submit</Link><Link className="portal-button primary" href="/supplier-login">Login to submit</Link></>}</footer>
  </article>;
}
