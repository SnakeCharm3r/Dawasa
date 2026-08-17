"use client";

import { useAuth } from "@/components/auth-provider";
import { useEntityScope } from "@/components/entity-scope-provider";
import type { Role } from "@/lib/types";
import {
  BadgeDollarSign,
  BarChart3,
  Boxes,
  Building2,
  ChevronRight,
  ClipboardCheck,
  FileCheck2,
  FileText,
  LayoutDashboard,
  LogOut,
  Menu,
  PackageCheck,
  PanelLeftClose,
  ReceiptText,
  Settings,
  ShieldCheck,
  ShoppingCart,
  Truck,
  Megaphone,
  UserCheck,
  Users,
  X,
} from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useMemo, useState } from "react";

type NavItem = {
  label: string;
  href: string;
  icon: React.ComponentType<{ size?: number; strokeWidth?: number }>;
  roles?: Role[];
};

const workflowItems: NavItem[] = [
  { label: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { label: "Requisitions", href: "/requisitions", icon: ClipboardCheck, roles: ["super_admin", "gm", "ceo", "accountant", "procurement_officer", "department_head", "requester", "auditor", "line_manager"] },
  { label: "Proformas", href: "/quotations", icon: FileText, roles: ["super_admin", "procurement_officer", "gm", "accountant", "auditor", "requester", "line_manager", "department_head"] },
  { label: "LPOs", href: "/purchase-orders", icon: ShoppingCart, roles: ["super_admin", "gm", "accountant", "procurement_officer", "department_head", "line_manager", "requester", "auditor", "storekeeper", "receiving_officer"] },
  { label: "Delivery & store receipt", href: "/goods-receipts", icon: PackageCheck, roles: ["super_admin", "gm", "accountant", "procurement_officer", "department_head", "requester", "auditor", "storekeeper", "receiving_officer"] },
  { label: "Supplier invoices", href: "/invoices", icon: ReceiptText, roles: ["super_admin", "gm", "accountant", "procurement_officer", "department_head", "line_manager", "requester", "auditor"] },
  { label: "Payments", href: "/payments", icon: BadgeDollarSign, roles: ["super_admin", "accountant", "gm", "auditor"] },
  { label: "Closures", href: "/closures", icon: FileCheck2, roles: ["super_admin", "gm", "accountant", "procurement_officer", "department_head", "requester", "auditor"] },
];

const managementItems: NavItem[] = [
  { label: "Budgets", href: "/budgets", icon: Boxes, roles: ["accountant", "gm", "ceo"] },
  { label: "Suppliers", href: "/suppliers", icon: Truck, roles: ["super_admin", "procurement_officer", "accountant", "gm", "auditor"] },
  { label: "Supplier verification", href: "/supplier-verification", icon: UserCheck, roles: ["super_admin", "gm"] },
  { label: "Tenders & RFQs", href: "/admin-tenders", icon: Megaphone, roles: ["super_admin", "procurement_officer", "gm"] },
  { label: "Reports", href: "/reports", icon: BarChart3, roles: ["super_admin", "gm", "accountant", "procurement_officer", "auditor"] },
];

const adminItems: NavItem[] = [
  { label: "Business entities", href: "/entities", icon: Building2, roles: ["super_admin", "gm", "accountant", "auditor"] },
  { label: "Departments", href: "/departments", icon: Settings, roles: ["super_admin", "gm", "accountant", "auditor"] },
  { label: "Users", href: "/users", icon: Users, roles: ["super_admin", "gm"] },
];

function initials(name: string) {
  return name.split(" ").slice(0, 2).map((part) => part[0]).join("").toUpperCase();
}

export function AppShell({ children }: { children: React.ReactNode }) {
  const { user, loading, logout } = useAuth();
  const { entities, selectedEntityId, setSelectedEntityId } = useEntityScope();
  const pathname = usePathname();
  const router = useRouter();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(false);

  useEffect(() => {
    if (!loading && (!user || user.roles.includes("supplier"))) router.replace("/login");
  }, [loading, router, user]);

  const visible = useMemo(() => {
    const isCeo = user?.roles.includes("ceo");
    const filter = (items: NavItem[]) => items.filter((item) => isCeo || !item.roles || item.roles.some((role) => user?.roles.includes(role)));
    return { workflow: filter(workflowItems), management: filter(managementItems), admin: filter(adminItems) };
  }, [user]);

  const pageTitle = [...workflowItems, ...managementItems, ...adminItems]
    .find((item) => pathname === item.href)?.label ?? "Procurement";

  if (loading || !user) {
    return <div className="app-loader"><div className="spinner" /><span>Loading workspace</span></div>;
  }

  const nav = (
    <>
      <div className="brand-row">
        <Link href="/dashboard" className="brand" aria-label="Procurement home">
          <span className="brand-mark"><ShieldCheck size={20} /></span>
          {!collapsed && <span><strong>Procure</strong><small>Control office</small></span>}
        </Link>
        <button className="icon-button mobile-only" onClick={() => setMobileOpen(false)} title="Close navigation"><X size={19} /></button>
      </div>
      <nav className="sidebar-nav" aria-label="Primary navigation">
        <NavSection label="Workflow" items={visible.workflow} pathname={pathname} collapsed={collapsed} close={() => setMobileOpen(false)} />
        <NavSection label="Management" items={visible.management} pathname={pathname} collapsed={collapsed} close={() => setMobileOpen(false)} />
        {visible.admin.length > 0 && <NavSection label="Administration" items={visible.admin} pathname={pathname} collapsed={collapsed} close={() => setMobileOpen(false)} />}
      </nav>
      <div className="sidebar-user">
        <span className="avatar">{initials(user.name)}</span>
        {!collapsed && <span className="user-copy"><strong>{user.name}</strong><small>{user.job_title ?? user.roles[0]?.replaceAll("_", " ")}</small></span>}
        <button className="icon-button" onClick={() => void logout()} title="Sign out"><LogOut size={18} /></button>
      </div>
    </>
  );

  return (
    <div className={collapsed ? "app-frame sidebar-collapsed" : "app-frame"}>
      <aside className="sidebar desktop-sidebar">{nav}</aside>
      {mobileOpen && <div className="mobile-overlay" onClick={() => setMobileOpen(false)} />}
      <aside className={mobileOpen ? "sidebar mobile-sidebar open" : "sidebar mobile-sidebar"}>{nav}</aside>
      <div className="app-main">
        <header className="topbar">
          <div className="topbar-start">
            <button className="icon-button mobile-only" onClick={() => setMobileOpen(true)} title="Open navigation"><Menu size={20} /></button>
            <button className="icon-button desktop-only" onClick={() => setCollapsed((value) => !value)} title={collapsed ? "Expand navigation" : "Collapse navigation"}><PanelLeftClose size={19} /></button>
            <span className="crumb"><span>Workspace</span><ChevronRight size={15} /><strong>{pageTitle}</strong></span>
          </div>
          <div className="topbar-context">
            <span className="context-dot" />
            {user.roles.includes("ceo") ? <select aria-label="Business entity" value={selectedEntityId} onChange={(event) => setSelectedEntityId(event.target.value)}><option value="">All business entities</option>{entities.map((entity) => <option value={entity.id} key={entity.id}>{entity.name} ({entity.code})</option>)}</select> : <span>{user.department?.business_entity?.name ?? "Procurement Group"}</span>}
          </div>
        </header>
        <main className="content">{children}</main>
      </div>
    </div>
  );
}

function NavSection({ label, items, pathname, collapsed, close }: { label: string; items: NavItem[]; pathname: string; collapsed: boolean; close: () => void }) {
  if (items.length === 0) return null;
  return (
    <div className="nav-section">
      {!collapsed && <p className="nav-label">{label}</p>}
      {items.map((item) => {
        const Icon = item.icon;
        const active = pathname === item.href;
        return (
          <Link key={item.href} href={item.href} className={active ? "nav-link active" : "nav-link"} title={collapsed ? item.label : undefined} onClick={close}>
            <Icon size={18} strokeWidth={1.8} />
            {!collapsed && <span>{item.label}</span>}
          </Link>
        );
      })}
    </div>
  );
}
