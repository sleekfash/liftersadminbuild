import type { CashDenominations, DailyRegister, RegisterStatus, RoleCode, User, VarianceCategory } from "./api/types";

/** Denomination ladder matching the physical till sheet (₦1,000 down to ₦10 notes). */
export const denominationLadder: { key: keyof CashDenominations; value: number; label: string }[] = [
  { key: "n1000", value: 1000, label: "₦1,000" },
  { key: "n500", value: 500, label: "₦500" },
  { key: "n200", value: 200, label: "₦200" },
  { key: "n100", value: 100, label: "₦100" },
  { key: "n50", value: 50, label: "₦50" },
  { key: "n20", value: 20, label: "₦20" },
  { key: "n10", value: 10, label: "₦10" },
];

const emptyDenominations: CashDenominations = { n1000: 0, n500: 0, n200: 0, n100: 0, n50: 0, n20: 0, n10: 0 };

/** Physical cash = sum(count × denomination value) — the workbook PHY CIH column. */
export function totalPhysicalCash(denominations: CashDenominations): number {
  return denominationLadder.reduce((total, item) => total + (denominations[item.key] ?? 0) * item.value, 0);
}

/** Cash book closing balance: opening + collections − disbursements (the CB CIH column). */
export function cashBookClosing(register: Pick<DailyRegister, "cashBookOpening" | "totalCollections" | "totalDisbursements">): number {
  return register.cashBookOpening + register.totalCollections - register.totalDisbursements;
}

/** Expected vault cash: cash book closing − monitoring cash (CB CIH − MONITORING). */
export function expectedVaultCash(register: Pick<DailyRegister, "cashBookOpening" | "totalCollections" | "totalDisbursements" | "monitoringCash">): number {
  return cashBookClosing(register) - register.monitoringCash;
}

/** DIFF: expected vault cash − physical counted. Positive means the vault is short. */
export function computeVariance(register: Pick<DailyRegister, "cashBookOpening" | "totalCollections" | "totalDisbursements" | "monitoringCash">, denominations: CashDenominations): number {
  return expectedVaultCash(register) - totalPhysicalCash(denominations);
}

export const varianceCategories: Record<VarianceCategory, string> = {
  FIELD_COLLECTION_SHORTAGE: "Field collection shortage",
  UNRECORDED_DISBURSEMENT: "Unrecorded disbursement",
  PHYSICAL_CASH_OVERAGE: "Physical cash overage",
  TRANSIT_TIMING_DELAY: "Transit timing delay",
  OTHER: "Other",
};

const has = (user: User, ...roles: RoleCode[]) => user.roles.some((role) => roles.includes(role));
const isBranchBound = (user: User) => !has(user, "SUPER_ADMIN", "SUB_ADMIN");
const sameBranch = (user: User, register: DailyRegister) => !user.branch || user.branch.id === register.branchId;

/** Only supervisors may categorise and authorise a variance; admins operate across branches, managers within their own. */
export function canResolveVariance(user: User, register: DailyRegister): boolean {
  if (!has(user, "BRANCH_MANAGER", "SUPER_ADMIN", "SUB_ADMIN")) return false;
  return !isBranchBound(user) || sameBranch(user, register);
}

export function resolveVarianceError(user: User, register: DailyRegister): string | null {
  if (register.status !== "VARIANCE_FLAGGED") return "Only a flagged variance can be resolved.";
  if (!canResolveVariance(user, register)) return "A branch manager or administrator of this branch must authorise the variance.";
  return null;
}

export function validateVarianceResolution(input: { category?: VarianceCategory; reason?: string }): string | null {
  if (!input.category || !Object.hasOwn(varianceCategories, input.category)) return "Select a variance category.";
  const length = Array.from((input.reason ?? "").trim()).length;
  return length < 15 || length > 1000 ? "Justification must contain 15 to 1,000 characters." : null;
}

/** Locking requires the register to be settled; flagged variances block it. */
export function lockRegisterError(user: User, register: DailyRegister): string | null {
  if (!has(user, "BRANCH_MANAGER", "SUPER_ADMIN", "SUB_ADMIN")) return "Only a branch manager or administrator may lock a register.";
  if (isBranchBound(user) && !sameBranch(user, register)) return "You may only lock the register for your own branch.";
  if (register.status === "VARIANCE_FLAGGED") return "Resolve variance before locking.";
  if (register.status === "LOCKED") return "This register is already locked.";
  return null;
}

export function canCountRegister(user: User, register: DailyRegister): boolean {
  if (register.status === "LOCKED") return false;
  if (!has(user, "BRANCH_MANAGER", "FINANCE_OFFICER", "SUPER_ADMIN", "SUB_ADMIN")) return false;
  return !isBranchBound(user) || sameBranch(user, register);
}

export const registerStatusLabels: Record<RegisterStatus, string> = {
  OPEN: "Open",
  COUNTED: "Counted",
  BALANCED: "Balanced",
  VARIANCE_FLAGGED: "Variance flagged",
  VARIANCE_APPROVED: "Variance approved",
  LOCKED: "Locked",
};

export function emptyCount(): CashDenominations {
  return { ...emptyDenominations };
}
