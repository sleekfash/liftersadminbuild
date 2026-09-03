export type RoleCode =
  | "SUPER_ADMIN"
  | "SUB_ADMIN"
  | "FINANCE_OFFICER"
  | "BRANCH_MANAGER"
  | "AUDITOR";

export type MemberStatus = "PENDING" | "VERIFIED" | "ACTIVE" | "SUSPENDED" | "TERMINATED";
export type DisbursementStage = "DRAFT" | "SUBMITTED" | "BRANCH_APPROVED" | "FINANCE_REVIEW" | "AUTHORIZED" | "PAID";

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