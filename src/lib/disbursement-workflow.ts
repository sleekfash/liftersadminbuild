import type { BranchReviewInput, Disbursement, DisbursementAction, DisbursementStage, RejectionCategory, RoleCode, User } from "./api/types";

/** Maker-checker transitions, mirroring the Laravel DisbursementRequestPolicy. */
export const transitions: Record<DisbursementAction, { from: DisbursementStage; to: DisbursementStage; label: string }> = {
  submit: { from: "DRAFT", to: "SUBMITTED", label: "Submit" },
  branchApprove: { from: "SUBMITTED", to: "BRANCH_APPROVED", label: "Branch approve" },
  financeReview: { from: "BRANCH_APPROVED", to: "FINANCE_REVIEW", label: "Finance review" },
  authorize: { from: "FINANCE_REVIEW", to: "AUTHORIZED", label: "Authorize" },
  markPaid: { from: "AUTHORIZED", to: "PAID", label: "Mark paid" },
};

const has = (user: User, ...roles: RoleCode[]) => user.roles.some((role) => roles.includes(role));
const sameBranch = (user: User, item: Disbursement) => !user.branch || user.branch.id === item.branchId;

export function canCreateDisbursement(user: User) {
  return has(user, "SUPER_ADMIN", "SUB_ADMIN", "BRANCH_MANAGER", "FINANCE_OFFICER");
}

export const rejectionCategories: Record<RejectionCategory, string> = {
  INCOMPLETE_DOCUMENTATION: "Incomplete documentation",
  MEMBER_ELIGIBILITY: "Member eligibility",
  AMOUNT_PURPOSE_MISMATCH: "Amount / purpose mismatch",
  DUPLICATE_REQUEST: "Duplicate request",
  OTHER: "Other",
};

export function canInspectBranchReview(user: User, item: Disbursement) {
  return item.stage === "SUBMITTED" && (has(user, "SUPER_ADMIN") || (has(user, "BRANCH_MANAGER") && user.branch?.id === item.branchId));
}

export function branchReviewError(user: User, item: Disbursement): string | null {
  if (!canInspectBranchReview(user, item)) return "Only an authorised reviewer can review submitted requests in their branch.";
  if (!item.requestedBy) return "Requester identity is missing. Review is blocked until it is confirmed.";
  if (item.requestedBy === user.id) return "You cannot approve or reject your own submission. Another reviewer must decide.";
  return null;
}

export function validateBranchReview(input: BranchReviewInput): string | null {
  if (input.decision === "approve") return Array.from(input.remarks ?? "").length > 1000 ? "Remarks must not exceed 1,000 characters." : null;
  if (input.decision !== "reject") return "Select a valid decision.";
  if (!input.category || !Object.hasOwn(rejectionCategories, input.category)) return "Select a rejection category.";
  const length = Array.from((input.reason ?? "").trim()).length;
  return length < 15 || length > 1000 ? "Justification must contain 15 to 1,000 characters." : null;
}

/** Returns the single action this user may take on the item right now, or null. */
export function availableAction(user: User, item: Disbursement): DisbursementAction | null {
  const isSuper = has(user, "SUPER_ADMIN");
  const isMaker = item.requestedBy === user.id;
  switch (item.stage) {
    case "DRAFT": return isSuper || (isMaker && sameBranch(user, item)) ? "submit" : null;
    case "SUBMITTED": return branchReviewError(user, item) === null ? "branchApprove" : null;
    case "BRANCH_APPROVED": return isSuper || (has(user, "FINANCE_OFFICER") && sameBranch(user, item)) ? "financeReview" : null;
    case "FINANCE_REVIEW": return isSuper ? "authorize" : null;
    case "AUTHORIZED": return isSuper || (has(user, "FINANCE_OFFICER") && sameBranch(user, item)) ? "markPaid" : null;
    default: return null;
  }
}
