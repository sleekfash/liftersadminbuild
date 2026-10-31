import { useMutation, useQueryClient, useSuspenseQuery } from "@tanstack/react-query";
import { ArrowLeftRight, FileSignature, Plus, ShieldAlert, ShieldCheck } from "lucide-react";
import { FormEvent, type ReactNode, useState } from "react";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from "@/components/ui/sheet";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { useAuth } from "@/lib/auth";
import { acknowledgeHandover, approveHandover, disputeHandover, listBranches, listHandovers, listManagerCandidates, openHandover, postBranchManager, signOutgoingHandover } from "@/lib/api/client";
import { acknowledgeError, approveError, handoverClaims, handoverStatusLabels, handoverStatusStyle, isAdminActor, isParty, openHandoverError, transferBlockError } from "@/lib/handover-workflow";
import type { BranchHandover, OpenHandoverInput, User } from "@/lib/api/types";

const title = (value: string) => value.replaceAll("_", " ").toLowerCase().replace(/(^|\s)\S/g, (letter) => letter.toUpperCase());
const when = (value?: string | null) => value ? new Date(value).toLocaleString() : "—";

function PageHeader({ eyebrow, heading, description, action }: { eyebrow: string; heading: string; description: string; action?: ReactNode }) {
  return <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">{eyebrow}</p><h1 className="mt-2 font-serif text-4xl font-semibold tracking-tight">{heading}</h1><p className="mt-2 max-w-2xl text-sm text-muted-foreground">{description}</p></div>{action}</div>;
}
function TableShell({ children }: { children: ReactNode }) { return <div className="overflow-hidden border border-border bg-card"><div className="overflow-x-auto">{children}</div></div>; }
function Empty({ message }: { message: string }) { return <div className="px-6 py-12 text-center text-sm text-muted-foreground">{message}</div>; }
function StatusBadge({ status }: { status: BranchHandover["status"] }) { return <Badge variant="outline" className={handoverStatusStyle(status)}>{handoverStatusLabels[status]}</Badge>; }

export function HandoversPage() {
  const { user } = useAuth();
  const me = user as User;
  const queryClient = useQueryClient();
  const [branchId, setBranchId] = useState<number | undefined>(undefined);
  const [detailItem, setDetailItem] = useState<BranchHandover | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [notice, setNotice] = useState("");
  const [error, setError] = useState("");
  const refresh = () => { queryClient.invalidateQueries({ queryKey: ["handovers"] }); queryClient.invalidateQueries({ queryKey: ["dashboard"] }); queryClient.invalidateQueries({ queryKey: ["admin-data"] }); };
  const { data: handovers } = useSuspenseQuery({ queryKey: ["handovers", me?.id, branchId ?? null], queryFn: () => listHandovers(me, branchId) });
  const { data: branches } = useSuspenseQuery({ queryKey: ["branches"], queryFn: listBranches });

  const updated = (item: BranchHandover, message: string) => { setDetailItem(item); refresh(); setNotice(""); setError(""); setNotice(message); };
  const canOpen = isAdminActor(me) || (me.roles.includes("BRANCH_MANAGER") && Boolean(me.branch));
  const open = handovers.filter((item) => item.status !== "COMPLETED" && item.status !== "DISPUTED").length;
  const disputed = handovers.filter((item) => item.status === "DISPUTED").length;
  const awaitingMe = handovers.filter((item) => (item.status === "DRAFT" && item.outgoingUserId === me.id) || (item.status === "PENDING_INCOMING" && item.incomingUserId === me.id) || (item.status === "PENDING_APPROVAL" && isAdminActor(me) && !isParty(me, item))).length;

  return (
    <div className="mx-auto max-w-[1440px] space-y-8">
      <PageHeader eyebrow="Succession control" heading="Manager handovers" description="A posting moves the officer only. Branch records stay with the branch; the incoming manager inherits the portfolio through a signed handover claim that the incoming officer must accept before the transfer completes." action={canOpen ? <Button variant={showForm ? "secondary" : "default"} onClick={() => { setShowForm((value) => !value); setError(""); }}><Plus />{showForm ? "Close form" : "New handover claim"}</Button> : undefined} />
      {notice && <p role="status" className="border border-primary/25 bg-primary/5 px-4 py-3 text-sm text-primary">{notice}</p>}
      {error && <p role="alert" className="border border-destructive/25 bg-destructive/5 px-4 py-3 text-sm text-destructive">{error}</p>}

      <div className="grid gap-4 sm:grid-cols-3">
        <div className="border border-border bg-card p-5"><p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Open handovers</p><p className="mt-2 text-3xl font-semibold">{open}</p></div>
        <div className="border border-border bg-card p-5"><p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Awaiting your action</p><p className="mt-2 text-3xl font-semibold">{awaitingMe}</p></div>
        <div className="border border-border bg-card p-5"><p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Disputed</p><p className={`mt-2 text-3xl font-semibold ${disputed ? "text-destructive" : ""}`}>{disputed}</p></div>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
        {!me.branch && <div className="space-y-2"><Label htmlFor="handover-branch">Filter by branch</Label><select id="handover-branch" value={branchId ?? ""} onChange={(event) => setBranchId(event.target.value ? Number(event.target.value) : undefined)} className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm sm:w-56"><option value="">All branches</option>{branches.map((branch) => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select></div>}
        {(branchId !== undefined) && <Button variant="ghost" onClick={() => setBranchId(undefined)}>Clear filter</Button>}
      </div>

      <TableShell>
        <table className="w-full text-left text-sm">
          <thead className="bg-muted/40 text-[10px] uppercase tracking-wider text-muted-foreground"><tr><th className="px-6 py-3">Branch</th><th className="px-6 py-3">Outgoing officer</th><th className="px-6 py-3">Incoming officer</th><th className="px-6 py-3">Opened</th><th className="px-6 py-3">Status</th><th className="px-6 py-3 text-right">Action</th></tr></thead>
          <tbody className="divide-y divide-border">
            {handovers.map((item) => (
              <tr key={item.id}>
                <td className="px-6 py-4"><p className="font-semibold">{item.branchName ?? `Branch ${item.branchId}`}</p><p className="font-mono text-xs text-muted-foreground">{item.branchCode}</p></td>
                <td className="px-6 py-4">{item.outgoingUserName ?? "—"}{item.outgoingUserId === me.id && <span className="ml-2 text-xs text-primary">you</span>}</td>
                <td className="px-6 py-4">{item.incomingUserName ?? "—"}{item.incomingUserId === me.id && <span className="ml-2 text-xs text-primary">you</span>}</td>
                <td className="px-6 py-4 text-muted-foreground">{when(item.createdAt)}</td>
                <td className="px-6 py-4"><StatusBadge status={item.status} /></td>
                <td className="px-6 py-4 text-right"><Button size="sm" variant="outline" onClick={() => { setDetailItem(item); setNotice(""); setError(""); }}><ArrowLeftRight className="size-4" />Open</Button></td>
              </tr>
            ))}
          </tbody>
        </table>
        {handovers.length === 0 && <Empty message="No handovers are visible for your branch yet." />}
      </TableShell>

      {showForm && <OpenHandoverDialog user={me} onClose={() => setShowForm(false)} onOpened={(item) => { setShowForm(false); refresh(); setNotice(`Handover claim opened for ${item.branchName}. The claims snapshot is frozen until both officers sign.`); }} />}
      {detailItem && <HandoverDrawer handover={detailItem} user={me} onClose={() => setDetailItem(null)} onUpdated={updated} />}
    </div>
  );
}

function OpenHandoverDialog({ user, onClose, onOpened }: { user: User; onClose: () => void; onOpened: (item: BranchHandover) => void }) {
  const { data: branches } = useSuspenseQuery({ queryKey: ["branches"], queryFn: listBranches });
  const { data: candidates } = useSuspenseQuery({ queryKey: ["manager-candidates"], queryFn: listManagerCandidates });
  const [branchId, setBranchId] = useState<number>(user.branch?.id ?? branches[0]?.id ?? 0);
  const [incomingId, setIncomingId] = useState("");
  const [error, setError] = useState("");
  const mutation = useMutation({ mutationFn: (input: OpenHandoverInput) => openHandover(user, input), onSuccess: onOpened, onError: (failure) => setError(failure.message) });
  const isManagerOpener = user.roles.includes("BRANCH_MANAGER") && user.branch?.id === branchId;
  const options = candidates.filter((candidate) => !isManagerOpener || candidate.id !== user.id);

  const submit = (event: FormEvent) => {
    event.preventDefault();
    setError("");
    const clientError = openHandoverError(user, { branchId, incomingUserId: Number(incomingId) }, isManagerOpener ? user.id : null, false);
    if (clientError) { setError(clientError); return; }
    mutation.mutate({ branchId, incomingUserId: Number(incomingId) });
  };

  return (
    <Dialog open onOpenChange={(open) => { if (!open) onClose(); }}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="font-serif text-2xl">Open handover claim</DialogTitle>
          <DialogDescription>Opening freezes a snapshot of the branch portfolio as the claims document. The outgoing officer signs it, the incoming officer accepts or disputes it, and an administrator approves before any transfer completes.</DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-5">
          <div className="space-y-2">
            <Label htmlFor="claim-branch">Branch</Label>
            <select id="claim-branch" value={branchId} disabled={Boolean(user.branch)} onChange={(event) => setBranchId(Number(event.target.value))} className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm disabled:opacity-60">{branches.map((branch) => <option key={branch.id} value={branch.id}>{branch.name} · {branch.code}</option>)}</select>
            <p className="text-xs text-muted-foreground">Outgoing officer: {isManagerOpener ? `${user.name} (you — the branch's current manager)` : "the branch's current manager, resolved on submit"}</p>
          </div>
          <div className="space-y-2">
            <Label htmlFor="claim-incoming">Incoming officer</Label>
            <select id="claim-incoming" required value={incomingId} onChange={(event) => setIncomingId(event.target.value)} className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
              <option value="" disabled>Select the incoming manager</option>
              {options.map((candidate) => <option key={candidate.id} value={candidate.id}>{candidate.name} · {candidate.branch?.name ?? "All branches"}</option>)}
            </select>
          </div>
          {error && <p role="alert" className="text-sm text-destructive">{error}</p>}
          <div className="flex gap-3">
            <Button type="submit" disabled={mutation.isPending}>{mutation.isPending ? "Opening…" : "Open claim"}</Button>
            <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function HandoverDrawer({ handover, user, onClose, onUpdated }: { handover: BranchHandover; user: User; onClose: () => void; onUpdated: (item: BranchHandover, message: string) => void }) {
  const [notes, setNotes] = useState("");
  const [reason, setReason] = useState("");
  const [disputing, setDisputing] = useState(false);
  const [actionError, setActionError] = useState("");
  const isAdmin = isAdminActor(user);
  const claims = handoverClaims(handover.snapshot);
  const outgoingSelf = handover.outgoingUserId === user.id;
  const incomingSelf = handover.incomingUserId === user.id;
  const reasonLength = Array.from(reason.trim()).length;

  const run = (fn: () => Promise<BranchHandover>, message: string) => ({ mutationFn: fn, onSuccess: (item: BranchHandover) => { setActionError(""); onUpdated(item, message); }, onError: (failure: Error) => setActionError(failure.message) });
  const sign = useMutation(run(() => signOutgoingHandover(user, handover, notes), "Claims signed. The incoming officer must now accept or dispute."));
  const accept = useMutation(run(() => acknowledgeHandover(user, handover, notes), "Acceptance recorded. An administrator must approve before the transfer completes."));
  const dispute = useMutation(run(() => disputeHandover(user, handover, reason), "Handover marked as disputed."));
  const approve = useMutation(run(() => approveHandover(user, handover), "Handover approved and completed. You can now post the incoming manager."));
  const post = useMutation({ mutationFn: () => postBranchManager(user, { branchId: handover.branchId, incomingUserId: handover.incomingUserId ?? 0, handoverId: handover.id, reason: "Completed handover." }), onSuccess: () => { setActionError(""); onUpdated(handover, `${handover.incomingUserName ?? "The incoming officer"} is now posted. Branch records were not moved.`); }, onError: (failure: Error) => setActionError(failure.message) });

  const approveGuard = approveError(user, handover);
  const acceptGuard = acknowledgeError(user, handover);
  const postBlocked = transferBlockError(user, { branchId: handover.branchId, incomingUserId: handover.incomingUserId ?? 0, handoverId: handover.id }, handover, handover.outgoingUserId);
  const canPost = isAdmin && handover.status === "COMPLETED" && handover.incomingUserId != null;

  return (
    <Sheet open onOpenChange={(open) => { if (!open) onClose(); }}>
      <SheetContent className="w-full overflow-y-auto sm:max-w-2xl">
        <SheetHeader>
          <SheetTitle className="font-serif text-2xl">Handover #{handover.id} · {handover.branchName ?? `Branch ${handover.branchId}`}</SheetTitle>
          <SheetDescription>{handover.outgoingUserName ?? "—"} → {handover.incomingUserName ?? "—"} · opened {when(handover.createdAt)}</SheetDescription>
        </SheetHeader>
        <div className="mt-4 space-y-6 px-4 pb-10">
          <div className="flex items-center gap-3"><StatusBadge status={handover.status} /><span className="text-xs text-muted-foreground">{handoverStatusLabels[handover.status]}</span></div>

          {handover.status === "DISPUTED" && handover.disputeReason && <p className="flex items-start gap-2 border border-destructive/25 bg-destructive/5 px-4 py-3 text-sm text-destructive"><ShieldAlert className="mt-0.5 size-4 shrink-0" /><span><strong>Disputed:</strong> {handover.disputeReason}</span></p>}

          <div className="border border-border bg-card p-5">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">Frozen claims snapshot</p>
            <p className="mt-1 text-xs text-muted-foreground">Captured when the claim was opened and never recomputed — this is the document both officers sign against.</p>
            <dl className="mt-4 space-y-2 text-sm">{claims.map((claim) => <div key={claim.label} className="flex justify-between gap-4"><dt className="text-muted-foreground">{claim.label}</dt><dd className="text-right font-medium">{claim.value}</dd></div>)}</dl>
          </div>

          <div className="border border-border bg-card p-5">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">Signatures</p>
            <div className="mt-3 space-y-2 text-sm">
              <p><span className="text-muted-foreground">Outgoing:</span> {handover.outgoingSignedAt ? `signed ${when(handover.outgoingSignedAt)}` : "not signed"}{handover.outgoingNotes ? ` — “${handover.outgoingNotes}”` : ""}</p>
              <p><span className="text-muted-foreground">Incoming:</span> {handover.incomingSignedAt ? `accepted ${when(handover.incomingSignedAt)}` : "not accepted"}{handover.incomingNotes ? ` — “${handover.incomingNotes}”` : ""}</p>
              <p><span className="text-muted-foreground">Approval:</span> {handover.approvedAt ? `${handover.approvedByName ?? "administrator"} approved ${when(handover.approvedAt)}` : "pending"}</p>
            </div>
          </div>

          {outgoingSelf && handover.status === "DRAFT" && (
            <div className="space-y-3 border border-border bg-card p-5">
              <p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">Sign your claims</p>
              <Textarea rows={3} maxLength={5000} value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Optional notes — e.g. keys returned, pending verifications, cash in transit." aria-label="Outgoing sign-off notes" />
              <Button disabled={sign.isPending} onClick={() => sign.mutate()}><FileSignature className="size-4" />{sign.isPending ? "Signing…" : "Sign off & submit claims"}</Button>
            </div>
          )}

          {incomingSelf && handover.status === "PENDING_INCOMING" && (
            <div className="space-y-3 border border-border bg-card p-5">
              <p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">Your acceptance is required</p>
              {!disputing ? (
                <>
                  <Textarea rows={2} maxLength={5000} value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Optional acknowledgement notes." aria-label="Acceptance notes" />
                  <div className="flex gap-3">
                    <Button disabled={accept.isPending} onClick={() => accept.mutate()}><ShieldCheck className="size-4" />{accept.isPending ? "Recording…" : "Accept handover"}</Button>
                    <Button variant="outline" onClick={() => setDisputing(true)}>Dispute instead</Button>
                  </div>
                </>
              ) : (
                <>
                  <div className="flex items-center justify-between"><Label htmlFor="dispute-reason">Dispute reason</Label><span className={`text-xs ${reasonLength < 10 ? "text-muted-foreground" : "text-primary"}`}>{reasonLength}/5,000 (min 10)</span></div>
                  <Textarea id="dispute-reason" rows={4} maxLength={5000} required value={reason} onChange={(event) => setReason(event.target.value)} placeholder="Explain what in the claims snapshot is inaccurate or missing." />
                  <div className="flex gap-3">
                    <Button variant="destructive" disabled={dispute.isPending || reasonLength < 10} onClick={() => dispute.mutate()}>{dispute.isPending ? "Recording…" : "Record dispute"}</Button>
                    <Button variant="ghost" onClick={() => { setDisputing(false); setReason(""); }}>Back</Button>
                  </div>
                </>
              )}
            </div>
          )}

          {isAdmin && handover.status === "PENDING_APPROVAL" && (
            <div className="space-y-3 border border-border bg-card p-5">
              <p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">Administrator approval</p>
              {isParty(user, handover) && <p className="flex items-start gap-2 border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-400"><ShieldAlert className="mt-0.5 size-4 shrink-0" />You are a party to this handover. Another administrator must approve it.</p>}
              <Button disabled={Boolean(approveGuard) || approve.isPending} onClick={() => approve.mutate()}><ShieldCheck className="size-4" />{approve.isPending ? "Approving…" : "Approve & complete handover"}</Button>
            </div>
          )}

          {handover.status === "COMPLETED" && (
            <div className="space-y-3 border border-primary/25 bg-primary/5 p-5">
              <p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">Complete the transfer</p>
              <p className="text-sm text-muted-foreground">Posting {handover.incomingUserName ?? "the incoming officer"} as branch manager moves the officer only — members, disbursements, treasury, imports, and registers stay owned by {handover.branchName ?? "the branch"}.</p>
              {canPost ? <Button disabled={post.isPending} onClick={() => post.mutate()}><ArrowLeftRight className="size-4" />{post.isPending ? "Posting…" : `Post ${handover.incomingUserName ?? "incoming manager"}`}</Button>
                : <Button disabled title={postBlocked ?? undefined}>Post incoming manager</Button>}
              {postBlocked && !canPost && <p className="text-xs text-destructive">{postBlocked}</p>}
            </div>
          )}

          {isAdmin && handover.status !== "COMPLETED" && (
            <div className="space-y-2 border border-border bg-card p-5">
              <p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">Transfer is blocked</p>
              <p className="text-sm text-muted-foreground">{transferBlockError(user, { branchId: handover.branchId, incomingUserId: handover.incomingUserId ?? 0, handoverId: handover.id }, handover, handover.outgoingUserId) ?? "Posting is blocked until this handover completes."}</p>
            </div>
          )}

          {actionError && <p role="alert" className="text-sm text-destructive">{actionError}</p>}

          <div className="border border-border bg-card p-5">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-primary">Audit trail</p>
            {handover.auditTrail && handover.auditTrail.length > 0 ? (
              <ol className="mt-3 space-y-3 text-sm">
                {handover.auditTrail.map((entry) => (
                  <li key={entry.id} className="flex gap-3">
                    <Badge variant="outline" className="h-fit shrink-0 text-[10px]">{title(entry.event)}</Badge>
                    <div><p>{entry.summary}</p><p className="text-xs text-muted-foreground">{entry.actorName ?? "System"} · {when(entry.at)}</p></div>
                  </li>
                ))}
              </ol>
            ) : <p className="mt-3 text-sm text-muted-foreground">No audit events recorded yet.</p>}
          </div>
        </div>
      </SheetContent>
    </Sheet>
  );
}
