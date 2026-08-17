import { PublicFooter, PublicHeader } from "@/components/portal/public-shell";
import { ArrowRight, BadgeCheck, FileText, Send, ShieldCheck } from "lucide-react";
import Link from "next/link";

export default function Home() {
  return <div className="portal-site">
    <PublicHeader />
    <main>
      <section className="portal-hero"><div className="portal-container hero-grid"><div>
        <span className="eyebrow">Official procurement opportunities</span><h1>Supplier and Tender Portal</h1>
        <p>Discover current opportunities, register your business, maintain compliant documents, and submit secure quotations online.</p>
        <div className="hero-actions"><Link className="portal-button primary" href="/tenders">View open tenders <ArrowRight size={17} /></Link><Link className="portal-button secondary" href="/supplier-registration">Become a supplier</Link></div>
      </div><aside className="trust-card"><ShieldCheck size={32} /><h2>A fair, secure sourcing process</h2><p>Your company documents and pricing remain private. Submitted quotations are sealed from other suppliers and internal evaluation begins only after closure.</p></aside></div></section>
      <section className="portal-section portal-container"><div className="section-intro"><span className="eyebrow">Simple process</span><h2>From registration to quotation</h2></div><div className="process-grid">
        {[[FileText, "01", "Register", "Create your company profile and upload supporting documents."], [BadgeCheck, "02", "Get verified", "Our authorised team reviews your eligibility and compliance."], [Send, "03", "Submit quotation", "Respond securely to matching opportunities before the deadline."]].map(([Icon, number, title, copy]) => <article className="process-card" key={String(number)}><Icon size={24} /><span>{String(number)}</span><h3>{String(title)}</h3><p>{String(copy)}</p></article>)}
      </div></section>
      <section className="portal-cta"><div className="portal-container"><div><span className="eyebrow light">Open opportunities</span><h2>Ready to work with us?</h2><p>Browse tenders without an account. Register when you are ready to submit.</p></div><Link className="portal-button white" href="/tenders">Browse tenders</Link></div></section>
    </main><PublicFooter />
  </div>;
}
