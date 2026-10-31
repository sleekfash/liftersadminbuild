import { acknowledgeError, approveError, disputeError, signNotesError, transferBlockError } from "../handover-workflow";
import { mockAcknowledgeHandover, mockApproveHandover, mockDisputeHandover, mockGetHandover, mockListHandovers, mockListManagerCandidates, mockOpenHandover, mockPostBranchManager, mockSignOutgoingHandover, mockGetDailyRegister, mockGetDisbursement, mockLockRegister, mockResolveVariance, mockReviewDisbursement, mockAdminData, mockCreateBranch, mockCreateDisbursement, mockCreateImport, mockCreatePeriod, mockCreateUser, mockDashboard, mockGetDailyRegisters as mockListDailyRegisters, mockListDisbursements, mockListImports, mockListMembers, mockListPeriods, mockListTreasury, mockLogin, mockSaveRegisterCount, mockSubmitDisbursement, mockTransitionDisbursement } from "./mock";
import { branchReviewError, validateBranchReview, transitions } from "../disbursement-workflow";
import { canResolveVariance, lockRegisterError, resolveVarianceError, validateVarianceResolution } from "../register-workflow";
import type { BranchReviewInput, ReviewStamp, AdminData, AdminUser, Branch, BranchHandover, BranchManagerAssignment, CashDenominations, DailyRegister, DashboardData, Disbursement, DisbursementAction, HandoverSnapshot, ImportBatch, LoginResponse, Member, MonthlyPeriod, OpenHandoverInput, PostBranchManagerInput, RegisterStatus, ResolveVarianceInput, RoleCode, SaveRegisterCountInput, TreasuryTransaction, User } from "./types";

const TOKEN_KEY = "lifters-touch-token";
const USER_KEY = "lifters-touch-user";
const isMock = import.meta.env.VITE_API_MODE !== "live";
const apiBase = () => String(import.meta.env.VITE_API_BASE_URL ?? "").replace(/\/+$/, "");
const label = (value: unknown) => String(value ?? "").replaceAll("_", " ").toLowerCase().replace(/(^|\s)\S/g, (letter) => letter.toUpperCase());
const normaliseUser = (raw: Record<string, unknown>): User => ({ id: Number(raw.id), name: String(raw.name ?? ""), email: String(raw.email ?? ""), roles: ((raw.roles as Array<Record<string, unknown>> | string[] | undefined) ?? []).map((role) => typeof role === "string" ? role as RoleCode : String(role.code) as RoleCode), branch: raw.branch ? { id: Number((raw.branch as Record<string, unknown>).id), name: String((raw.branch as Record<string, unknown>).name), code: String((raw.branch as Record<string, unknown>).code ?? ""), address: String((raw.branch as Record<string, unknown>).address ?? "") } : raw.branch_id ? { id: Number(raw.branch_id), name: "Assigned branch", code: "", address: "" } : null });
const unwrap = <T>(payload: unknown): T => { if (payload && typeof payload === "object" && "data" in payload) return (payload as { data: T }).data; return payload as T; };
const unwrapList = (payload: unknown): Array<Record<string, unknown>> => {
  const value = unwrap<unknown>(payload);
  if (Array.isArray(value)) return value as Array<Record<string, unknown>>;
  if (value && typeof value === "object" && "data" in value) {
    const nested = (value as { data: unknown }).data;
    return Array.isArray(nested) ? nested as Array<Record<string, unknown>> : [];
  }
  return [];
};
async function request<T>(method: "GET" | "POST" | "PATCH", path: string, body?: unknown, query?: Record<string, string | number | undefined>): Promise<T> { const token = session.getToken(); if (!token) throw new Error("Your session has expired. Please sign in again."); const search = query ? `?${Object.entries(query).filter(([, value]) => value !== undefined && value !== "").map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`).join("&")}` : ""; const headers: Record<string, string> = { Accept: "application/json", Authorization: `Bearer ${token}` }; if (body !== undefined) headers["Content-Type"] = "application/json"; if (method !== "GET") headers["Idempotency-Key"] = typeof crypto !== "undefined" && "randomUUID" in crypto ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`; const response = await fetch(`${apiBase()}/api/v1${path}${search}`, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) }); const payload = await response.json().catch(() => ({})); if (response.status === 401) { session.clear(); throw new Error("Your session has expired. Please sign in again."); } if (!response.ok) { const details = payload && typeof payload === "object" && "errors" in payload ? JSON.stringify(payload.errors) : ""; throw new Error(`${payload?.message ?? "Request failed."}${details ? ` ${details}` : ""}`); } return unwrap<T>(payload); }

export const session = {
  getToken: () => (typeof window === "undefined" ? null : window.localStorage.getItem(TOKEN_KEY)),
  getUser: (): User | null => {
    if (typeof window === "undefined") return null;
      const raw = window.localStorage.getItem(USER_KEY); try { return raw ? (JSON.parse(raw) as User) : null; } catch { return null; }
  },
  set: ({ token, user }: LoginResponse) => {
    if (typeof window !== "undefined") {
      window.localStorage.setItem(TOKEN_KEY, token);
      window.localStorage.setItem(USER_KEY, JSON.stringify(user));
    }
  },
  clear: () => {
    if (typeof window !== "undefined") {
      window.localStorage.removeItem(TOKEN_KEY);
      window.localStorage.removeItem(USER_KEY);
    }
  },
};

export async function login(email: string, password: string) {
  if (isMock) return mockLogin(email, password);
  if (!apiBase()) throw new Error("Live sign-in is not configured yet. Add the deployed Laravel API address and reload.");
  const response = await fetch(`${apiBase()}/api/v1/login`, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify({ email, password }) }); const payload = await response.json();
  if (!response.ok) throw new Error(payload.message ?? "Unable to sign in.");
  return { token: payload.token, user: normaliseUser(payload.data) };
}

export async function dashboard(user: User): Promise<DashboardData> {
  if (isMock) return mockDashboard(user);
  const [memberList, disbursementList, branchList] = await Promise.all([listMembers(user), listDisbursements(user), listBranches()]); const members = ["ACTIVE", "VERIFIED", "PENDING", "SUSPENDED"].map((status) => ({ status: status as DashboardData["members"][number]["status"], count: memberList.filter((item) => item.status === status).length })); const disbursements = ["SUBMITTED", "BRANCH_APPROVED", "FINANCE_REVIEW", "AUTHORIZED", "PAID"].map((stage) => ({ stage: stage as DashboardData["disbursements"][number]["stage"], count: disbursementList.filter((item) => item.stage === stage).length, value: disbursementList.filter((item) => item.stage === stage).reduce((sum, item) => sum + item.amount, 0) })); return { members, disbursements, openReconciliation: 0, pendingHandovers: 0, auditEvents: [], branches: branchList.map((branch) => ({ branch, members: memberList.filter((item) => item.branchId === branch.id).length, openValue: disbursementList.filter((item) => item.branchId === branch.id && item.stage !== "PAID").reduce((sum, item) => sum + item.amount, 0), health: "On track" as const })) };
}

const mapMember = (raw: Record<string, unknown>): Member => ({ id: Number(raw.id), name: String(raw.name ?? `${raw.first_name ?? ""} ${raw.last_name ?? ""}`).trim(), memberNumber: String(raw.memberNumber ?? raw.umid ?? ""), status: String(raw.status ?? "PENDING").toUpperCase() as Member["status"], branchId: Number(raw.branchId ?? raw.branch_id ?? 0) });
export const mapDisbursement = (raw: Record<string, unknown>): Disbursement => {
  const member = raw.member as Record<string, unknown> | undefined;
  const requester = raw.requester as Record<string, unknown> | undefined;
  const history = (raw.status_histories ?? raw.statusHistories ?? []) as Array<Record<string, unknown>>;
  const normalStage = (stage: unknown) => String(stage ?? "DRAFT").toUpperCase().replace("FINANCE_REVIEWED", "FINANCE_REVIEW") as Disbursement["stage"];
  const timeline: ReviewStamp[] = history.filter((stamp) => stamp.to_stage !== "DRAFT").map((stamp) => ({ stage: normalStage(stamp.to_stage), at: String(stamp.created_at ?? ""), actorId: Number(stamp.changed_by) || undefined, actorName: String((stamp.actor as Record<string, unknown> | undefined)?.name ?? ""), remarks: String(stamp.reason ?? "") }));
  const submitted = timeline.find((stamp) => stamp.stage === "SUBMITTED");
  return { id: Number(raw.id), reference: String(raw.reference ?? raw.id ?? ""), memberId: Number(raw.member_id ?? raw.memberId) || undefined, memberName: String(member?.name ?? (member ? `${member.first_name ?? ""} ${member.last_name ?? ""}`.trim() : raw.memberName ?? "Member")), amount: Number(raw.amount ?? 0), stage: normalStage(raw.stage), branchId: Number(raw.branchId ?? raw.branch_id ?? 0), submittedAt: String(raw.submittedAt ?? raw.submitted_at ?? submitted?.at ?? ""), requestedBy: Number(raw.requested_by ?? raw.requestedBy ?? 0) || undefined, requesterName: requester?.name ? String(requester.name) : undefined, purpose: raw.purpose ? String(raw.purpose) : undefined, category: raw.category ? String(raw.category) : undefined, umid: member?.umid ? String(member.umid) : undefined, phone: member?.phone ? String(member.phone) : undefined, verificationStatus: member?.status as Member["status"] | undefined, rejectionCategory: raw.rejection_category as Disbursement["rejectionCategory"], rejectedReason: raw.rejected_reason ? String(raw.rejected_reason) : undefined, timeline };
};
export async function me(): Promise<User> { if (isMock) return session.getUser() ?? (await mockLogin("admin@example.com", "password")).user; return normaliseUser(await request<Record<string, unknown>>("GET", "/me")); }
export async function listBranches(): Promise<Branch[]> { if (isMock) return (await mockAdminData()).branches; return unwrapList(await request<unknown>("GET", "/branches")).map((item) => ({ id: Number(item.id), name: String(item.name), code: String(item.code), address: String(item.address ?? "") })); }
export async function createBranch(data: Omit<Branch, "id">): Promise<Branch> { if (isMock) return mockCreateBranch(data); const item = await request<Record<string, unknown>>("POST", "/branches", data); return { id: Number(item.id), name: String(item.name), code: String(item.code), address: String(item.address ?? "") }; }
export async function listMembers(user: User, search = ""): Promise<Member[]> { if (isMock) return mockListMembers(user, search); return unwrapList(await request<unknown>("GET", "/members", undefined, { search })).map(mapMember); }
export async function createMember(data: Record<string, unknown>): Promise<Member> { const item = isMock ? data : await request<Record<string, unknown>>("POST", "/members", data); return mapMember(item as Record<string, unknown>); }
export async function listDisbursements(user: User): Promise<Disbursement[]> { if (isMock) return mockListDisbursements(user); return unwrapList(await request<unknown>("GET", "/disbursements")).map(mapDisbursement); }
export async function createDisbursement(data: { memberId: number; branchId: number; amount: number; purpose: string }, actor: User): Promise<Disbursement> { if (isMock) return mockCreateDisbursement(data, actor); return mapDisbursement(await request<Record<string, unknown>>("POST", "/disbursements", { member_id: data.memberId, branch_id: data.branchId, amount: data.amount, purpose: data.purpose || null })); }
export async function submitDisbursement(id: number): Promise<Disbursement> { if (isMock) return mockSubmitDisbursement(id); return mapDisbursement(await request<Record<string, unknown>>("POST", `/disbursements/${id}/submit`, {})); }
const actionPaths: Record<DisbursementAction, string> = { submit: "submit", branchApprove: "approve/branch", financeReview: "approve/finance", authorize: "authorize", markPaid: "mark-paid" };
export async function advanceDisbursement(id: number, action: DisbursementAction, remarks = ""): Promise<Disbursement> { if (action === "branchApprove") return reviewDisbursement(id, { decision: "approve", remarks }); if (isMock) { const step = transitions[action]; return mockTransitionDisbursement(id, step.from, step.to); } return mapDisbursement(await request<Record<string, unknown>>("POST", `/disbursements/${id}/${actionPaths[action]}`, remarks ? { remarks } : {})); }
export async function listTreasury(user: User): Promise<TreasuryTransaction[]> { if (isMock) return mockListTreasury(user); return unwrapList(await request<unknown>("GET", "/treasury/transactions")).map((item) => ({ id: Number(item.id), type: String(item.type ?? ""), amount: Number(item.amount ?? 0), balanceAfter: Number(item.balance_after ?? item.balanceAfter ?? 0), reference: String(item.reference ?? item.id), disbursementId: Number(item.disbursement_request_id ?? 0) || undefined, periodId: Number(item.monthly_period_id ?? 0) || undefined, occurredOn: String(item.occurred_on ?? "") })); }
export async function listPeriods(): Promise<MonthlyPeriod[]> { if (isMock) return mockListPeriods(); return unwrapList(await request<unknown>("GET", "/periods")).map((item) => ({ id: Number(item.id), name: String(item.name), month: Number(item.month), year: Number(item.year), status: String(item.status).toUpperCase() as MonthlyPeriod["status"], openingBalance: Number(item.opening_balance ?? 0), closingBalance: Number(item.closing_balance ?? 0) || undefined })); }
export async function createPeriod(data: Omit<MonthlyPeriod, "id" | "status">): Promise<MonthlyPeriod> { if (isMock) return mockCreatePeriod(data); return { ...(await request<Record<string, unknown>>("POST", "/periods", data) as unknown as MonthlyPeriod) }; }
export async function listImports(user: User): Promise<ImportBatch[]> { if (isMock) return mockListImports(user); return unwrapList(await request<unknown>("GET", "/imports/batches")).map((item) => ({ id: Number(item.id), filename: String(item.filename), status: String(item.status).toUpperCase() as ImportBatch["status"], branchId: Number(item.branch_id), rowsCount: Number(item.rows_count ?? item.rowsCount ?? 0), createdAt: String(item.created_at ?? "") })); }
export async function createImport(data: { filename: string; branchId: number }): Promise<ImportBatch> { if (isMock) return mockCreateImport(data); const item = await request<Record<string, unknown>>("POST", "/imports/batches", { filename: data.filename, branch_id: data.branchId }); return { id: Number(item.id), filename: String(item.filename), status: String(item.status).toUpperCase() as ImportBatch["status"], branchId: Number(item.branch_id), rowsCount: 0 }; }
export async function adminData(): Promise<AdminData> { if (isMock) return mockAdminData(); const [branches, users, roles, auditLogs] = await Promise.all([listBranches(), request<unknown>("GET", "/users"), request<unknown>("GET", "/roles"), request<unknown>("GET", "/audit-logs")]); return { branches, users: unwrapList(users).map((item) => ({ ...normaliseUser(item), isActive: Boolean(item.is_active ?? true), createdAt: String(item.created_at ?? "") })), roles: unwrapList(roles).map((item) => ({ id: Number(item.id), name: String(item.name), code: String(item.code) as RoleCode, description: String(item.description ?? "") })), auditLogs: unwrapList(auditLogs).map((item) => ({ id: Number(item.id), eventType: String(item.event_type ?? ""), summary: String(item.summary ?? ""), actorId: Number(item.actor_id ?? 0) || undefined, createdAt: String(item.created_at ?? "") })) }; }
export async function createUser(data: { name: string; email: string; password: string; branchId: number | null; role: RoleCode }): Promise<AdminUser> { if (isMock) return mockCreateUser(data); const item = await request<Record<string, unknown>>("POST", "/users", { ...data, branch_id: data.branchId, roles: [data.role] }); return { ...normaliseUser(item), isActive: Boolean(item.is_active ?? true) }; }
export async function createRole(data: { code: RoleCode; name: string; description: string }) { if (isMock) return data; return request("POST", "/roles", data); }
export async function getDisbursement(id: number): Promise<Disbursement> {
  const actor = session.getUser();
  if (!actor) throw new Error("Please sign in again.");
  return isMock ? mockGetDisbursement(id, actor) : mapDisbursement(await request<Record<string, unknown>>("GET", `/disbursements/${id}`));
}
export async function reviewDisbursement(id: number, input: BranchReviewInput): Promise<Disbursement> {
  const actor = session.getUser();
  if (!actor) throw new Error("Please sign in again.");
  const item = await getDisbursement(id);
  const error = branchReviewError(actor, item) ?? validateBranchReview(input);
  if (error) throw new Error(error);
  if (isMock) return mockReviewDisbursement(id, input, actor);
  return mapDisbursement(await request<Record<string, unknown>>("POST", `/disbursements/${id}/${input.decision === "approve" ? "approve/branch" : "reject/branch"}`, input.decision === "approve" ? { remarks: input.remarks ?? "" } : { category: input.category, reason: input.reason?.trim() }));
}

// ---- Daily branch cash register & till reconciliation ----
const mapRegister = (raw: Record<string, unknown>): DailyRegister => ({
  id: Number(raw.id),
  branchId: Number(raw.branchId ?? raw.branch_id ?? 0),
  branchName: String(raw.branchName ?? (raw.branch as Record<string, unknown> | undefined)?.name ?? ""),
  registerDate: String(raw.registerDate ?? raw.register_date ?? ""),
  status: String(raw.status ?? "OPEN").toUpperCase() as RegisterStatus,
  cashBookOpening: Number(raw.cashBookOpening ?? raw.cash_book_opening ?? 0),
  totalCollections: Number(raw.totalCollections ?? raw.total_collections ?? 0),
  totalDisbursements: Number(raw.totalDisbursements ?? raw.total_disbursements ?? 0),
  monitoringCash: Number(raw.monitoringCash ?? raw.monitoring_cash ?? 0),
  expectedPhysicalCash: Number(raw.expectedPhysicalCash ?? raw.expected_physical_cash ?? 0),
  physicalCashCounted: Number(raw.physicalCashCounted ?? raw.physical_cash_counted ?? 0),
  variance: Number(raw.variance ?? 0),
  denominations: (raw.denominations as CashDenominations | null | undefined) ?? null,
  varianceCategory: (raw.varianceCategory ?? raw.variance_category) as DailyRegister["varianceCategory"],
  varianceReason: raw.varianceReason ? String(raw.varianceReason) : raw.variance_reason ? String(raw.variance_reason) : null,
  varianceApprovedBy: raw.varianceApprovedBy ? String(raw.varianceApprovedBy) : raw.variance_approved_by ? String(raw.variance_approved_by) : null,
  closedBy: raw.closedBy ? String(raw.closedBy) : raw.closed_by ? String(raw.closed_by) : null,
  closedAt: raw.closedAt ? String(raw.closedAt) : raw.closed_at ? String(raw.closed_at) : null,
  lockedAt: raw.lockedAt ? String(raw.lockedAt) : raw.locked_at ? String(raw.locked_at) : null,
});

export async function listDailyRegisters(user: User, branchId?: number, date?: string): Promise<DailyRegister[]> {
  if (isMock) return mockListDailyRegisters(user, branchId, date);
  return unwrapList(await request<unknown>("GET", "/registers", undefined, { branch_id: branchId, date })).map(mapRegister);
}
export async function getDailyRegister(id: number): Promise<DailyRegister> {
  const actor = session.getUser();
  if (!actor) throw new Error("Please sign in again.");
  if (isMock) return mockGetDailyRegister(id, actor);
  return mapRegister(await request<Record<string, unknown>>("GET", `/registers/${id}`));
}
export async function saveRegisterCount(input: SaveRegisterCountInput): Promise<DailyRegister> {
  const actor = session.getUser();
  if (!actor) throw new Error("Please sign in again.");
  if (isMock) return mockSaveRegisterCount(actor, input);
  return mapRegister(await request<Record<string, unknown>>("POST", `/registers/${input.registerId}/count`, { denominations: input.denominations }));
}
export async function resolveVariance(input: ResolveVarianceInput): Promise<DailyRegister> {
  const actor = session.getUser();
  if (!actor) throw new Error("Please sign in again.");
  const register = await getDailyRegister(input.registerId);
  const error = (canResolveVariance(actor, register) ? null : "A branch manager or administrator of this branch must authorise the variance.") ?? resolveVarianceError(actor, register) ?? validateVarianceResolution(input);
  if (error) throw new Error(error);
  if (isMock) return mockResolveVariance(actor, input);
  return mapRegister(await request<Record<string, unknown>>("POST", `/registers/${input.registerId}/variance/resolve`, { category: input.category, reason: input.reason.trim() }));
}
export async function lockRegister(id: number): Promise<DailyRegister> {
  const actor = session.getUser();
  if (!actor) throw new Error("Please sign in again.");
  const register = await getDailyRegister(id);
  const error = lockRegisterError(actor, register);
  if (error) throw new Error(error);
  if (isMock) return mockLockRegister(actor, id);
  return mapRegister(await request<Record<string, unknown>>("POST", `/registers/${id}/lock`, {}));
}
