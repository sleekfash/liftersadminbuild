import type { AdminData, AdminUser, Branch, DashboardData, Disbursement, ImportBatch, LoginResponse, Member, MonthlyPeriod, RoleCode, TreasuryTransaction, User } from "./types";

const branches: Branch[] = [
  { id: 1, name: "Lagos Central", code: "LAG-01", address: "12 Marina Road, Lagos" },
  { id: 2, name: "Ibadan North", code: "IBA-02", address: "4 Mokola Hill, Ibadan" },
];

const users: Array<User & { password: string }> = [
  { id: 1, name: "Amaka Okafor", email: "admin@example.com", roles: ["SUPER_ADMIN"], branch: null, password: "password" },
  { id: 2, name: "Tunde Balogun", email: "finance@example.com", roles: ["FINANCE_OFFICER"], branch: null, password: "password" },
  { id: 3, name: "Adaeze Nwosu", email: "manager.lagos@example.com", roles: ["BRANCH_MANAGER"], branch: branches[0], password: "password" },
  { id: 4, name: "Chinedu Eze", email: "auditor@example.com", roles: ["AUDITOR"], branch: null, password: "password" },
];

const members: Member[] = [
  { id: 1, name: "Grace Adebayo", memberNumber: "LT-24018", status: "ACTIVE", branchId: 1 },
  { id: 2, name: "Michael Okoro", memberNumber: "LT-24021", status: "ACTIVE", branchId: 1 },
  { id: 3, name: "Fatima Bello", memberNumber: "LT-24031", status: "VERIFIED", branchId: 1 },
  { id: 4, name: "Daniel Uche", memberNumber: "LT-23980", status: "PENDING", branchId: 1 },
  { id: 5, name: "Sarah Yusuf", memberNumber: "LT-23884", status: "ACTIVE", branchId: 2 },
  { id: 6, name: "Ibrahim Musa", memberNumber: "LT-23902", status: "SUSPENDED", branchId: 2 },
];

const disbursements: Disbursement[] = [
  { id: 1, reference: "DSB-2026-0041", memberName: "Grace Adebayo", amount: 850000, stage: "SUBMITTED", branchId: 1, submittedAt: "Today, 09:42" },
  { id: 2, reference: "DSB-2026-0039", memberName: "Michael Okoro", amount: 420000, stage: "BRANCH_APPROVED", branchId: 1, submittedAt: "Yesterday, 16:18" },
  { id: 3, reference: "DSB-2026-0038", memberName: "Sarah Yusuf", amount: 1200000, stage: "FINANCE_REVIEW", branchId: 2, submittedAt: "Yesterday, 14:06" },
  { id: 4, reference: "DSB-2026-0036", memberName: "Ibrahim Musa", amount: 300000, stage: "AUTHORIZED", branchId: 2, submittedAt: "19 Aug, 11:20" },
];

const treasury: TreasuryTransaction[] = [
  { id: 1, type: "DISBURSEMENT", amount: -420000, balanceAfter: 15800000, reference: "DSB-2026-0039", disbursementId: 2, periodId: 1, occurredOn: "2026-08-20" },
  { id: 2, type: "FUNDING", amount: 5000000, balanceAfter: 16220000, reference: "FND-2026-08", periodId: 1, occurredOn: "2026-08-01" },
];
const periods: MonthlyPeriod[] = [
  { id: 1, name: "August 2026", month: 8, year: 2026, status: "REVIEW", openingBalance: 12000000, closingBalance: 16220000 },
  { id: 2, name: "July 2026", month: 7, year: 2026, status: "LOCKED", openingBalance: 9800000, closingBalance: 12000000 },
];
const imports: ImportBatch[] = [
  { id: 1, filename: "august-members.xlsx", status: "VALIDATED", branchId: 1, rowsCount: 184, createdAt: "2026-08-21" },
  { id: 2, filename: "ibadan-disbursements.xlsx", status: "UPLOADED", branchId: 2, rowsCount: 42, createdAt: "2026-08-20" },
];

const delay = (ms = 120) => new Promise((resolve) => setTimeout(resolve, ms));
const visible = <T extends { branchId: number }>(items: T[], user: User) => user.branch ? items.filter((item) => item.branchId === user.branch?.id) : items;
const toUser = (user: User & { password?: string }): User => { const { password: _password, ...safe } = user; return safe; };

export const mockLogin = async (email: string, password: string): Promise<LoginResponse> => {
  await new Promise((resolve) => setTimeout(resolve, 350));
  const match = users.find((user) => user.email.toLowerCase() === email.toLowerCase() && user.password === password);
  if (!match) throw new Error("The email or password is not correct.");
  const { password: _password, ...user } = match;
  return { token: `mock-token-${user.id}`, user };
};

export const mockDashboard = async (user: User): Promise<DashboardData> => {
  await new Promise((resolve) => setTimeout(resolve, 180));
  const visibleMembers = user.branch ? members.filter((member) => member.branchId === user.branch?.id) : members;
  const visibleDisbursements = user.branch ? disbursements.filter((item) => item.branchId === user.branch?.id) : disbursements;
  const stages = ["SUBMITTED", "BRANCH_APPROVED", "FINANCE_REVIEW", "AUTHORIZED", "PAID"] as const;
  const memberStatuses = ["ACTIVE", "VERIFIED", "PENDING", "SUSPENDED"] as const;
  return {
    members: memberStatuses.map((status) => ({ status, count: visibleMembers.filter((member) => member.status === status).length })),
    disbursements: stages.map((stage) => ({ stage, count: visibleDisbursements.filter((item) => item.stage === stage).length, value: visibleDisbursements.filter((item) => item.stage === stage).reduce((total, item) => total + item.amount, 0) })),
    openReconciliation: user.roles.includes("AUDITOR") ? 8 : user.branch ? 3 : 11,
    pendingHandovers: user.roles.some((role) => ["SUPER_ADMIN", "SUB_ADMIN"].includes(role)) ? 2 : 0,
    auditEvents: [
      { id: 1, label: "Disbursement submitted for review", actor: "Adaeze Nwosu", time: "12 minutes ago", severity: "info" },
      { id: 2, label: "Reconciliation item flagged", actor: "System", time: "48 minutes ago", severity: "warning" },
      { id: 3, label: "Member status updated", actor: "Tunde Balogun", time: "2 hours ago", severity: "info" },
    ],
    branches: branches.map((branch) => ({ branch, members: members.filter((member) => member.branchId === branch.id).length, openValue: disbursements.filter((item) => item.branchId === branch.id && item.stage !== "PAID").reduce((total, item) => total + item.amount, 0), health: branch.id === 2 ? "Needs attention" : "On track" })),
  };
};

export const mockListMembers = async (user: User, search = ""): Promise<Member[]> => { await delay(); const term = search.trim().toLowerCase(); return visible(members, user).filter((item) => !term || `${item.name} ${item.memberNumber}`.toLowerCase().includes(term)); };
export const mockListDisbursements = async (user: User): Promise<Disbursement[]> => { await delay(); return visible(disbursements, user); };
export const mockListTreasury = async (user: User): Promise<TreasuryTransaction[]> => { await delay(); return user.branch ? treasury.filter((item) => item.disbursementId === undefined || disbursements.find((d) => d.id === item.disbursementId)?.branchId === user.branch?.id) : treasury; };
export const mockListPeriods = async (): Promise<MonthlyPeriod[]> => { await delay(); return periods; };
export const mockListImports = async (user: User): Promise<ImportBatch[]> => { await delay(); return visible(imports, user); };
export const mockAdminData = async (): Promise<AdminData> => { await delay(); return { branches: [...branches], users: users.map(toUser).map((user) => ({ ...user, isActive: true })), roles: [{ id: 1, name: "Super Admin", code: "SUPER_ADMIN", description: "Full agency administration" }, { id: 2, name: "Sub Admin", code: "SUB_ADMIN", description: "Operational administration" }, { id: 3, name: "Finance Officer", code: "FINANCE_OFFICER", description: "Finance and treasury workflows" }, { id: 4, name: "Branch Manager", code: "BRANCH_MANAGER", description: "Branch operations" }, { id: 5, name: "Auditor", code: "AUDITOR", description: "Read-only assurance" }], auditLogs: [{ id: 1, eventType: "CREATE", summary: "August import batch created", actorId: 2, createdAt: "12 minutes ago" }, { id: 2, eventType: "ASSIGNMENT", summary: "Lagos Central manager posted", actorId: 1, createdAt: "Yesterday" }] }; };
export const mockCreateBranch = async (data: Omit<Branch, "id">): Promise<Branch> => { await delay(); const branch = { ...data, id: branches.length + 1 }; branches.push(branch); return branch; };
export const mockCreateUser = async (data: { name: string; email: string; branchId: number | null; role: RoleCode }): Promise<AdminUser> => { await delay(); const branch = branches.find((item) => item.id === data.branchId) ?? null; const user: AdminUser = { id: users.length + 1, name: data.name, email: data.email, roles: [data.role], branch, isActive: true }; users.push({ ...user, password: "password" }); return user; };
export const mockCreatePeriod = async (data: Omit<MonthlyPeriod, "id" | "status">): Promise<MonthlyPeriod> => { await delay(); const period = { ...data, id: periods.length + 1, status: "OPEN" as const }; periods.unshift(period); return period; };
export const mockCreateImport = async (data: { filename: string; branchId: number }): Promise<ImportBatch> => { await delay(); const batch = { ...data, id: imports.length + 1, status: "UPLOADED" as const, rowsCount: 0, createdAt: "Just now" }; imports.unshift(batch); return batch; };

export const roleLabel = (role: RoleCode) => role.replaceAll("_", " ").toLowerCase().replace(/(^|\s)\S/g, (letter) => letter.toUpperCase());