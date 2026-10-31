export type RoleCode =
  | "SUPER_ADMIN"
  | "SUB_ADMIN"
  | "FINANCE_OFFICER"
  | "BRANCH_MANAGER"
  | "CREDIT_OFFICER"
  | "AUDITOR";

export type MemberStatus = "PENDING" | "VERIFIED" | "ACTIVE" | "SUSPENDED" | "TERMINATED";
export type DisbursementStage = "DRAFT" | "SUBMITTED" | "BRANCH_APPROVED" | "FINANCE_REVIEW" | "AUTHORIZED" | "PAID" | "REJECTED";
export type PeriodStatus = "OPEN" | "REVIEW" | "CLOSED" | "LOCKED";
export type ImportStatus = "UPLOADED" | "MAPPED" | "VALIDATED" | "POSTED" | "FAILED";
export type RegisterStatus = "OPEN" | "COUNTED" | "BALANCED" | "VARIANCE_FLAGGED" | "VARIANCE_APPROVED" | "LOCKED";
export type VarianceCategory = "FIELD_COLLECTION_SHORTAGE" | "UNRECORDED_DISBURSEMENT" | "PHYSICAL_CASH_OVERAGE" | "TRANSIT_TIMING_DELAY" | "OTHER";

export interface CashDenominations {
  n1000: number;
  n500: number;
  n200: number;
  n100: number;
  n50: number;
  n20: number;
  n10: number;
}

export interface Branch {
  id: number;
  name: string;
  code: string;
  address: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  roles: RoleCode[];
  branch: Branch | null;
}

export interface AdminUser extends User {
  isActive: boolean;
  createdAt?: string;
}

export interface Member {
  id: number;
  name: string;
  memberNumber: string;
  phone?: string;
  status: MemberStatus;
  branchId: number;
}

export interface ReviewStamp {
  stage: DisbursementStage;
  at: string;
  actorId?: number;
  actorName?: string;
  remarks?: string;
}
export type RejectionCategory = "INCOMPLETE_DOCUMENTATION" | "MEMBER_ELIGIBILITY" | "AMOUNT_PURPOSE_MISMATCH" | "DUPLICATE_REQUEST" | "OTHER";
export interface BranchReviewInput {
  decision: "approve" | "reject";
  remarks?: string;
  category?: RejectionCategory;
  reason?: string;
}

export interface Disbursement {
  id: number;
  reference: string;
  memberName: string;
  amount: number;
  stage: DisbursementStage;
  branchId: number;
  submittedAt: string;
  requestedBy?: number;
  purpose?: string;
  memberId?: number;
  umid?: string;
  phone?: string;
  verificationStatus?: MemberStatus;
  requesterName?: string;
  category?: string;
  rejectionCategory?: RejectionCategory;
  rejectedReason?: string;
  timeline?: ReviewStamp[];
}
export type DisbursementAction = "submit" | "branchApprove" | "financeReview" | "authorize" | "markPaid";

export interface TreasuryTransaction {
  id: number;
  type: string;
  amount: number;
  balanceAfter: number;
  reference: string;
  disbursementId?: number;
  periodId?: number;
  occurredOn?: string;
}

export interface MonthlyPeriod {
  id: number;
  name: string;
  month: number;
  year: number;
  status: PeriodStatus;
  openingBalance: number;
  closingBalance?: number;
}

export interface ReconciliationRun {
  id: number;
  monthlyPeriodId: number;
  status: string;
  itemsCount: number;
  createdAt?: string;
}

export interface ReconciliationItem {
  id: number;
  reconciliationRunId: number;
  severity: string;
  status: string;
  description: string;
}

export interface ImportBatch {
  id: number;
  filename: string;
  status: ImportStatus;
  branchId: number;
  rowsCount: number;
  createdAt?: string;
}

export interface ImportRow {
  id: number;
  importBatchId: number;
  rowNumber: number;
  status: string;
  errors?: string[];
}

export interface AuditLog {
  id: number;
  eventType: string;
  summary: string;
  actorId?: number;
  createdAt?: string;
}

export interface BranchManagerAssignment {
  id: number;
  branchId: number;
  userId: number;
  isActive: boolean;
  startedAt?: string;
  endedAt?: string;
  endedReason?: string | null;
}

export type HandoverStatus = "DRAFT" | "PENDING_INCOMING" | "PENDING_APPROVAL" | "COMPLETED" | "DISPUTED";

export interface HandoverSnapshot {
  capturedAt: string;
  membersByStatus: Record<string, number>;
  membersTotal: number;
  disbursementsByStage: Record<string, number>;
  openDisbursementValue: number;
  unpostedImportBatches: number;
  openReconciliationItems: number;
  latestClosedPeriodId?: number | null;
}

export interface HandoverAuditEntry {
  id: number;
  event: string;
  summary: string;
  actorId?: number;
  actorName?: string;
  at: string;
}

export interface BranchHandover {
  id: number;
  branchId: number;
  branchName?: string;
  branchCode?: string;
  outgoingUserId: number | null;
  outgoingUserName?: string | null;
  incomingUserId: number | null;
  incomingUserName?: string | null;
  status: HandoverStatus;
  snapshot?: HandoverSnapshot | null;
  outgoingSignedAt?: string | null;
  outgoingNotes?: string | null;
  incomingSignedAt?: string | null;
  incomingNotes?: string | null;
  disputeReason?: string | null;
  approvedBy?: number | null;
  approvedByName?: string | null;
  approvedAt?: string | null;
  createdBy?: number | null;
  createdAt?: string;
  auditTrail?: HandoverAuditEntry[];
}

export interface OpenHandoverInput {
  branchId: number;
  outgoingUserId?: number;
  incomingUserId: number;
}

export interface PostBranchManagerInput {
  branchId: number;
  incomingUserId: number;
  handoverId?: number;
  override?: boolean;
  reason?: string;
}

export interface DailyRegister {
  id: number;
  branchId: number;
  branchName: string;
  registerDate: string;
  status: RegisterStatus;
  cashBookOpening: number;
  totalCollections: number;
  totalDisbursements: number;
  monitoringCash: number;
  expectedPhysicalCash: number;
  physicalCashCounted: number;
  variance: number;
  denominations?: CashDenominations | null;
  varianceCategory?: VarianceCategory | null;
  varianceReason?: string | null;
  varianceApprovedBy?: string | null;
  closedBy?: string | null;
  closedAt?: string | null;
  lockedAt?: string | null;
}

export interface SaveRegisterCountInput {
  registerId: number;
  denominations: CashDenominations;
}

export interface ResolveVarianceInput {
  registerId: number;
  category: VarianceCategory;
  reason: string;
}

export interface ApiPage<T> {
  data: T[];
  meta?: { current_page?: number; last_page?: number; per_page?: number; total?: number };
}

export interface AdminData {
  branches: Branch[];
  users: AdminUser[];
  roles: { id: number; name: string; code: RoleCode; description?: string }[];
  auditLogs: AuditLog[];
}

export interface DashboardData {
  members: { status: MemberStatus; count: number }[];
  disbursements: { stage: DisbursementStage; count: number; value: number }[];
  openReconciliation: number;
  pendingHandovers: number;
  auditEvents: { id: number; label: string; actor: string; time: string; severity: "info" | "warning" }[];
  branches: { branch: Branch; members: number; openValue: number; health: "On track" | "Needs attention" }[];
}

export interface LoginResponse {
  token: string;
  user: User;
}