import { describe, expect, it } from "vitest";
import { acknowledgeError, approveError, canOpenHandover, disputeError, handoverClaims, openHandoverError, signNotesError, transferBlockError } from "./handover-workflow";
import type { BranchHandover, User } from "./api/types";

const admin: User = { id: 1, name: "Admin", email: "a@x.test", roles: ["SUPER_ADMIN"], branch: null };
const subAdmin: User = { id: 9, name: "Sub", email: "s@x.test", roles: ["SUB_ADMIN"], branch: null };
const branchA = { id: 10, name: "Branch A", code: "BRA", address: "" };
const branchB = { id: 11, name: "Branch B", code: "BRB", address: "" };
const outgoing: User = { id: 2, name: "Outgoing", email: "o@x.test", roles: ["BRANCH_MANAGER"], branch: branchA };
const incoming: User = { id: 3, name: "Incoming", email: "i@x.test", roles: ["BRANCH_MANAGER"], branch: branchB };
const other: User = { id: 4, name: "Other", email: "x@x.test", roles: ["BRANCH_MANAGER"], branch: branchB };

const handover = (over: Partial<BranchHandover> = {}): BranchHandover => ({ id: 1, branchId: 10, outgoingUserId: 2, incomingUserId: 3, status: "DRAFT", ...over });

describe("handover workflow guards", () => {
  it("lets only the branch's current manager or an admin open a claim", () => {
    expect(canOpenHandover(outgoing, 10, 2)).toBe(true);
    expect(canOpenHandover(other, 10, 2)).toBe(false);
    expect(canOpenHandover(admin, 10, null)).toBe(true);
  });

  it("blocks opening when officers match or an open handover exists", () => {
    expect(openHandoverError(outgoing, { branchId: 10, incomingUserId: 2 }, 2, false)).toMatch(/differ/);
    expect(openHandoverError(outgoing, { branchId: 10, incomingUserId: 3 }, 2, true)).toMatch(/already exists/);
    expect(openHandoverError(outgoing, { branchId: 10, incomingUserId: 3 }, 2, false)).toBeNull();
    expect(openHandoverError(other, { branchId: 10, incomingUserId: 3 }, 2, false)).toMatch(/current manager/);
  });

  it("restricts acceptance to the named incoming officer at the right stage", () => {
    expect(acknowledgeError(incoming, handover({ status: "PENDING_INCOMING" }))).toBeNull();
    expect(acknowledgeError(incoming, handover())).toMatch(/named incoming officer/);
    expect(acknowledgeError(outgoing, handover({ status: "PENDING_INCOMING" }))).toMatch(/named incoming officer/);
  });

  it("disputes require a party or admin and a 10–5,000 character reason", () => {
    expect(disputeError(incoming, handover(), "r".repeat(10))).toBeNull();
    expect(disputeError(other, handover(), "Not my handover at all")).toMatch(/two officers/);
    expect(disputeError(admin, handover(), "short")).toMatch(/10 to 5,000/);
    expect(disputeError(incoming, handover({ status: "COMPLETED" }), "r".repeat(20))).toMatch(/two officers/);
  });

  it("keeps outgoing sign-off notes within limits", () => {
    expect(signNotesError("Vault counted and keys returned.")).toBeNull();
    expect(signNotesError("x".repeat(5001))).toMatch(/5,000/);
  });

  it("approval needs a non-party administrator", () => {
    expect(approveError(admin, handover({ status: "PENDING_APPROVAL" }))).toBeNull();
    expect(approveError(subAdmin, handover({ status: "PENDING_APPROVAL" }))).toBeNull();
    expect(approveError(subAdmin, handover({ status: "PENDING_APPROVAL", incomingUserId: 9 }))).toMatch(/Another administrator/);
    expect(approveError(subAdmin, handover({ status: "PENDING_INCOMING" }))).toMatch(/after the incoming officer/);
  });

  it("blocks transfers until the matching handover is completed unless a super admin overrides", () => {
    expect(transferBlockError(subAdmin, { branchId: 10, incomingUserId: 3 }, null, 2)).toMatch(/completed handover/);
    expect(transferBlockError(subAdmin, { branchId: 10, incomingUserId: 3, override: true }, null, 2)).toMatch(/super admin/);
    expect(transferBlockError(admin, { branchId: 10, incomingUserId: 3, override: true }, null, 2)).toBeNull();
    expect(transferBlockError(subAdmin, { branchId: 10, incomingUserId: 3 }, handover({ status: "PENDING_APPROVAL" }), 2)).toMatch(/completed before posting/);
    expect(transferBlockError(subAdmin, { branchId: 10, incomingUserId: 3 }, handover({ status: "COMPLETED" }), 2)).toBeNull();
    expect(transferBlockError(subAdmin, { branchId: 10, incomingUserId: 4 }, handover({ status: "COMPLETED" }), 2)).toMatch(/incoming officer does not match/);
    expect(transferBlockError(subAdmin, { branchId: 11, incomingUserId: 3 }, handover({ status: "COMPLETED" }), 2)).toMatch(/different branch/);
  });

  it("freezes the claims snapshot into readable rows", () => {
    const rows = handoverClaims({ capturedAt: "2026-10-31T09:00:00Z", membersByStatus: { ACTIVE: 4 }, membersTotal: 4, disbursementsByStage: { SUBMITTED: 2 }, openDisbursementValue: 850000, unpostedImportBatches: 1, openReconciliationItems: 0, latestClosedPeriodId: 2 });
    expect(rows.some((row) => row.label === "Members on the branch portfolio" && row.value.includes("4"))).toBe(true);
    expect(rows.some((row) => row.label === "Open disbursement value" && row.value.includes("850,000"))).toBe(true);
    expect(handoverClaims(null)).toEqual([]);
  });
});
