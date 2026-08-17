"use client";
import { useAuth } from "@/components/auth-provider";
import { Building2, FileCheck2, FileText, LayoutDashboard, LogOut, Menu, UserRound, X } from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";

const nav = [[LayoutDashboard, "Overview", "/supplier-dashboard"], [UserRound, "Company profile", "/profile"], [FileCheck2, "Documents", "/documents"], [FileText, "Available tenders", "/supplier-tenders"], [FileText, "My responses", "/tender-responses"]] as const;
export function SupplierShell({ children }: { children: React.ReactNode }) {
  const { user, loading, logout } = useAuth(); const router = useRouter(); const pathname = usePathname(); const [open, setOpen] = useState(false);
  const [verificationMessage, setVerificationMessage] = useState("");
  useEffect(() => { if (!loading && (!user || !user.roles.includes("supplier"))) router.replace("/supplier-login"); }, [loading, router, user]);
  if (loading || !user?.roles.includes("supplier")) return <div className="portal-state full-page">Loading secure portal…</div>;
  const sidebar = <><header><Link href="/supplier-dashboard"><Building2 size={22} /><span><strong>Supplier Portal</strong><small>{user.supplier?.application_reference}</small></span></Link><button onClick={() => setOpen(false)}><X size={19} /></button></header><nav>{nav.map(([Icon, label, href]) => <Link className={pathname === href || pathname.startsWith(`${href}/`) ? "active" : ""} href={href} key={href}><Icon size={18} />{label}</Link>)}</nav><footer><div><strong>{user.supplier?.name}</strong><small>{user.email}</small></div><button onClick={() => void logout()} title="Sign out"><LogOut size={18} /></button></footer></>;
  return <div className="supplier-workspace"><aside className="supplier-sidebar">{sidebar}</aside>{open && <><div className="supplier-overlay" onClick={() => setOpen(false)} /><aside className="supplier-sidebar mobile">{sidebar}</aside></>}<div className="supplier-main"><header className="supplier-topbar"><button onClick={() => setOpen(true)}><Menu size={20} /></button><div><span>Supplier status</span><strong>{user.supplier?.status.replaceAll("_", " ")}</strong></div><Link href="/tenders">Public tenders</Link></header>{!user.email_verified_at && <div className="verification-banner"><span><strong>Verify your company email</strong>{verificationMessage || "Open the verification link sent during registration."}</span><button onClick={() => void api("supplier-portal/email/verification-notification", { method: "POST" }).then((result) => setVerificationMessage(String(result.message)))}>Resend email</button></div>}<main>{children}</main></div></div>;
}
