import { describe, expect, it } from "vitest";
import { availableAction } from "./disbursement-workflow";
import type { Disbursement, User } from "./api/types";

const branch = { id: 1, name: "Lagos", code: "LAG", address: "" };
const manager: User = { id: 3, name: "M", email: "m@x", roles: ["BRANCH_MANAGER"], branch };
const finance: User = { id: 2, name: "F", email: "f@x", roles: ["FINANCE_OFFICER"], branch: null };
const item = (stage: Disbursement["stage"], requestedBy = 9): Disbursement => ({ id: 1, reference: "R", memberName: "A", amount: 10, stage, branchId: 1, submittedAt: "", requestedBy });

describe("disbursement maker-checker", () => {
  it("maker cannot branch-approve own request", () => expect(availableAction(manager, item("SUBMITTED", 3))).toBeNull());
  it("manager approves another maker's request", () => expect(availableAction(manager, item("SUBMITTED"))).toBe("branchApprove"));
  it("only the maker submits a draft", () => { expect(availableAction(manager, item("DRAFT", 3))).toBe("submit"); expect(availableAction(manager, item("DRAFT"))).toBeNull(); });
  it("finance cannot authorize", () => expect(availableAction(finance, item("FINANCE_REVIEW"))).toBeNull());
  it("finance marks authorized as paid", () => expect(availableAction(finance, item("AUTHORIZED"))).toBe("markPaid"));
});
