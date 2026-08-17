import { PublicFooter, PublicHeader } from "@/components/portal/public-shell";
import { TenderDirectory } from "@/components/portal/tender-directory";

export default function TendersPage() { return <div className="portal-site"><PublicHeader /><main><section className="page-banner"><div className="portal-container"><span className="eyebrow light">Procurement opportunities</span><h1>Open tenders and RFQs</h1><p>Review specifications and submission deadlines. Internal estimates and supplier pricing are never published.</p></div></section><section className="portal-section portal-container"><TenderDirectory /></section></main><PublicFooter /></div>; }
