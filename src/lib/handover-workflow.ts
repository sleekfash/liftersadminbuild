import type { BranchHandover, HandoverSnapshot, HandoverStatus, PostBranchManagerInput, RoleCode, User } from "./api/types";

/** Handover lifecycle, mirroring the Laravel HandoverStatus enum and BranchHandoverService. */
export const handoverStatusLabels: Record<HandoverStatus, string> = {
  DRAFT: "Awaiting outgoing sign-off",
  PENDING_INCOMING: "Awaiting incoming acceptance",
  PENDING_APPROVAL: "Awaiting administrator approval",
  COMPLETED: "Completed",
  DISPUTED: "Disputed",
};

export const handoverStatusStyle = (status: HandoverStatus) =>
  status === "COMPLETED"
    ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
    : status === "DISPUTED"
      ? "border-destructive/30 bg-destructive/10 text-destructive"
      : status === "PENDING_APPROVAL"
        ? "border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400"
        : "border-primary/25 bg-primary/5 text-primary";

const has = (user: User, ...roles: RoleCode[]) => user.roles.some((role) => roles.includes(role));
export const isAdminActor = (user: User) => has(user, "SUPER_ADMIN", "SUB_ADMIN");
export const isSuperAdmin = (user: User) => has(user, "SUPER_ADMIN");
const openStatuses: HandoverStatus[] = ["DRAFT", "PENDING_INCOMING", "PENDING_APPROVAL"];
export const isOpenHandover = (handover: BranchHandover) => openStatuses.includes(handover.status);
export const isParty = (user: User, handover: BranchHandover) => user.id === handover.outgoingUserId || user.id === handover.incomingUserId;

/** The outgoing officer (the branch's current manager) or an administrator opens the claim. */
export function canOpenHandover(user: User, branchId: number, currentManagerId: number | null) {
  if (isAdminActor(user)) return true;
  return has(user, "BRANCH_MANAGER") && user.branch?.id === branchId && currentManagerId === user.id;
}

export function openHandoverError(
  user: User,
  input: { branchId: number; outgoingUserId?: number; incomingUserId: number },
  currentManagerId: number | null,
  openHandoverExists: boolean,
): string | null {
  if (!canOpenHandover(user, input.branchId, currentManagerId)) return "Only this branch's current manager or an administrator can open a handover claim.";
  if (openHandoverExists) return "An open handover already exists for this branch. Complete, approve, or resolve the dispute first.";
  const outgoingId = !isAdminActor(user) ? user.id : input.outgoingUserId ?? currentManagerId;
  if (outgoingId != null && outgoingId === input.incomingUserId) return "Outgoing and incoming officers must differ.";
  return null;
}

export const canSignOutgoing = (user: User, handover: BranchHandover) => handover.status === "DRAFT" && handover.outgoingUserId === user.id;
export const canAcknowledge = (user: User, handover: BranchHandover) => handover.status === "PENDING_INCOMING" && handover.incomingUserId === user.id;
export const canDispute = (user: User, handover: BranchHandover) => isOpenHandover(handover) && (isParty(user, handover) || isAdminActor(user));
export const canApprove = (user: User, handover: BranchHandover) => handover.status === "PENDING_APPROVAL" && isAdminActor(user) && !isParty(user, handover);

export function signNotesError(notes: string | null | undefined): string | null {
  return Array.from((notes ?? "").trim()).length > 5000 ? "Notes must not exceed 5,000 characters." : null;
}

export function acknowledgeError(user: User, handover: BranchHandover): string | null {
  return canAcknowledge(user, handover) ? null : "Only the named incoming officer can accept this handover after the outgoing officer signs.";
}

export function disputeError(user: User, handover: BranchHandover, reason: string | null | undefined): string | null {
  if (!canDispute(user, handover)) return "Only the two officers or an administrator can dispute this handover, and only before it is approved.";
  const length = Array.from((reason ?? "").trim()).length;
  return length < 10 || length > 5000 ? "The dispute reason must contain 10 to 5,000 characters." : null;
}

export function approveError(user: User, handover: BranchHandover): string | null {
  if (!isAdminActor(user)) return "Only an administrator can approve and complete a handover.";
  if (isParty(user, handover)) return "You are a party to this handover. Another administrator must approve it.";
  if (handover.status !== "PENDING_APPROVAL") return "The handover can only be approved after the incoming officer accepts it.";
  return null;
}

/** Transfer blocking: posting a replacement requires a COMPLETED handover between the two officers unless a super admin overrides (audited). */
export function transferBlockError(user: User, input: PostBranchManagerInput, handover: BranchHandover | null | undefined, currentOutgoingId: number | null): string | null {
  if (input.override) {
    if (!isSuperAdmin(user)) return "Only a super admin may override the handover requirement.";
    return null;
  }
  if (!handover) return "A completed handover between the outgoing and incoming officer is required, or an explicit super admin override.";
  if (handover.branchId !== input.branchId) return "The referenced handover belongs to a different branch.";
  if (handover.status !== "COMPLETED") return `The handover must be completed before posting a replacement. It is currently ${handoverStatusLabels[handover.status].toLowerCase()}.`;
  if (currentOutgoingId != null && handover.outgoingUserId !== currentOutgoingId) return "The handover's outgoing officer does not match the branch's current manager.";
  if (handover.incomingUserId !== input.incomingUserId) return "The handover's incoming officer does not match the officer being posted.";
  return null;
}

export interface HandoverClaimRow {
  label: string;
  value: string;
}

const money = new Intl.NumberFormat("en-NG", { style: "currency", currency: "NGN", maximumFractionDigits: 0 });
const prettyKey = (key: string) => key.replaceAll("_", " ").toLowerCase().replace(/(^|\s)\S/g, (letter) => letter.toUpperCase());
const countSummary = (counts: Record<string, number>) => {
  const entries = Object.entries(counts).filter(([, value]) => value > 0);
  return entries.length ? entries.map(([key, value]) => `${prettyKey(key)}: ${value}`).join(" · ") : "None";
};

/** The frozen claims document both officers sign against; it is never recomputed. */
export function handoverClaims(snapshot: HandoverSnapshot | null | undefined): HandoverClaimRow[] {
  if (!snapshot) return [];
  return [
    { label: "Claims captured at", value: snapshot.capturedAt ? new Date(snapshot.capturedAt).toLocaleString() : "—" },
    { label: "Members on the branch portfolio", value: `${snapshot.membersTotal} (${countSummary(snapshot.membersByStatus)})` },
    { label: "Disbursement requests by stage", value: countSummary(snapshot.disbursementsByStage) },
    { label: "Open disbursement value", value: money.format(snapshot.openDisbursementValue) },
    { label: "Unposted import batches", value: String(snapshot.unpostedImportBatches) },
    { label: "Open reconciliation items", value: String(snapshot.openReconciliationItems) },
    { label: "Latest closed period", value: snapshot.latestClosedPeriodId ? `Period #${snapshot.latestClosedPeriodId}` : "None" },
  ];
}
