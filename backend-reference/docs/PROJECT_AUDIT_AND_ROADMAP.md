# Project Audit & Premium Roadmap

## 1. What the system does today

**Backend (Laravel, `backend-reference/`)**
- Sanctum token auth (`/api/v1/login`, `/me`, `/logout`), roles: SUPER_ADMIN, SUB_ADMIN, FINANCE_OFFICER, BRANCH_MANAGER, AUDITOR.
- Branches: list/show, **manager postings with full history**, **handover/consent workflow** (new).
- Members: CRUD plus lifecycle (verify → activate → suspend → reactivate → terminate) with status history.
- Disbursements: submit → branch approve → finance review → authorize → mark paid, with self-approval blocked and per-stage approval records.
- Treasury: postings and transaction listing.
- Periods: create, review, close, lock, reopen with reconciliation blockers.
- Reconciliation: runs, items, under-review/resolve/override.
- Workbook imports: batch → sheet snapshot → rows → map → validate → post to trial balance.
- Cross-cutting: audit log, idempotency middleware, branch isolation traits (`ScopesByBranch` for policies, `ScopesQueryByBranch` for queries).

**Frontend (TanStack Start, `src/`)**
- Still the template placeholder page. The only real surface is the MCP server (`/mcp`) exposing 6 tools that proxy the Laravel API with the caller's bearer token.

## 2. Gaps between "works" and "agency-grade"

| Area | Gap |
| --- | --- |
| Frontend | No application UI at all; no design system, no role-aware dashboards |
| API surface | `UserController` still returns an empty array; no user create/update/role assignment endpoints |
| Contracts | No OpenAPI spec generated from code; Postman collection is hand-maintained and can drift |
| Security | No rate limiting on `/login`; no MFA for SUPER_ADMIN; no password policy; no session/device revocation UI |
| Data integrity | No soft deletes or row versioning on financial tables; migrations have empty `down()` |
| Performance | Workbook parsing/mapping/validation run inline in the request; large files will time out |
| Reporting | No PDF/XLSX exports of statutory reports or the new handover report |
| Notifications | No email/SMS on approval stages, postings or handover signatures |
| Testing | Several feature tests are placeholders; no contract tests, no CI database run |
| Observability | Logger only — no error tracking, no request tracing, no uptime/alerting |

## 3. Recommendations, in priority order

**P0 — correctness and trust**
1. Finish `UserController` (index/store/update, role assignment via maker-checker) and cover it with policy tests.
2. Turn the placeholder feature tests into real coverage; run `php artisan test` in CI against sqlite.
3. Rate limit `/login` (`throttle:5,1`) and add MFA for SUPER_ADMIN and FINANCE_OFFICER.
4. Add `down()` migrations or an explicit, documented forward-only policy.

**P1 — scale and operations**
5. Move import parsing/mapping/validation into queued jobs with progress polling; keep the HTTP endpoints as job dispatchers.
6. Publish an OpenAPI document generated from the routes/resources, and generate the frontend API client from it.
7. Scheduled backups plus a rehearsed restore drill (the deployment pack has the checklist; the drill is what makes it real).
8. Error tracking + structured request logs with a correlation id echoed to the client.

**P2 — the premium layer**
9. Build the React frontend in vertical slices: Auth → Members → Disbursements → Treasury → Periods/Reconciliation → Imports → Admin (branches, postings, handovers).
10. Role-aware dashboards: branch manager sees their branch queue; finance sees the payment pipeline; auditor sees exceptions and the audit trail.
11. Statutory exports (PDF/XLSX) with print stylesheets — including the signed handover report.
12. Notification layer on every stage transition, posting and handover signature.
13. Accessibility (WCAG AA), keyboard-first tables, empty/loading/error states everywhere, and a single documented design system rather than ad-hoc styling.

## 4. Branch portfolio rule (now enforced in code)

A branch manager posting changes **the officer's assignment only**. `branch_manager_assignments` records the history, `branches.current_manager_id` points at the incumbent, and no member, disbursement, treasury transaction, reconciliation run or import batch is ever re-keyed. Import batches now carry their own `branch_id`, stamped at upload time, so a transferred officer cannot drag historical uploads into their new branch — and cannot read their old branch's rows afterwards.

Cross-branch reads remain available to SUPER_ADMIN, SUB_ADMIN and AUDITOR only, plus any finance officer a super admin explicitly lists in the `imports.cross_branch_reviewers` app setting (audited).
