# Branch Manager Transfers, Import Authorization Hardening & Project Audit

All work below targets the Laravel source in `backend-reference/` (frontend is still the placeholder index route).

## 1. Branch section: move the manager, never the branch's records

Current state (verified): `branches` has only `name/code/address/is_active` — no manager column. `users.branch_id` is a plain nullable column, and every operational record (`members`, `disbursement_requests`) carries its own `branch_id`. So a transfer is just an update of `users.branch_id`, but today there is no controlled way to do it, no history, and no guard that stops in-flight work from being orphaned.

Build an explicit, auditable posting workflow:

- New migration: `branch_manager_assignments` (branch_id, user_id, role_code, started_at, ended_at, ended_reason, assigned_by) plus `branches.current_manager_id` (nullable FK). Records stay attached to `branches.id` and are never rewritten.
- New `BranchAssignmentService` with `postManager(user, toBranch, actor)`:
  - closes the outgoing assignment row, opens the new one, updates `users.branch_id` and both branches' `current_manager_id`
  - explicitly touches **no** member / disbursement / treasury / import row
  - writes an audit log entry for each side of the move
- Endpoints (SUPER_ADMIN / SUB_ADMIN only): `POST /v1/branches/{branch}/manager` (post/replace), `DELETE /v1/branches/{branch}/manager`, `GET /v1/branches/{branch}/assignments`.
- Regression test asserting that after a transfer, the counts and `branch_id` values of members and disbursements at both branches are unchanged.

## 2. Handover / consent workflow

- New migration: `branch_handovers` (branch_id, outgoing_user_id, incoming_user_id, status, snapshot JSON, outgoing_signed_at, incoming_signed_at, approved_by, approved_at, notes) with a status flow: `DRAFT → PENDING_INCOMING → PENDING_APPROVAL → COMPLETED` (plus `DISPUTED`).
- On creation the service freezes a **handover snapshot**: member counts by status, open disbursements by stage, unposted import batches, open reconciliation items, latest closed period. This is the report both parties sign against.
- Endpoints: create handover, outgoing manager signs (consent + notes), incoming manager acknowledges (or raises `DISPUTED` with reason), admin approves and completes. The manager posting in step 1 can be configured to require a `COMPLETED` handover before `current_manager_id` flips, or to allow an admin override that is recorded in the audit log.
- `HandoverPolicy`: only the two named parties may sign their own side; only SUPER_ADMIN/SUB_ADMIN may approve; branch staff can read their own branch's handovers only.
- `GET /v1/branches/{branch}/handovers/{handover}/report` returns the signed report payload for printing/export.

## 3. Integration test for branch-scoped import row listings

New `tests/Feature/ImportBranchScopeTest.php` booting Laravel with the sqlite in-memory DB:

- two branches, each with an uploader + a branch manager, one import batch and rows per branch
- `GET /v1/imports/rows` as branch A staff returns only rows whose batch uploader belongs to branch A
- filtering by another branch's `import_batch_id` returns an empty set (not a leak)
- a user with no branch assignment sees nothing (fail-closed)
- SUPER_ADMIN / AUDITOR see both branches
- direct reads and mutations of another branch's batch/sheet/row return 403

## 4. Harden every import endpoint

Audit of the eleven import routes found these gaps:

- `ImportPolicy::post`, `verify`, `branchApprove`, `financeReview`, `markPaid`, `review` only check a role — no branch check. Add `sameBranchVia($user, $model, 'uploader')` to every one that receives a model.
- `ImportSheetSnapshotController::store` and `ImportRowController::store`/`skip` authorize through the parent batch, which is correct, but there is no route-level guard that the `{sheet}`/`{row}` actually belongs to the `{batch}` in the URL — add explicit ownership assertions so a mismatched nested id 404s instead of authorizing against the wrong parent.
- `ImportBatchController::store` has no branch validation: add branch binding of the uploader to the batch (`branch_id` denormalized on `import_batches`) so scoping no longer depends on the uploader's *current* branch — important once managers get transferred (step 1), otherwise moving a user silently re-homes their past uploads.
- Form requests (`StoreImportBatchRequest`, `StoreImportSheetRequest`, `StoreImportRowsRequest`) all `return true` from `authorize()`; move the branch/ownership checks into them as a second layer.
- Cross-branch access remains possible only for SUPER_ADMIN, SUB_ADMIN and AUDITOR via the existing `isCrossBranch()` gate; a new `app_settings` flag can grant a named finance user temporary cross-branch import review, recorded in the audit log.

## 5. Project audit and premium recommendations

Deliver `docs/PROJECT_AUDIT_AND_ROADMAP.md` covering:

- **What it does today:** auth (Sanctum), users/roles/branches, member lifecycle, 5-stage disbursement workflow, treasury posting, monthly period close/lock/reopen, reconciliation, workbook import → trial balance, audit log, idempotency middleware. Frontend: none yet — the app still renders the template placeholder page.
- **Gaps to agency-grade:** no frontend at all; `BranchController`/`UserController` return empty arrays (stubs); no pagination/filter contract tests; no rate limiting on `/login`; no MFA; no soft deletes or record-level versioning on financial rows; no exports (PDF/XLSX) of statutory reports; no notification layer (email/SMS on approval stages); no queue/worker usage for imports (large workbooks are processed inline); no OpenAPI spec generated from code; no observability beyond a logger.
- **Recommendations, prioritised:** finish the two stub controllers; add maker-checker on user/role changes; move import parsing to queued jobs with progress; add scheduled backups + point-in-time restore drill; add contract tests generated from the Postman collection; introduce a design system and build the React frontend in vertical slices (Auth → Members → Disbursements → Treasury → Reports); add role-aware dashboards; add accessibility and print stylesheets for statutory reports; publish an OpenAPI document and generate the frontend client from it.

## Technical notes

- Three new migrations (assignments, handovers, `import_batches.branch_id` + backfill), new models `BranchManagerAssignment` and `BranchHandover`, new services, policies, form requests, resources and controllers, and route entries under `v1/branches/*`.
- Backfill for `import_batches.branch_id` uses the uploader's branch at migration time.
- PHP cannot execute in this environment: I write the code, you run `composer install && php artisan migrate && php artisan test` locally to verify.
