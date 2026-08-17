"use client";

import { EmptyState, LoadingState, StatusBadge } from "@/components/ui/portal-ui";
import { api, collectionFrom } from "@/lib/api";
import { formatDate, statusLabel } from "@/lib/formatters";
import type { Category, Tender } from "@/lib/portal-types";
import { CalendarClock, Search, Tag } from "lucide-react";
import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";

export function TenderDirectory() {
  const [tenders, setTenders] = useState<Tender[]>([]); const [categories, setCategories] = useState<Category[]>([]);
  const [search, setSearch] = useState(""); const [category, setCategory] = useState(""); const [type, setType] = useState("");
  const [loading, setLoading] = useState(true); const [error, setError] = useState("");
  async function load(event?: FormEvent) {
    event?.preventDefault(); setLoading(true); setError("");
    try { const params = new URLSearchParams({ search, category, type }); const payload = await api(`portal/tenders?${params}`); setTenders(collectionFrom(payload).rows as Tender[]); }
    catch (caught) { setError(caught instanceof Error ? caught.message : "Could not load tenders."); } finally { setLoading(false); }
  }
  useEffect(() => { void load(); void api<{ data: Category[] }>("portal/supplier-categories").then((value) => setCategories(value.data)); }, []); // eslint-disable-line react-hooks/exhaustive-deps
  return <>
    <form className="tender-filters" onSubmit={load}><label><Search size={17} /><input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search number or title" /></label><select value={category} onChange={(e) => setCategory(e.target.value)}><option value="">All categories</option>{categories.map((item) => <option value={item.code} key={item.id}>{item.name}</option>)}</select><select value={type} onChange={(e) => setType(e.target.value)}><option value="">All tender types</option><option value="rfq">RFQ</option><option value="open_tender">Open tender</option><option value="restricted_rfq">Restricted RFQ</option></select><button className="portal-button primary">Apply filters</button></form>
    {error && <div className="portal-alert error">{error}</div>}{loading ? <LoadingState label="Loading opportunities" /> : tenders.length === 0 ? <EmptyState title="No open tenders" copy="There are no opportunities matching these filters right now." /> : <div className="tender-grid">{tenders.map((tender) => <article className="tender-card" key={tender.id}><div className="tender-card-top"><StatusBadge status={tender.status} /><span>{statusLabel(tender.tender_type)}</span></div><p className="tender-number">{tender.tender_number}</p><h2>{tender.title}</h2><p className="tender-summary">{tender.public_summary}</p><dl><div><Tag size={15} /><dt>Category</dt><dd>{tender.category?.name ?? "General"}</dd></div><div><CalendarClock size={15} /><dt>Deadline</dt><dd>{formatDate(tender.submission_deadline)}</dd></div></dl><Link className="card-link" href={`/tenders/${tender.tender_number}`}>View tender →</Link></article>)}</div>}
  </>;
}
