import { createFileRoute, Link, useNavigate, useSearch } from "@tanstack/react-router";
import { ArrowRight, LockKeyhole } from "lucide-react";
import { FormEvent, useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { auth, useAuth } from "@/lib/auth";

export const Route = createFileRoute("/login")({
  head: () => ({ meta: [{ title: "Sign in — Lifter's Touch" }, { name: "description", content: "Secure sign in for the Lifter's Touch operations console." }, { property: "og:title", content: "Sign in — Lifter's Touch" }, { property: "og:description", content: "Secure sign in for the Lifter's Touch operations console." }, { property: "og:type", content: "website" }, { name: "twitter:card", content: "summary" }] }),
  component: LoginPage,
});

function LoginPage() {
  const { isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const search = useSearch({ from: "/login" }) as { redirect?: string };
  const [email, setEmail] = useState("manager.lagos@example.com");
  const [password, setPassword] = useState("password");
  const [error, setError] = useState("");
  const [pending, setPending] = useState(false);
  useEffect(() => { if (isAuthenticated) void navigate({ href: search.redirect ?? "/dashboard", replace: true }); }, [isAuthenticated, navigate, search.redirect]);
  if (isAuthenticated) return null;
  const submit = async (event: FormEvent) => { event.preventDefault(); setError(""); setPending(true); try { await auth.login(email, password); await navigate({ to: search.redirect ?? "/dashboard", replace: true }); } catch (submissionError) { setError(submissionError instanceof Error ? submissionError.message : "Unable to sign in."); } finally { setPending(false); } };
  return <main className="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]"><section className="relative hidden overflow-hidden bg-sidebar p-12 text-sidebar-foreground lg:flex lg:flex-col lg:justify-between"><div><div className="flex items-center gap-3"><span className="flex size-11 items-center justify-center rounded-lg bg-sidebar-primary text-lg font-bold text-sidebar-primary-foreground">LT</span><span className="font-serif text-xl font-semibold">Lifter's Touch</span></div><div className="mt-28 max-w-lg"><p className="text-xs font-bold uppercase tracking-[0.2em] text-sidebar-primary">Empowerment foundation</p><h1 className="mt-5 font-serif text-5xl font-semibold leading-[1.08]">Every branch.<br />One clear view.</h1><p className="mt-6 max-w-md text-base leading-7 text-sidebar-foreground/60">A disciplined operating system for member services, disbursement governance, and financial accountability.</p></div></div><div className="flex items-center gap-2 text-xs text-sidebar-foreground/45"><LockKeyhole className="size-3.5" />Protected operations console</div></section><section className="flex items-center justify-center bg-background px-6 py-12"><div className="w-full max-w-md"><div className="mb-12 lg:hidden"><Link to="/" className="flex items-center gap-3"><span className="flex size-10 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground">LT</span><span className="font-serif text-lg font-semibold">Lifter's Touch</span></Link></div><p className="text-xs font-bold uppercase tracking-[0.2em] text-primary">Welcome back</p><h2 className="mt-3 font-serif text-4xl font-semibold tracking-tight">Sign in to continue</h2><p className="mt-3 text-sm text-muted-foreground">Use your authorised account to access the operations console.</p><form onSubmit={submit} className="mt-10 space-y-5"><div className="space-y-2"><Label htmlFor="email">Email address</Label><Input id="email" type="email" autoComplete="email" value={email} onChange={(event) => setEmail(event.target.value)} required /></div><div className="space-y-2"><div className="flex items-center justify-between"><Label htmlFor="password">Password</Label><span className="text-xs text-muted-foreground">Mock environment</span></div><Input id="password" type="password" autoComplete="current-password" value={password} onChange={(event) => setPassword(event.target.value)} required /></div>{error && <p role="alert" className="border border-destructive/25 bg-destructive/5 px-3 py-2 text-sm text-destructive">{error}</p>}<Button disabled={pending} className="h-11 w-full">{pending ? "Signing in…" : "Sign in"}<ArrowRight className="size-4" /></Button></form><p className="mt-8 text-center text-xs text-muted-foreground">Demo: manager.lagos@example.com · password</p></div></section></main>;
}