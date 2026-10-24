import { useMutation, useQueryClient, useSuspenseQuery } from "@tanstack/react-query";
import { AlertTriangle, Lock, ShieldCheck } from "lucide-react";
import { FormEvent, type ReactNode, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from "@/components/ui/sheet";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { useAuth } from "@/lib/auth";
import { listBranches, listDailyRegisters, lockRegister, resolveVariance, saveRegisterCount } from "@/lib/api/client";
import { canCountRegister, canResolveVariance, denominationLadder, emptyCount, expectedVaultCash, lockRegisterError, registerStatusLabels, resolveVarianceError, totalPhysicalCash, varianceCategories } from "@/lib/register-workflow";
import type { CashDenominations, DailyRegister, User, VarianceCategory } from "@/lib/api/types";

const money = new Intl.NumberFormat("en-NG", { style: "currency", currency: "NGN", maximumFractionDigits: 0 });
const title = (value: string) => value.replaceAll("_", " ").toLowerCase().replace(/(^|\s)\S/g, (letter) => letter.toUpperCase());

function PageHeader({ eyebrow, heading, description, action }: { eyebrow: string; heading: string; description: string; action?: ReactNode }) {
  return <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">{eyebrow}</p><h1 className="mt-2 font-serif text-4xl font-semibold tracking-tight">{heading}</h1><p className="mt-2 max-w-2xl text-sm text-muted-foreground">{description}</p></div>{action}</div>;
}

function TableShell({ children }: { children: ReactNode }) { return <div className="overflow-hidden border border-border bg-card"><div className="overflow-x-auto">{children}</div></div>; }
function Empty({ message }: { message: string }) { return <div className="px-6 py-12 text-center text-sm text-muted-foreground">{message}</div>; }

function StatusBadge({ status }: { status: DailyRegister["status"] }) {
  const className = status === "BALANCED" ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400" : status === "VARIANCE_FLAGGED" ? "border-destructive/30 bg-destructive/10 text-destructive" : status === "VARIANCE_APPROVED" ? "border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400" : status === "LOCKED" ? "border-border bg-muted text-muted-foreground" : "border-primary/25 bg-primary/5 text-primary";
  return <Badge variant="outline" className={className}>{registerStatusLabels[status]}</Badge>;
}

function VarianceValue({ value, flagged }: { value: number; flagged: boolean }) {
  const strong = value !== 0;
  return <span className={strong ? "font-semibold text-destructive" : "font-medium text-muted-foreground"}>{money.format(value)}{flagged && strong ? " ⚠" : ""}</span>;
}

export function DailyRegisterPage() {
  const { user } = useAuth();
  const me = user as User;
  const queryClient = useQueryClient();
  const [date, setDate] = useState("");
  const [branchId, setBranchId] = useState<number | undefined>(undefined);
  const [countItem, setCountItem] = useState<DailyRegister | null>(null);
  const [resolveItem, setResolveItem] = useState<DailyRegister | null>(null);
  const [notice, setNotice] = useState("");
  const [error, setError] = useState("");

  const { data: registers } = useSuspenseQuery({ queryKey: ["daily-registers", me?.id, branchId ?? null, date], queryFn: () => listDailyRegisters(me, branchId, date || undefined) });
  const { data: branches } = useSuspenseQuery({ queryKey: ["branches"], queryFn: listBranches });
  const refresh = () => queryClient.invalidateQueries({ queryKey: ["daily-registers"] });

  const lock = useMutation({ mutationFn: lockRegister, onSuccess: (item) => { refresh(); setError(""); setNotice(`${item.branchName} register for ${item.registerDate} is locked. No further changes can be made.`); }, onError: (failure) => setError(failure.message) });

  const canSeeBranchFilter = !me.branch;
  const flagged = registers.filter((item) => item.status === "VARIANCE_FLAGGED").length;
  const balanced = registers.filter((item) => item.status === "BALANCED" || item.status === "LOCKED").length;
  const netVariance = registers.reduce((total, item) => total + item.variance, 0);

  return (
    <div className="mx-auto max-w-[1440px] space-y-8">
      <PageHeader eyebrow="Cash control" heading="Daily branch cash register" description="Reconcile each branch's physical till against the cash book (CB CIH), monitoring cash, and counted vault (PHY CIH) before locking the day." />
      {notice && <p role="status" className="border border-primary/25 bg-primary/5 px-4 py-3 text-sm text-primary">{notice}</p>}
      {error && <p role="alert" className="border border-destructive/25 bg-destructive/5 px-4 py-3 text-sm text-destructive">{error}</p>}

      <div className="grid gap-4 sm:grid-cols-3">
        <div className="border border-border bg-card p-5"><p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Registers in view</p><p className="mt-2 text-3xl font-semibold">{registers.length}</p></div>
        <div className="border border-border bg-card p-5"><p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Balanced &amp; locked</p><p className="mt-2 text-3xl font-semibold">{balanced}</p></div>
        <div className="border border-border bg-card p-5"><p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Net variance (DIFF)</p><p className={`mt-2 text-3xl font-semibold ${netVariance !== 0 ? "text-destructive" : ""}`}>{money.format(netVariance)}</p></div>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div className="space-y-2"><Label htmlFor="register-date">Filter by date</Label><Input id="register-date" type="date" value={date} onChange={(event) => setDate(event.target.value)} className="w-48" /></div>
        {canSeeBranchFilter && <div className="space-y-2"><Label htmlFor="register-branch">Filter by branch</Label><select id="register-branch" value={branchId ?? ""} onChange={(event) => setBranchId(event.target.value ? Number(event.target.value) : undefined)} className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm sm:w-56"><option value="">All branches</option>{branches.map((branch) => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select></div>}
        {(date || branchId !== undefined) && <Button variant="ghost" onClick={() => { setDate(""); setBranchId(undefined); }}>Clear filters</Button>}
      </div>

      <TableShell>
        <table className="w-full text-left text-sm">
          <thead className="bg-muted/40 text-[10px] uppercase tracking-wider text-muted-foreground"><tr><th className="px-6 py-3">Date</th><th className="px-6 py-3">Branch</th><th className="px-6 py-3">Opening book (CB CIH)</th><th className="px-6 py-3">Monitoring</th><th className="px-6 py-3">Counted vault (PHY CIH)</th><th className="px-6 py-3">Variance (DIFF)</th><th className="px-6 py-3">Status</th><th className="px-6 py-3 text-right">Actions</th></tr></thead>
          <tbody className="divide-y divide-border">
            {registers.map((item) => {
              const lockError = lockRegisterError(me, item);
              return (
                <tr key={item.id}>
                  <td className="px-6 py-4 font-mono text-xs">{item.registerDate}</td>
                  <td className="px-6 py-4 font-semibold">{item.branchName}</td>
                  <td className="px-6 py-4">{money.format(expectedVaultCash(item) + item.monitoringCash)}</td>
                  <td className="px-6 py-4">{money.format(item.monitoringCash)}</td>
                  <td className="px-6 py-4">{item.physicalCashCounted ? money.format(item.physicalCashCounted) : <span className="text-muted-foreground">Not counted</span>}</td>
                  <td className="px-6 py-4"><VarianceValue value={item.variance} flagged={item.status === "VARIANCE_FLAGGED"} /></td>
                  <td className="px-6 py-4"><StatusBadge status={item.status} /></td>
                  <td className="px-6 py-4">
                    <div className="flex justify-end gap-2">
                      {canCountRegister(me, item) && <Button size="sm" variant="outline" onClick={() => { setCountItem(item); setError(""); setNotice(""); }}>Count till</Button>}
                      {item.status === "VARIANCE_FLAGGED" && canResolveVariance(me, item) && <Button size="sm" variant="outline" onClick={() => { setResolveItem(item); setError(""); setNotice(""); }}><AlertTriangle className="size-4" />Resolve</Button>}
                      {["BRANCH_MANAGER", "SUPER_ADMIN", "SUB_ADMIN"].some((role) => me.roles.includes(role as User["roles"][number])) && (
                        <TooltipProvider delayDuration={200}>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <span tabIndex={lockError && item.status === "VARIANCE_FLAGGED" ? 0 : undefined}>
                                <Button size="sm" variant="ghost" disabled={Boolean(lockError)} onClick={() => lock.mutate(item.id)}><Lock className="size-4" />Lock</Button>
                              </span>
                            </TooltipTrigger>
                            {lockError && <TooltipContent>{lockError}</TooltipContent>}
                          </Tooltip>
                        </TooltipProvider>
                      )}
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
        {registers.length === 0 && <Empty message="No daily registers match this filter yet." />}
      </TableShell>

      {countItem && <CashDenominationDrawer register={countItem} user={me} onClose={() => setCountItem(null)} onFlagged={(item) => { setCountItem(null); setResolveItem(item); }} onSaved={() => refresh()} />}
      {resolveItem && <VarianceResolutionModal register={resolveItem} user={me} onClose={() => setResolveItem(null)} onResolved={(item) => { refresh(); setError(""); setNotice(`${item.branchName} variance of ${money.format(item.variance)} approved for ${varianceCategories[item.varianceCategory as VarianceCategory]}.`); }} />}
    </div>
  );
}

export function CashDenominationDrawer({ register, user, onClose, onSaved, onFlagged }: { register: DailyRegister; user: User; onClose: () => void; onSaved: () => void; onFlagged: (item: DailyRegister) => void }) {
  const [counts, setCounts] = useState<CashDenominations>({ ...emptyCount(), ...(register.denominations ?? {}) });
  const counted = totalPhysicalCash(counts);
  const expected = expectedVaultCash(register);
  const variance = expected - counted;
  const balanced = variance === 0;

  const submit = useMutation({
    mutationFn: () => saveRegisterCount({ registerId: register.id, denominations: counts }),
    onSuccess: (item) => { onSaved(); if (item.status === "VARIANCE_FLAGGED") onFlagged(item); else onClose(); },
  });

  return (
    <Sheet open onOpenChange={(open) => { if (!open) onClose(); }}>
      <SheetContent className="w-full overflow-y-auto sm:max-w-xl">
        <SheetHeader>
          <SheetTitle className="font-serif text-2xl">Physical till count</SheetTitle>
          <SheetDescription>{register.branchName} · {register.registerDate}. Count each note denomination, then submit the count to reconcile against the cash book.</SheetDescription>
        </SheetHeader>
        <form onSubmit={(event: FormEvent) => { event.preventDefault(); submit.mutate(); }} className="mt-4 space-y-6 px-4 pb-8">
          <div className="space-y-2">
            {denominationLadder.map((denomination) => (
              <div key={denomination.key} className="grid grid-cols-[80px_1fr_110px] items-center gap-3">
                <span className="font-mono text-sm font-semibold">{denomination.label}</span>
                <Input type="number" min={0} step={1} inputMode="numeric" value={counts[denomination.key]} aria-label={`${denomination.label} note count`} onChange={(event) => setCounts((current) => ({ ...current, [denomination.key]: Math.max(0, Number(event.target.value) || 0) }))} />
                <span className="text-right text-sm text-muted-foreground">{money.format((counts[denomination.key] ?? 0) * denomination.value)}</span>
              </div>
            ))}
          </div>
          <div className="space-y-2 border-t border-border pt-4 text-sm">
            <div className="flex justify-between"><span className="text-muted-foreground">Expected vault cash (CB CIH − Monitoring)</span><span className="font-semibold">{money.format(expected)}</span></div>
            <div className="flex justify-between"><span className="text-muted-foreground">Counted physical cash (PHY CIH)</span><span className="font-semibold">{money.format(counted)}</span></div>
            <div className="flex justify-between border-t border-border pt-2"><span className="font-semibold">Real-time variance (DIFF)</span><span className={balanced ? "font-semibold text-emerald-600 dark:text-emerald-400" : "font-semibold text-destructive"}>{money.format(variance)}{balanced ? " — balanced" : ""}</span></div>
          </div>
          {!balanced && <p className="flex items-start gap-2 border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-400"><AlertTriangle className="mt-0.5 size-4 shrink-0" />The count does not match the expected vault cash. Submitting will flag the variance and a supervisor must categorise and authorise it.</p>}
          {submit.error && <p role="alert" className="text-sm text-destructive">{submit.error.message}</p>}
          <div className="flex gap-3">
            <Button type="submit" disabled={submit.isPending}>{submit.isPending ? "Saving…" : "Submit count"}</Button>
            <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
          </div>
        </form>
      </SheetContent>
    </Sheet>
  );
}

export function VarianceResolutionModal({ register, user, onClose, onResolved }: { register: DailyRegister; user: User; onClose: () => void; onResolved: (item: DailyRegister) => void }) {
  const [category, setCategory] = useState<VarianceCategory | "">("");
  const [reason, setReason] = useState("");
  const reasonLength = Array.from(reason.trim()).length;
  const guard = resolveVarianceError(user, register);

  const submit = useMutation({
    mutationFn: () => resolveVariance({ registerId: register.id, category: category as VarianceCategory, reason }),
    onSuccess: (item) => { onResolved(item); onClose(); },
  });

  const onSubmit = (event: FormEvent) => {
    event.preventDefault();
    if (!category) return;
    if (reasonLength < 15 || reasonLength > 1000) return;
    submit.mutate();
  };

  return (
    <Dialog open onOpenChange={(open) => { if (!open) onClose(); }}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="font-serif text-2xl">Resolve variance</DialogTitle>
          <DialogDescription>{register.branchName} · {register.registerDate}. Difference of {money.format(register.variance)} must be categorised and authorised before the register can be locked.</DialogDescription>
        </DialogHeader>
        {guard ? <p className="border border-destructive/25 bg-destructive/5 px-4 py-3 text-sm text-destructive">{guard}</p> : (
          <form onSubmit={onSubmit} className="space-y-5">
            <p className="flex items-start gap-2 border border-primary/25 bg-primary/5 px-4 py-3 text-sm text-primary"><ShieldCheck className="mt-0.5 size-4 shrink-0" />Supervisor authorisation: only a branch manager or administrator may categorise and sign off this variance. The decision is recorded against your name.</p>
            <div className="space-y-2">
              <Label htmlFor="variance-category">Variance category</Label>
              <select id="variance-category" required value={category} onChange={(event) => setCategory(event.target.value as VarianceCategory)} className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                <option value="" disabled>Select a category</option>
                {(Object.keys(varianceCategories) as VarianceCategory[]).map((key) => <option key={key} value={key}>{varianceCategories[key]}</option>)}
              </select>
            </div>
            <div className="space-y-2">
              <div className="flex items-center justify-between"><Label htmlFor="variance-reason">Detailed justification</Label><span className={`text-xs ${reasonLength < 15 ? "text-muted-foreground" : "text-primary"}`}>{reasonLength}/1,000 (min 15)</span></div>
              <Textarea id="variance-reason" required rows={4} maxLength={1000} value={reason} onChange={(event) => setReason(event.target.value)} placeholder="Explain the difference — e.g. field collections still in transit, an unrecorded disbursement voucher, or a counting error." />
            </div>
            {submit.error && <p role="alert" className="text-sm text-destructive">{submit.error.message}</p>}
            <div className="flex gap-3">
              <Button type="submit" disabled={submit.isPending || !category || reasonLength < 15 || reasonLength > 1000}>{submit.isPending ? "Approving…" : "Authorise variance"}</Button>
              <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
            </div>
          </form>
        )}
      </DialogContent>
    </Dialog>
  );
}
