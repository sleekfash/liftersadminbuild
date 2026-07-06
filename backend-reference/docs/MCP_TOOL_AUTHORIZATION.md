# MCP Tool Authorization Contract

The MCP server in this project (`src/lib/mcp/`) is a thin bridge. It MUST NOT
re-implement business rules. Every MCP tool that touches Laravel resources
must call the backend API — never the DB — so the same policies enforced by
Sanctum/HTTP callers apply identically.

## Rules for every new MCP tool

1. Tool handler receives the caller's Supabase/OAuth token via `ToolContext`.
   Forward it to Laravel as `Authorization: Bearer <token>` — never a
   service-role or admin token.
2. Never accept `branch_id`, `requested_by`, or `user_id` from tool input for
   authorization decisions. Laravel derives these from the authenticated user
   and its `FormRequest::withValidator()` + Policy layer.
3. Read/write operations that map to a policy method must hit the matching
   HTTP endpoint so `authorize()` runs:
   - Members list/create/view/update → `MemberPolicy`
   - Disbursements create/submit/approve/authorize/pay → `DisbursementRequestPolicy`
   - Treasury/reconciliation/import → their respective policies
4. Surface `403` and `422` responses back to the caller verbatim — do not
   swallow authorization errors.

## Current status

- `echo` — connectivity check, no data access, no auth required.
- No other tools are currently wired. When adding them, follow the rules
  above so branch-scope and self-approval invariants are preserved by the
  Laravel policy layer that the unit + feature tests already lock down.

## Verified invariants (see `tests/Unit/`)

- `BranchScopeAuthorizationTest` — cross-branch view/update blocked for
  BRANCH_MANAGER and FINANCE_OFFICER; SUPER_ADMIN and AUDITOR bypass.
- `BranchScopeAuthorizationTest::test_branch_manager_cannot_self_approve_own_submission`
  — BRANCH_MANAGER cannot `branchApprove` their own request.
- `ApprovalServiceSelfApprovalTest` — the service-layer self-approval check
  is present and the branch-scope FormRequests are enforced.
