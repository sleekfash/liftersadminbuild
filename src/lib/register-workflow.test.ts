import { describe, expect, it } from "vitest";
import { computeVariance, emptyCount, expectedVaultCash, lockRegisterError, resolveVarianceError, totalPhysicalCash, validateVarianceResolution } from "./register-workflow";
import type { CashDenominations, DailyRegister, User } from "./api/types";

const branchUser = (id: number, roles: User["roles"], branchId: number): User => ({ id, name: `User ${id}`, email: "user@example.com", roles, branch: { id: branchId, name: "Branch", code: "B", address: "" } });
const crossBranchUser = (id: number, roles: User["roles"]): User => ({ id, name: `User ${id}`, email: "user@example.com", roles, branch: null });
const register = (overrides: Partial<DailyRegister> = {}): DailyRegister => ({
  id: 1,
  branchId: 7,
  branchName: "Owode",
  registerDate: "2026-10-24",
  status: "VARIANCE_FLAGGED",
  cashBookOpening: 200_000,
  totalCollections: 180_000,
  totalDisbursements: 90_000,
  monitoringCash: 40_000,
  expectedPhysicalCash: 250_000,
  physicalCashCounted: 284_800,
  variance: -34_800,
  denominations: { n1000: 248, n500: 70, n200: 8, n100: 2, n50: 0, n20: 0, n10: 0 },
  ...overrides,
});

describe("physical till counting", () => {
  it("sums each denomination by its value", () => {
    const counts: CashDenominations = { n1000: 248, n500: 70, n200: 8, n100: 2, n50: 0, n20: 0, n10: 0 };
    expect(totalPhysicalCash(counts)).toBe(284_800);
  });

  it("computes expected vault cash as cash book closing minus monitoring cash", () => {
    const item = register();
    expect(cashBookClosing(item)).toBe(290_000);
    expect(expectedVaultCash(item)).toBe(250_000);
  });

  it("reproduces the Owode workbook variance of -34,800", () => {
    const item = register();
    expect(computeVariance(item, item.denominations as CashDenominations)).toBe(-34_800);
  });

  it("treats a zero variance as balanced", () => {
    const counts = { ...emptyCount(), n1000: 250 };
    expect(computeVariance(register(), counts)).toBe(0);
  });
});

describe("variance resolution", () => {
  it("requires a supervisor role on the register's branch", () => {
    expect(resolveVarianceError(branchUser(3, ["BRANCH_MANAGER"], 7), register())).toBeNull();
    expect(resolveVarianceError(branchUser(2, ["FINANCE_OFFICER"], 7), register())).toContain("branch manager or administrator");
    expect(resolveVarianceError(branchUser(3, ["BRANCH_MANAGER"], 1), register())).toContain("branch manager or administrator");
    expect(resolveVarianceError(branchUser(1, ["SUPER_ADMIN"], 9), register())).toBeNull();
  });

  it("only applies to flagged registers", () => {
    expect(resolveVarianceError(branchUser(3, ["BRANCH_MANAGER"], 7), register({ status: "BALANCED" }))).toContain("flagged");
  });

  it("enforces the 15 to 1,000 character justification", () => {
    expect(validateVarianceResolution({ category: "OTHER", reason: "short" })).toContain("15");
    expect(validateVarianceResolution({ category: "OTHER", reason: "x".repeat(15) })).toBeNull();
    expect(validateVarianceResolution({ category: "OTHER", reason: "x".repeat(1000) })).toBeNull();
    expect(validateVarianceResolution({ category: "OTHER", reason: "x".repeat(1001) })).toContain("1,000");
    expect(validateVarianceResolution({ reason: "x".repeat(20) })).toContain("category");
  });
});

describe("register locking", () => {
  it("rejects locking a flagged variance", () => {
    expect(lockRegisterError(branchUser(3, ["BRANCH_MANAGER"], 7), register())).toBe("Resolve variance before locking.");
  });

  it("allows a same-branch manager to lock a balanced register", () => {
    expect(lockRegisterError(branchUser(3, ["BRANCH_MANAGER"], 7), register({ status: "BALANCED" }))).toBeNull();
  });

  it("denies finance officers and other branches", () => {
    expect(lockRegisterError(branchUser(2, ["FINANCE_OFFICER"], 7), register({ status: "BALANCED" }))).toContain("branch manager or administrator");
    expect(lockRegisterError(branchUser(3, ["BRANCH_MANAGER"], 1), register({ status: "BALANCED" }))).toContain("own branch");
  });

  it("blocks double locking", () => {
    expect(lockRegisterError(branchUser(3, ["BRANCH_MANAGER"], 7), register({ status: "LOCKED" }))).toContain("already locked");
  });
});

function cashBookClosing(item: DailyRegister) {
  return item.cashBookOpening + item.totalCollections - item.totalDisbursements;
}
