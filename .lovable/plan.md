# Admin Panel, Operational Pages & Laravel/MCP Integration

## Goal

Turn the current auth/dashboard prototype into a usable agency operations console. The frontend will support mock mode for preview work and use the Laravel API when configured. `backend-reference/` is the Laravel source of truth and will receive the missing administration endpoints required by the UI.

## Scope

1. **Shared live API foundation**
   - Replace ad-hoc requests with one typed client that attaches the bearer token, normalizes Laravel `{data, message, errors}` responses, sends `Idempotency-Key` on mutations, and handles 401/logout consistently.
   - Keep `VITE_API_MODE=mock` as the default fallback and add a documented environment example for `VITE_API_MODE=live` and `VITE_API_BASE_URL`.
   - Add `/me` session hydration so a refreshed browser uses the server’s current user, roles, and branch rather than stale local data.

2. **Real operational pages**
   - Replace the broken “Next” navigation links with protected, role-aware routes and usable list/detail/action screens for:
     - Members: search/list, create/edit, detail, and permitted lifecycle actions.
     - Disbursements: filtered queue, detail/approval history, create, submit, branch approval, finance review, authorization, and mark-paid actions according to backend policy and stage.
     - Treasury: transaction list/detail and links back to the originating disbursement/period.
     - Periods & reconciliation: period list/detail, run reconciliation, review/close/lock/reopen actions, reconciliation item resolution and override.
     - Imports: batch list/detail, sheet and row review, map/validate/post/skip workflow, status filters, and branch-safe empty/error states.
   - Use TanStack Query loaders plus suspense queries, pagination, consistent loading/empty/error states, and route-level role guards rather than relying only on hidden navigation links.

3. **Administration and first-run setup**
   - Build an admin workspace with tabs for branches, users, roles, manager postings/handovers, audit activity, and import cross-branch reviewers.
   - Add Laravel endpoints and policies for real branch creation/update, user list/create/update with branch and role assignment, canonical role listing/management, and audit-log reads. Keep the five existing policy-backed role codes as the authoritative roles; arbitrary custom permission roles are not introduced because current authorization is enum/code based.
   - Add a secure, one-time first-run initialization flow that creates the first branch and initial super administrator inside a transaction, guarded by a server-side setup secret and disabled after initialization. Keep the existing CLI seeders as the deployment fallback; never expose seed passwords in the browser.
   - Wire the existing branch manager posting and handover endpoints without moving branch-owned portfolios. Show the frozen handover report, consent status, approval state, and the super-admin-only override path.

4. **Backend contract hardening**
   - Add request validation, service transactions, resources, policy checks, and feature tests for the new admin endpoints.
   - Implement the filters advertised by the MCP/member/disbursement contracts, or remove unsupported filters; do not silently accept no-op query parameters.
   - Preserve branch isolation and maker-checker rules: authorization decisions come from Laravel policies, not client-provided branch/user fields. Review the current super-admin-only abilities before exposing action buttons.

5. **MCP production wiring**
   - Keep `list_members`, `get_member`, and `submit_disbursement` as real Laravel proxies using the caller’s OAuth bearer token.
   - Verify the production Laravel URL and OAuth issuer are configured, update the stale MCP authorization documentation, and add contract checks for 401/403/422 propagation, branch scoping, self-approval rejection, and the supported filters.

## Implementation order

```text
API client + auth refresh/error handling
        ↓
Members → Disbursements → Treasury → Periods/Reconciliation → Imports
        ↓
Laravel admin endpoints + secure initialization
        ↓
Administration UI + manager handovers/audit views
        ↓
MCP contract checks + full verification
```

## Verification

- Confirm all six navigation destinations resolve through generated TanStack routes and direct URL access is role-protected.
- Run frontend build/lint and exercise mock-mode login, navigation, list/detail views, mutations, and sign-out in the preview.
- Run Laravel migrations and feature/unit tests locally against SQLite, including new branch/user/role/setup tests and existing branch-scope/import/self-approval tests.
- With live environment variables configured, verify representative API responses and mutation errors from the browser and MCP tools.

## Not included

Custom permission-builder roles, MFA, notifications, queued workbook processing, statutory exports, and a full OpenAPI generator remain separate follow-up work; they are not required to deliver this admin panel and operational console.