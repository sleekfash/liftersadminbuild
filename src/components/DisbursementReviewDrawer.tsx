import { useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Check, Circle, ShieldAlert, X } from "lucide-react";
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { getDisbursement, reviewDisbursement } from "@/lib/api/client";
import { branchReviewError, rejectionCategories, validateBranchReview } from "@/lib/disbursement-workflow";
import type { BranchReviewInput, Disbursement, RejectionCategory, User } from "@/lib/api/types";

const title = (value: string) => value.replaceAll("_", " ").toLowerCase().replace(/(^|\s)\S/g, (letter) => letter.toUpperCase());
const date = (value?: string) => value ? (Number.isNaN(Date.parse(value)) ? value : new Date(value).toLocaleString("en-GB")) : "Not recorded";
const money = new Intl.NumberFormat("en-NG", { style: "currency", currency: "NGN" });

export function DisbursementReviewDrawer({ item, user, onClose }: { item: Disbursement; user: User; onClose: () => void }) {
  const queryClient = useQueryClient();
  const details = useQuery({ queryKey: ["disbursement-review", item.id], queryFn: () => getDisbursement(item.id), staleTime: 0 });
  const [decision, setDecision] = useState<"approve" | "reject">("approve");
  const [remarks, setRemarks] = useState("");
  const [category, setCategory] = useState<RejectionCategory | "">("");
  const [reason, setReason] = useState("");
  const [formError, setFormError] = useState("");
  const mutation = useMutation({ mutationFn: (input: BranchReviewInput) => reviewDisbursement(item.id, input), onSuccess: (updated) => {
    queryClient.setQueryData(["disbursement-review", item.id], updated);
    void queryClient.invalidateQueries({ queryKey: ["disbursements"] });
    void queryClient.invalidateQueries({ queryKey: ["dashboard"] });
  } });
  const current = mutation.data ?? details.data ?? item;
  const guard = branchReviewError(user, current);
  const blocked = !!guard || details.isFetching || !!details.error || mutation.isPending || mutation.isSuccess;
  const input: BranchReviewInput = { decision, remarks, category: category || undefined, reason };
  const submit = (event: FormEvent) => {
    event.preventDefault();
    const error = guard ?? validateBranchReview(input);
    setFormError(error ?? "");
    if (!error && !blocked) mutation.mutate(input);
  };
  const stamps = current.timeline ?? [];
  return <Sheet open onOpenChange={(open) => { if (!open && !mutation.isPending) onClose(); }}>
    <SheetContent className="w-full overflow-y-auto sm:max-w-2xl" onInteractOutside={(event) => { if (mutation.isPending) event.preventDefault(); }}>
      <SheetHeader className="pr-8"><SheetTitle>Branch review · {current.reference}</SheetTitle><SheetDescription>{title(current.stage)}</SheetDescription></SheetHeader>
      <section aria-label="Request summary" className="mt-6 border-y border-border py-5">
        <h2 className="mb-4 text-sm font-semibold">Request summary</h2>
        <dl className="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
          {[["Reference", current.reference], ["Submitted", date(current.submittedAt)], ["Member", current.memberName], ["UMID", current.umid ?? "Not recorded"], ["Phone", current.phone ?? "Not recorded"], ["Verification status", current.verificationStatus ? title(current.verificationStatus) : "Not recorded"], ["Amount", money.format(current.amount)], ["Category", current.category ?? "Not recorded"], ["Purpose", current.purpose ?? "Not recorded"], ["Requester", current.requesterName ?? (current.requestedBy ? `User #${current.requestedBy}` : "Not recorded")]].map(([label, value]) => <div key={label} className="min-w-0"><dt className="text-xs text-muted-foreground">{label}</dt><dd className="mt-1 break-words font-medium">{value}</dd></div>)}
        </dl>
      </section>
      {current.stage === "REJECTED" && <section aria-label="Rejection decision" className="border-b border-border py-4 text-sm"><h2 className="font-semibold">Rejection decision</h2><p className="mt-2 text-muted-foreground">{current.rejectionCategory ? rejectionCategories[current.rejectionCategory] : "Category not recorded"}</p><p className="mt-1 break-words">{current.rejectedReason}</p></section>}
      <section aria-label="Lifecycle timeline" className="py-5">
        <h2 className="mb-4 text-sm font-semibold">Lifecycle timeline</h2>
        <ol className="space-y-4">{stamps.length === 0 && <li className="text-sm text-muted-foreground">No lifecycle stamps recorded.</li>}{stamps.map((stamp, index) => <li key={`${stamp.stage}-${index}`} className="flex gap-3"><Check className="mt-0.5 size-4 shrink-0 text-primary" /><div className="min-w-0 text-sm"><p className="font-medium">{title(stamp.stage)}</p><p className="text-xs text-muted-foreground">{date(stamp.at)} · {stamp.actorName || (stamp.actorId ? `User #${stamp.actorId}` : "Identity not recorded")}</p>{stamp.remarks && <p className="mt-1 break-words text-muted-foreground">{stamp.remarks}</p>}</div></li>)}</ol>
        {current.stage !== "REJECTED" && <div className="mt-4 space-y-3">{(["BRANCH_APPROVED", "FINANCE_REVIEW", "AUTHORIZED", "PAID"] as const).filter((stage) => !stamps.some((stamp) => stamp.stage === stage)).map((stage) => <p key={stage} className="flex gap-3 text-xs text-muted-foreground"><Circle className="size-4" />{title(stage)} · Pending</p>)}</div>}
      </section>
      {details.isFetching && <p role="status" className="text-sm text-muted-foreground">Loading latest request…</p>}
      {details.error && <div role="alert" className="text-sm text-destructive">{details.error.message}<Button variant="ghost" onClick={() => void details.refetch()}>Retry</Button></div>}
      {mutation.isSuccess ? <div role="status" className="border-t border-border pt-5 text-sm text-primary">{decision === "approve" ? "Branch approval recorded. Awaiting finance review." : "Request rejected. The decision has been recorded."}<Button variant="outline" className="mt-4 block" onClick={onClose}>Close review</Button></div> : <>
        {guard && <p role="alert" className="mb-4 flex gap-2 border border-destructive/25 bg-destructive/5 p-3 text-sm text-destructive"><ShieldAlert className="size-5 shrink-0" />{guard}</p>}
        <form onSubmit={submit} className="space-y-4 border-t border-border pt-5">
          <div className="flex gap-2" aria-label="Review decision"><Button type="button" variant={decision === "approve" ? "default" : "outline"} aria-pressed={decision === "approve"} disabled={blocked} onClick={() => { setDecision("approve"); setFormError(""); mutation.reset(); }}><Check />Approve</Button><Button type="button" variant={decision === "reject" ? "destructive" : "outline"} aria-pressed={decision === "reject"} disabled={blocked} onClick={() => { setDecision("reject"); setFormError(""); mutation.reset(); }}><X />Reject</Button></div>
          {decision === "approve" ? <div className="space-y-2"><Label htmlFor="review-remarks">Remarks (optional)</Label><Textarea id="review-remarks" maxLength={1000} value={remarks} onChange={(event) => setRemarks(event.target.value)} disabled={blocked} /><p className="text-right text-xs text-muted-foreground">{Array.from(remarks).length} / 1,000</p></div> : <><div className="space-y-2"><Label htmlFor="review-category">Rejection category</Label><select id="review-category" className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" required value={category} disabled={blocked} onChange={(event) => setCategory(event.target.value as RejectionCategory)}><option value="">Select category</option>{Object.entries(rejectionCategories).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></div><div className="space-y-2"><Label htmlFor="review-reason">Detailed justification</Label><Textarea id="review-reason" required minLength={15} maxLength={1000} value={reason} disabled={blocked} onChange={(event) => setReason(event.target.value)} /><p className="text-right text-xs text-muted-foreground">{Array.from(reason.trim()).length} / 1,000 · Minimum 15</p></div></>}
          {(formError || mutation.error) && <p role="alert" className="text-sm text-destructive">{formError || mutation.error?.message}</p>}
          <Button type="submit" variant={decision === "reject" ? "destructive" : "default"} disabled={blocked}>{mutation.isPending ? "Recording decision…" : decision === "approve" ? "Confirm approval" : "Confirm rejection"}</Button>
        </form>
      </>}
    </SheetContent>
  </Sheet>;
}