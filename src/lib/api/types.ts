export type RoleCode =
  | "SUPER_ADMIN"
  | "SUB_ADMIN"
  | "FINANCE_OFFICER"
  | "BRANCH_MANAGER"
  | "AUDITOR";

export type MemberStatus = "PENDING" | "VERIFIED" | "ACTIVE" | "SUSPENDED" | "TERMINATED";
export type DisbursementStage = "DRAFT" | "SUBMITTED" | "BRANCH_APPROVED" | "FINANCE_REVIEW" | "AUTHORIZED" | "PAID";
export type PeriodStatus = "OPEN" | "REVIEW" | "CLOSED" | "LOCKED";
export type ImportStatus = "UPLOADED" | "MAPPED" | "VALIDATED" | "POSTED" | "FAILED";

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
  status: MemberStatus;
  branchId: number;
}

export interface Disbursement {
  id: number;
  reference: string;
  memberName: string;
  amount: number;
  stage: DisbursementStage;
  branchId: number;
  submittedAt: string;
}

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
}

export interface BranchHandover {
  id: number;
  branchId: number;
  outgoingUserId: number;
  incomingUserId: number;
  status: string;
  createdAt?: string;
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