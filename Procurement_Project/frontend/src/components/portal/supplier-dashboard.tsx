"use client";

import { EmptyState, LoadingState, StatusBadge } from "@/components/ui/portal-ui";
import { api } from "@/lib/api";
import { AlertTriangle, CheckCircle2, FileCheck2, FileText } from "lucide-react";
import Link from "next/link";
import { useEffect, useState } from "react";

type Compliance = {
  status: string;
  required_count: number;
  valid_count: number;
  score: number;
  missing_documents: string[];
  expired_documents: string[];
  rejected_documents: string[];
  expiring_documents: Record<string, string[]>;
};

type Dashboard = {
  supplier: {
    name: string;
    portal_status: string;
    documents: { id: number; status: string; expires_at: string | null }[];
  };
  compliance: Compliance;
  profile_completion: number;
  response_counts: Record<string, number>;
  expiring_document_count: number;
};

const label = (value: string) => value.replaceAll("_", " ");

export function SupplierDashboard() {
  const [data, setData] = useState<Dashboard | null>(null);

  useEffect(() => {
    void api<{ data: Dashboard }>("supplier-portal/dashboard").then((result) => setData(result.data));
  }, []);

  if (!data) return <LoadingState label="Loading dashboard" />;

  const attention = [
    ...data.compliance.missing_documents.map((item) => ({ item, reason: "Missing" })),
    ...data.compliance.expired_documents.map((item) => ({ item, reason: "Expired" })),
    ...data.compliance.rejected_documents.map((item) => ({ item, reason: "Rejected" })),
  ];

  return (
    <div className="supplier-page">
      <header className="supplier-page-heading">
        <div>
          <span className="eyebrow">Supplier workspace</span>
          <h1>Welcome, {data.supplier.name}</h1>
          <p>Keep your company compliant and respond to matching procurement opportunities.</p>
        </div>
        <StatusBadge status={data.supplier.portal_status} />
      </header>

      <div className="supplier-kpis">
        <article><CheckCircle2 /><div><span>Profile completion</span><strong>{data.profile_completion}%</strong></div></article>
        <article><FileCheck2 /><div><span>Verified requirements</span><strong>{data.compliance.valid_count}/{data.compliance.required_count}</strong></div></article>
        <article><FileText /><div><span>Submitted responses</span><strong>{data.response_counts.submitted ?? 0}</strong></div></article>
        <article className={data.expiring_document_count ? "warn" : ""}><AlertTriangle /><div><span>Documents expiring</span><strong>{data.expiring_document_count}</strong></div></article>
      </div>

      <section className="supplier-panel supplier-compliance-card">
        <div className="panel-heading">
          <div><h2>Compliance readiness</h2><p>Your document readiness for sourcing participation. Internal supplier ratings are not displayed here.</p></div>
          <StatusBadge status={data.compliance.status} />
        </div>
        <div className="supplier-compliance-meter" aria-label={`${data.compliance.score}% compliant`}>
          <i style={{ width: `${data.compliance.score}%` }} />
        </div>
        <small>{data.compliance.score}% of mandatory evidence is verified and valid.</small>
      </section>

      <section className="supplier-panel">
        <div className="panel-heading"><div><h2>Next actions</h2><p>Complete these items to remain ready for sourcing.</p></div></div>
        {data.profile_completion < 100 || attention.length > 0 ? (
          <div className="action-list">
            {data.profile_completion < 100 && <Link href="/profile"><UserAction title="Complete company profile" copy="Add all required company and category information." /></Link>}
            {attention.map(({ item, reason }) => <Link href="/documents" key={`${reason}-${item}`}><UserAction title={`${reason}: ${label(item)}`} copy="Upload or replace the evidence and wait for procurement verification." /></Link>)}
          </div>
        ) : <EmptyState title="You are up to date" copy="No supplier actions require attention." />}
      </section>
    </div>
  );
}

function UserAction({ title, copy }: { title: string; copy: string }) {
  return <><span><strong>{title}</strong><small>{copy}</small></span><b>Continue →</b></>;
}
