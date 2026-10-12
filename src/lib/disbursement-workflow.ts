import type { Disbursement, DisbursementAction, DisbursementStage, RoleCode, User } from "./api/types";

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

/** Returns the single action this user may take on the item right now, or null. */
export function availableAction(user: User, item: Disbursement): DisbursementAction | null {
  const isSuper = has(user, "SUPER_ADMIN");
  const isMaker = item.requestedBy === user.id;
  switch (item.stage) {
    case "DRAFT": return isSuper || (isMaker && sameBranch(user, item)) ? "submit" : null;
    case "SUBMITTED": return isSuper || (has(user, "BRANCH_MANAGER") && sameBranch(user, item) && !isMaker) ? "branchApprove" : null;
    case "BRANCH_APPROVED": return isSuper || (has(user, "FINANCE_OFFICER") && sameBranch(user, item)) ? "financeReview" : null;
    case "FINANCE_REVIEW": return isSuper ? "authorize" : null;
    case "AUTHORIZED": return isSuper || (has(user, "FINANCE_OFFICER") && sameBranch(user, item)) ? "markPaid" : null;
    default: return null;
  }
}
