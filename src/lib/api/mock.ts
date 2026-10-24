import { branchReviewError, validateBranchReview } from "../disbursement-workflow";
import { canCountRegister, emptyCount, expectedVaultCash, lockRegisterError, resolveVarianceError, totalPhysicalCash, validateVarianceResolution } from "../register-workflow";
import type { BranchReviewInput, AdminData, AdminUser, Branch, CashDenominations, DailyRegister, DashboardData, Disbursement, ImportBatch, LoginResponse, Member, MonthlyPeriod, RegisterStatus, ResolveVarianceInput, RoleCode, SaveRegisterCountInput, TreasuryTransaction, User, VarianceCategory } from "./types";

const branches: Branch[] = [
  { id: 3, name: "Head Office", code: "HQ", address: "Head Office" },
  { id: 1, name: "Lagos Central", code: "LAG-01", address: "12 Marina Road, Lagos" },
  { id: 2, name: "Ibadan North", code: "IBA-02", address: "4 Mokola Hill, Ibadan" },
  { id: 4, name: "Sango", code: "SGO", address: "Sango Ota" },
  { id: 5, name: "Itori", code: "ITR", address: "Itori" },
  { id: 6, name: "Ikotun", code: "IKT", address: "Ikotun" },
  { id: 7, name: "Owode", code: "OWD", address: "Owode" },
];

const users: Array<User & { password: string }> = [
  { id: 5, name: "Agency Administrator", email: "admin@lifterscenter.com", roles: ["SUPER_ADMIN"], branch: null, password: "password" },
  { id: 1, name: "Amaka Okafor", email: "admin@example.com", roles: ["SUPER_ADMIN"], branch: null, password: "password" },
  { id: 2, name: "Tunde Balogun", email: "finance@example.com", roles: ["FINANCE_OFFICER"], branch: null, password: "password" },
  { id: 3, name: "Adaeze Nwosu", email: "manager.lagos@example.com", roles: ["BRANCH_MANAGER"], branch: branches[1], password: "password" },
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
  { id: 1, reference: "DSB-2026-0041", memberName: "Grace Adebayo", amount: 850000, stage: "SUBMITTED", branchId: 1, submittedAt: "2026-10-18T09:42:00Z", memberId: 1, requestedBy: 2, requesterName: "Tunde Balogun", purpose: "School fees support", category: "Education", timeline: [{ stage: "SUBMITTED", at: "2026-10-18T09:42:00Z", actorId: 2, actorName: "Tunde Balogun" }] },
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
const enrichDisbursement = (item: Disbursement): Disbursement => {
  const member = members.find((m) => m.id === item.memberId || m.name === item.memberName);
  return { ...item, umid: member?.memberNumber, verificationStatus: member?.status, phone: member?.phone, requesterName: item.requesterName ?? users.find((u) => u.id === item.requestedBy)?.name, timeline: item.timeline?.map((stamp) => ({ ...stamp })) };
};
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
export const mockListDisbursements = async (user: User): Promise<Disbursement[]> => { await delay(); return visible(disbursements, user).map(enrichDisbursement); };
export const mockListTreasury = async (user: User): Promise<TreasuryTransaction[]> => { await delay(); return user.branch ? treasury.filter((item) => item.disbursementId === undefined || disbursements.find((d) => d.id === item.disbursementId)?.branchId === user.branch?.id) : treasury; };
export const mockListPeriods = async (): Promise<MonthlyPeriod[]> => { await delay(); return periods; };
export const mockListImports = async (user: User): Promise<ImportBatch[]> => { await delay(); return visible(imports, user); };
export const mockAdminData = async (): Promise<AdminData> => { await delay(); return { branches: [...branches], users: users.map(toUser).map((user) => ({ ...user, isActive: true })), roles: [{ id: 1, name: "Super Admin", code: "SUPER_ADMIN", description: "Full agency administration" }, { id: 2, name: "Sub Admin", code: "SUB_ADMIN", description: "Operational administration" }, { id: 3, name: "Finance Officer", code: "FINANCE_OFFICER", description: "Finance and treasury workflows" }, { id: 4, name: "Branch Manager", code: "BRANCH_MANAGER", description: "Branch operations" }, { id: 5, name: "Auditor", code: "AUDITOR", description: "Read-only assurance" }], auditLogs: [{ id: 1, eventType: "CREATE", summary: "August import batch created", actorId: 2, createdAt: "12 minutes ago" }, { id: 2, eventType: "ASSIGNMENT", summary: "Lagos Central manager posted", actorId: 1, createdAt: "Yesterday" }] }; };
export const mockCreateBranch = async (data: Omit<Branch, "id">): Promise<Branch> => { await delay(); const branch = { ...data, id: Math.max(...branches.map((b) => b.id)) + 1 }; branches.push(branch); return branch; };
export const mockCreateUser = async (data: { name: string; email: string; password: string; branchId: number | null; role: RoleCode }): Promise<AdminUser> => { await delay(); const branch = branches.find((item) => item.id === data.branchId) ?? null; const user: AdminUser = { id: Math.max(...users.map((u) => u.id)) + 1, name: data.name, email: data.email, roles: [data.role], branch, isActive: true }; users.push({ ...user, password: data.password }); return user; };
export const mockCreatePeriod = async (data: Omit<MonthlyPeriod, "id" | "status">): Promise<MonthlyPeriod> => { await delay(); const period = { ...data, id: periods.length + 1, status: "OPEN" as const }; periods.unshift(period); return period; };
export const mockCreateImport = async (data: { filename: string; branchId: number }): Promise<ImportBatch> => { await delay(); const batch = { ...data, id: imports.length + 1, status: "UPLOADED" as const, rowsCount: 0, createdAt: "Just now" }; imports.unshift(batch); return batch; };
export const mockSubmitDisbursement = async (id: number): Promise<Disbursement> => mockTransitionDisbursement(id, "DRAFT", "SUBMITTED");
export const mockTransitionDisbursement = async (id: number, from: Disbursement["stage"], to: Disbursement["stage"]): Promise<Disbursement> => { await delay(); const item = disbursements.find((candidate) => candidate.id === id); if (!item) throw new Error("Disbursement not found."); if (item.stage !== from) throw new Error(`This request is already ${item.stage.replaceAll("_", " ").toLowerCase()}.`); item.stage = to; const at = new Date().toISOString(); if (to === "SUBMITTED") item.submittedAt = at; item.timeline = [...(item.timeline ?? []), { stage: to, at, actorId: to === "SUBMITTED" ? item.requestedBy : undefined, actorName: to === "SUBMITTED" ? item.requesterName : undefined }]; return enrichDisbursement(item); };
export const mockCreateDisbursement = async (data: { memberId: number; amount: number; purpose: string }, actor: User): Promise<Disbursement> => {
  await delay();
  const member = members.find((item) => item.id === data.memberId);
  if (!member) throw new Error("Please choose a member.");
  if (actor.branch && actor.branch.id !== member.branchId) throw new Error("You may only create requests for members in your own branch.");
  if (!(data.amount >= 1)) throw new Error("Amount must be at least ₦1.");
  const id = Math.max(0, ...disbursements.map((item) => item.id)) + 1;
  const item: Disbursement = { id, reference: `DSB-2026-${String(41 + id).padStart(4, "0")}`, memberName: member.name, amount: data.amount, stage: "DRAFT", branchId: member.branchId, submittedAt: "", requestedBy: actor.id, requesterName: actor.name, memberId: member.id, purpose: data.purpose, timeline: [] };
  disbursements.unshift(item);
  return { ...item };
};

export const mockGetDisbursement = async (id: number, actor: User): Promise<Disbursement> => {
  await delay();
  const item = visible(disbursements, actor).find((candidate) => candidate.id === id);
  if (!item) throw new Error("Disbursement not found.");
  return enrichDisbursement(item);
};
export const mockReviewDisbursement = async (id: number, input: BranchReviewInput, actor: User): Promise<Disbursement> => {
  await delay();
  const item = disbursements.find((candidate) => candidate.id === id);
  if (!item) throw new Error("Disbursement not found.");
  const error = branchReviewError(actor, item) ?? validateBranchReview(input);
  if (error) throw new Error(error);
  item.stage = input.decision === "approve" ? "BRANCH_APPROVED" : "REJECTED";
  if (input.decision === "reject") { item.rejectionCategory = input.category; item.rejectedReason = input.reason?.trim(); }
  item.timeline = [...(item.timeline ?? []), { stage: item.stage, at: new Date().toISOString(), actorId: actor.id, actorName: actor.name, remarks: input.decision === "approve" ? input.remarks : item.rejectedReason }];
  return enrichDisbursement(item);
};

// ---- Daily branch cash register & till reconciliation (workbook columns M–Q) ----
const isoDate = (offsetDays = 0) => new Date(Date.now() + offsetDays * 86_400_000).toISOString().slice(0, 10);
const branchName = (branchId: number) => branches.find((branch) => branch.id === branchId)?.name ?? `Branch ${branchId}`;
const baseRegister = (id: number, branchId: number, registerDate: string, status: RegisterStatus, cashBookOpening: number, totalCollections: number, totalDisbursements: number, monitoringCash: number, overrides: Partial<DailyRegister> = {}): DailyRegister => {
  const expected = cashBookOpening + totalCollections - totalDisbursements - monitoringCash;
  return { id, branchId, branchName: branchName(branchId), registerDate, status, cashBookOpening, totalCollections, totalDisbursements, monitoringCash, expectedPhysicalCash: expected, physicalCashCounted: overrides.physicalCashCounted ?? 0, variance: overrides.variance ?? expected - (overrides.physicalCashCounted ?? 0), denominations: null, ...overrides };
};
const dailyRegisters: DailyRegister[] = [
  baseRegister(1, 4, isoDate(), "OPEN", 150_000, 220_000, 120_000, 30_000),
  baseRegister(2, 5, isoDate(), "OPEN", 90_000, 160_000, 80_000, 25_000),
  baseRegister(3, 6, isoDate(), "BALANCED", 250_000, 300_000, 150_000, 50_000, { physicalCashCounted: 350_000, denominations: { n1000: 340, n500: 0, n200: 50, n100: 0, n50: 0, n20: 0, n10: 0 } }),
  // Owode historical variance matches workbook cell Q12: -₦34,800 (cash overage).
  baseRegister(4, 7, isoDate(), "VARIANCE_FLAGGED", 200_000, 180_000, 90_000, 40_000, { physicalCashCounted: 284_800, variance: -34_800, denominations: { n1000: 248, n500: 70, n200: 8, n100: 2, n50: 0, n20: 0, n10: 0 } }),
  baseRegister(5, 6, isoDate(-1), "VARIANCE_APPROVED", 180_000, 140_000, 60_000, 45_000, { physicalCashCounted: 214_800, variance: 200, varianceCategory: "TRANSIT_TIMING_DELAY", varianceReason: "Field cash for the day reached the vault after the closing count.", varianceApprovedBy: "Adaeze Nwosu" }),
  baseRegister(6, 4, isoDate(-1), "LOCKED", 120_000, 210_000, 90_000, 30_000, { physicalCashCounted: 210_000, closedBy: "Adaeze Nwosu", closedAt: `${isoDate(-1)}T17:40:00Z`, lockedAt: `${isoDate(-1)}T17:45:00Z` }),
];

const registerDate = (item: DailyRegister) => item.registerDate;
export const mockGetDailyRegisters = async (user: User, branchId?: number, date?: string): Promise<DailyRegister[]> => {
  await delay();
  const branchVisible = user.branch ? dailyRegisters.filter((item) => item.branchId === user.branch?.id) : dailyRegisters;
  return branchVisible.filter((item) => (!branchId || item.branchId === branchId) && (!date || registerDate(item) === date)).map((item) => ({ ...item, denominations: item.denominations ? { ...item.denominations } : null }));
};
export const mockGetDailyRegister = async (id: number, user?: User): Promise<DailyRegister> => {
  await delay();
  const item = dailyRegisters.find((candidate) => candidate.id === id);
  if (!item || (user?.branch && item.branchId !== user.branch.id)) throw new Error("Daily register not found.");
  return { ...item, denominations: item.denominations ? { ...item.denominations } : null };
};
export const mockSaveRegisterCount = async (user: User, input: SaveRegisterCountInput): Promise<DailyRegister> => {
  await delay();
  const item = dailyRegisters.find((candidate) => candidate.id === input.registerId);
  if (!item) throw new Error("Daily register not found.");
  if (item.status === "LOCKED") throw new Error("This register is locked and can no longer be counted.");
  if (!canCountRegister(user, item)) throw new Error("Only this branch's manager, finance officer, or an administrator may record the till count.");
  const counted = totalPhysicalCash(input.denominations);
  const variance = expectedVaultCash(item) - counted;
  item.physicalCashCounted = counted;
  item.expectedPhysicalCash = expectedVaultCash(item);
  item.variance = variance;
  item.denominations = { ...emptyCount(), ...input.denominations };
  item.status = variance === 0 ? "BALANCED" : "VARIANCE_FLAGGED";
  return { ...item };
};
export const mockResolveVariance = async (user: User, input: ResolveVarianceInput): Promise<DailyRegister> => {
  await delay();
  const item = dailyRegisters.find((candidate) => candidate.id === input.registerId);
  if (!item) throw new Error("Daily register not found.");
  const error = resolveVarianceError(user, item) ?? validateVarianceResolution(input);
  if (error) throw new Error(error);
  item.varianceCategory = input.category as VarianceCategory;
  item.varianceReason = input.reason.trim();
  item.varianceApprovedBy = user.name;
  item.status = "VARIANCE_APPROVED";
  return { ...item };
};
export const mockLockRegister = async (user: User, id: number): Promise<DailyRegister> => {
  await delay();
  const item = dailyRegisters.find((candidate) => candidate.id === id);
  if (!item) throw new Error("Daily register not found.");
  const error = lockRegisterError(user, item);
  if (error) throw new Error(error);
  const now = new Date().toISOString();
  item.status = "LOCKED";
  item.closedBy = user.name;
  item.closedAt = now;
  item.lockedAt = now;
  return { ...item };
};

export const roleLabel = (role: RoleCode) => role.replaceAll("_", " ").toLowerCase().replace(/(^|\s)\S/g, (letter) => letter.toUpperCase());