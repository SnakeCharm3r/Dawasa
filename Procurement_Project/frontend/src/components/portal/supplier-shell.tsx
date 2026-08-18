"use client";

import { useAuth } from "@/components/auth-provider";
import { Building2, FileCheck2, FileText, LayoutDashboard, LogOut, Menu, UserRound, X } from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";

const navigation = [
  [LayoutDashboard, "Overview", "/supplier-dashboard"],
  [UserRound, "Company profile", "/profile"],
  [FileCheck2, "Documents", "/documents"],
  [FileText, "Available tenders", "/supplier-tenders"],
  [FileText, "My responses", "/tender-responses"],
] as const;

export function SupplierShell({ children }: { children: React.ReactNode }) {
  const { user, loading, logout } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const [open, setOpen] = useState(false);

  useEffect(() => {
    if (!loading && (!user || !user.roles.includes("supplier"))) router.replace("/supplier-login");
  }, [loading, router, user]);

  useEffect(() => setOpen(false), [pathname]);

  if (loading || !user?.roles.includes("supplier")) {
    return <div className="portal-state full-page">Loading supplier portal…</div>;
  }

  const sidebar = <>
    <header>
      <Link href="/supplier-dashboard" className="supplier-brand"><Building2 size={20} /><span><strong>Supplier Portal</strong><small>{user.supplier?.application_reference}</small></span></Link>
      <button onClick={() => setOpen(false)} aria-label="Close navigation"><X size={18} /></button>
    </header>
    <nav aria-label="Supplier portal navigation">
      {navigation.map(([Icon, label, href]) => {
        const active = pathname === href || pathname.startsWith(`${href}/`);
        return <Link className={active ? "active" : ""} href={href} key={href} aria-current={active ? "page" : undefined}><Icon size={17} /><span>{label}</span></Link>;
      })}
    </nav>
    <footer>
      <div><strong>{user.supplier?.name}</strong><small>{user.email}</small></div>
      <button onClick={() => void logout()} title="Sign out" aria-label="Sign out"><LogOut size={17} /></button>
    </footer>
  </>;

  return <div className="supplier-workspace">
    <aside className="supplier-sidebar">{sidebar}</aside>
    {open && <><div className="supplier-overlay" onClick={() => setOpen(false)} /><aside className="supplier-sidebar mobile">{sidebar}</aside></>}
    <div className="supplier-main">
      <header className="supplier-topbar"><button onClick={() => setOpen(true)} aria-label="Open navigation"><Menu size={20} /></button><div><span>Supplier status</span><strong>{user.supplier?.status.replaceAll("_", " ")}</strong></div><Link href="/tenders">Public tenders</Link></header>
      <main>{children}</main>
    </div>
  </div>;
}
