import { Link, Outlet, useLocation, useNavigate } from "@tanstack/react-router";
import { BarChart3, ClipboardList, FileSpreadsheet, Landmark, LayoutDashboard, LogOut, Menu, ReceiptText, Settings2, UsersRound, X } from "lucide-react";
import { useState } from "react";
import { Button } from "./ui/button";
import { useAuth } from "@/lib/auth";
import { roleLabel } from "@/lib/api/mock";
import type { RoleCode } from "@/lib/api/types";

const links = [
  { label: "Overview", to: "/dashboard", icon: LayoutDashboard, roles: [] as RoleCode[] },
  { label: "Members", to: "/members", icon: UsersRound, roles: [] as RoleCode[] },
  { label: "Disbursements", to: "/disbursements", icon: ReceiptText, roles: [] as RoleCode[] },
  { label: "Treasury", to: "/treasury", icon: Landmark, roles: ["FINANCE_OFFICER", "SUPER_ADMIN", "SUB_ADMIN"] as RoleCode[] },
  { label: "Periods & reconciliation", to: "/periods", icon: BarChart3, roles: ["AUDITOR", "FINANCE_OFFICER", "SUPER_ADMIN", "SUB_ADMIN"] as RoleCode[] },
  { label: "Imports", to: "/imports", icon: FileSpreadsheet, roles: ["FINANCE_OFFICER", "SUPER_ADMIN", "SUB_ADMIN"] as RoleCode[] },
  { label: "Administration", to: "/admin", icon: Settings2, roles: ["SUPER_ADMIN", "SUB_ADMIN"] as RoleCode[] },
];

export function AppShell() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [mobileOpen, setMobileOpen] = useState(false);
  if (!user) return null;
  const visibleLinks = links.filter((link) => link.roles.length === 0 || link.roles.some((role) => user.roles.includes(role)));
  const initials = user.name.split(" ").map((part) => part[0]).join("").slice(0, 2);
  const signOut = () => { logout(); void navigate({ to: "/login", replace: true }); };
  return (
    <div className="min-h-screen bg-background text-foreground">
      <aside className={`fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-sidebar-border bg-sidebar px-5 py-6 transition-transform lg:translate-x-0 ${mobileOpen ? "translate-x-0" : "-translate-x-full"}`}>
        <div className="flex items-center justify-between px-2">
          <Link to="/dashboard" className="flex items-center gap-3" onClick={() => setMobileOpen(false)}>
            <span className="flex size-10 items-center justify-center rounded-lg bg-sidebar-primary text-lg font-bold text-sidebar-primary-foreground">LT</span>
            <span><span className="block font-serif text-lg font-semibold leading-none text-sidebar-foreground">Lifter's Touch</span><span className="mt-1 block text-[10px] font-semibold uppercase tracking-[0.18em] text-sidebar-foreground/55">Operations console</span></span>
          </Link>
          <Button variant="ghost" size="icon" className="text-sidebar-foreground lg:hidden" onClick={() => setMobileOpen(false)} aria-label="Close navigation"><X /></Button>
        </div>
        <div className="mt-12 px-2 text-[10px] font-bold uppercase tracking-[0.18em] text-sidebar-foreground/45">Workspace</div>
        <nav className="mt-3 space-y-1" aria-label="Main navigation">
           {visibleLinks.map((link) => { const Icon = link.icon; const active = location.pathname === link.to; return <Link key={link.to} to={link.to} onClick={() => setMobileOpen(false)} className={`flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors ${active ? "bg-sidebar-primary text-sidebar-primary-foreground" : "text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground"}`}><Icon className="size-[18px]" />{link.label}</Link>; })}
        </nav>
        <div className="mt-auto border-t border-sidebar-border pt-4">
          <div className="flex items-center gap-3 px-2 py-3"><span className="flex size-9 items-center justify-center rounded-full bg-sidebar-accent text-xs font-bold text-sidebar-foreground">{initials}</span><div className="min-w-0"><p className="truncate text-sm font-semibold text-sidebar-foreground">{user.name}</p><p className="truncate text-xs text-sidebar-foreground/55">{roleLabel(user.roles[0])}</p></div></div>
          <Button variant="ghost" className="w-full justify-start gap-3 px-3 text-sidebar-foreground/65 hover:bg-sidebar-accent hover:text-sidebar-foreground" onClick={signOut}><LogOut className="size-4" />Sign out</Button>
        </div>
      </aside>
      {mobileOpen && <button aria-label="Close menu" className="fixed inset-0 z-30 bg-foreground/20 lg:hidden" onClick={() => setMobileOpen(false)} />}
      <main className="min-h-screen lg:pl-72"><header className="flex h-20 items-center justify-between border-b border-border bg-background px-5 sm:px-8"><Button variant="ghost" size="icon" className="lg:hidden" onClick={() => setMobileOpen(true)} aria-label="Open navigation"><Menu /></Button><div className="hidden text-sm text-muted-foreground sm:block">{user.branch ? `${user.branch.name} · ${user.branch.code}` : "All branches"}</div><div className="ml-auto flex items-center gap-3"><span className="hidden text-right sm:block"><span className="block text-sm font-semibold">{user.name}</span><span className="block text-xs text-muted-foreground">{user.email}</span></span><span className="flex size-9 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">{initials}</span></div></header><div className="px-5 py-8 sm:px-8 lg:px-10"><Outlet /></div></main>
    </div>
  );
}