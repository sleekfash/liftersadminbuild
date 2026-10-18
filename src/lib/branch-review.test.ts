import { describe, expect, it } from "vitest";
import { branchReviewError, validateBranchReview } from "./disbursement-workflow";
import { mockCreateDisbursement, mockGetDisbursement, mockLogin, mockReviewDisbursement, mockSubmitDisbursement } from "./api/mock";
import type { Disbursement, User } from "./api/types";

const manager: User = { id: 3, name: "Manager", email: "m@example.test", roles: ["BRANCH_MANAGER"], branch: { id: 1, name: "Lagos", code: "LAG", address: "" } };
const item: Disbursement = { id: 1, reference: "R", memberName: "Member", amount: 100, branchId: 1, stage: "SUBMITTED", submittedAt: "", requestedBy: 2 };
describe("branch review rules", () => {
  it("allows only same-branch submitted manager reviews", () => {
    expect(branchReviewError(manager, item)).toBeNull();
    expect(branchReviewError(manager, { ...item, branchId: 2 })).not.toBeNull();
    expect(branchReviewError({ ...manager, branch: null }, item)).not.toBeNull();
    expect(branchReviewError({ ...manager, roles: ["AUDITOR"] }, item)).not.toBeNull();
    expect(branchReviewError(manager, { ...item, stage: "BRANCH_APPROVED" })).not.toBeNull();
  });
  it("blocks self approval and rejection including super administrators", () => {
    expect(branchReviewError(manager, { ...item, requestedBy: 3 })).not.toBeNull();
    expect(branchReviewError({ ...manager, roles: ["SUPER_ADMIN"] }, { ...item, requestedBy: 3 })).not.toBeNull();
  });
  it("blocks unknown maker identity", () => expect(branchReviewError(manager, { ...item, requestedBy: undefined })).not.toBeNull());
  it("approval remarks are optional and limited to 1000 characters", () => {
    expect(validateBranchReview({ decision: "approve" })).toBeNull();
    expect(validateBranchReview({ decision: "approve", remarks: "x".repeat(1000) })).toBeNull();
    expect(validateBranchReview({ decision: "approve", remarks: "x".repeat(1001) })).not.toBeNull();
  });
  it("requires a rejection category", () => expect(validateBranchReview({ decision: "reject", reason: "x".repeat(15) })).not.toBeNull());
  it("rejection justification is 15 to 1000 trimmed characters", () => {
    for (const length of [15, 1000]) expect(validateBranchReview({ decision: "reject", category: "OTHER", reason: "x".repeat(length) })).toBeNull();
    for (const length of [0, 14, 1001]) expect(validateBranchReview({ decision: "reject", category: "OTHER", reason: "x".repeat(length) })).not.toBeNull();
    expect(validateBranchReview({ decision: "reject", category: "OTHER", reason: " ".repeat(20) })).not.toBeNull();
  });
});
describe("mock decision execution", () => {
  it("persists approval and sequential identity stamps and blocks replay", async () => {
    const maker = (await mockLogin("admin@example.com", "password")).user;
    const draft = await mockCreateDisbursement({ memberId: 1, amount: 100, purpose: "Education" }, maker);
    await mockSubmitDisbursement(draft.id);
    await expect(mockReviewDisbursement(draft.id, { decision: "approve" }, maker)).rejects.toThrow();
    const approved = await mockReviewDisbursement(draft.id, { decision: "approve", remarks: "Checked" }, manager);
    expect(approved.stage).toBe("BRANCH_APPROVED");
    expect((await mockGetDisbursement(draft.id, manager)).timeline?.map((s) => [s.stage, s.actorId])).toEqual([["SUBMITTED", maker.id], ["BRANCH_APPROVED", manager.id]]);
    await expect(mockReviewDisbursement(draft.id, { decision: "approve" }, manager)).rejects.toThrow();
  });
  it("requires valid rejection and persists terminal stage, category and reason", async () => {
    const maker = (await mockLogin("admin@example.com", "password")).user;
    const draft = await mockCreateDisbursement({ memberId: 1, amount: 100, purpose: "Education" }, maker);
    await mockSubmitDisbursement(draft.id);
    await expect(mockReviewDisbursement(draft.id, { decision: "reject", category: "OTHER", reason: "Too short" }, manager)).rejects.toThrow();
    await expect(mockReviewDisbursement(draft.id, { decision: "reject", category: "OTHER", reason: "Missing supporting documents" }, { ...manager, branch: { ...manager.branch as NonNullable<User["branch"]>, id: 2 } })).rejects.toThrow();
    await mockReviewDisbursement(draft.id, { decision: "reject", category: "INCOMPLETE_DOCUMENTATION", reason: "Missing supporting documents" }, manager);
    const rejected = await mockGetDisbursement(draft.id, manager);
    expect(rejected.stage).toBe("REJECTED");
    expect(rejected.rejectionCategory).toBe("INCOMPLETE_DOCUMENTATION");
    expect(rejected.rejectedReason).toBe("Missing supporting documents");
    expect(rejected.timeline?.at(-1)?.actorId).toBe(manager.id);
  });
});