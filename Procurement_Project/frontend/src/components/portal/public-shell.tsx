import { Building2, HelpCircle, LogIn } from "lucide-react";
import Link from "next/link";

export function PublicHeader() {
  return <header className="public-header"><div className="portal-container header-inner">
    <Link className="public-brand" href="/"><span><Building2 size={22} /></span><div><strong>Procurement Portal</strong><small>Supplier & Tender Services</small></div></Link>
    <nav aria-label="Public navigation"><Link href="/">Home</Link><Link href="/tenders">Open tenders</Link><Link href="/supplier-registration">Become a supplier</Link><Link href="/help"><HelpCircle size={15} /> Help</Link><Link className="login-link" href="/supplier-login"><LogIn size={15} /> Supplier login</Link></nav>
  </div></header>;
}

export function PublicFooter() {
  return <footer className="public-footer"><div className="portal-container footer-grid"><div><strong>Procurement Office</strong><p>Transparent, accountable and accessible supplier sourcing.</p></div><div><strong>Contact</strong><p>procurement@example.co.tz<br />+255 000 000 000</p></div><div><strong>Information</strong><Link href="/help">Help & contact</Link><span>Privacy notice</span><span>Terms and conditions</span></div></div><div className="portal-container footer-bottom">© {new Date().getFullYear()} Procurement Portal. All rights reserved.</div></footer>;
}
