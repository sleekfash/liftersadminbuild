# Frontend Slice 1 — Auth, App Shell & Dashboard

The Laravel backend in `backend-reference/` is feature-complete (auth, members, disbursements, treasury, periods, reconciliation, imports, branch postings/handovers). The React app is still the blank template placeholder — that is the only real gap. This slice builds the foundation every later screen plugs into, running against a mock data layer that flips to your live API with one environment variable.

## What you get

- A **sign-in page** matching the Laravel contract (`POST /v1/login` → token, `/v1/me` → user + roles + branch).
- A **protected app shell**: sidebar navigation filtered by role, top bar with the signed-in officer, their branch, and sign-out.
- A **role-aware dashboard** at `/dashboard`:
  - Branch Manager: their branch's member counts by status, disbursements awaiting their approval, open reconciliation items.
  - Finance Officer: the payment pipeline by stage and value.
  - Super/Sub Admin: all branches, manager postings and pending handovers.
  - Auditor: recent audit events and exceptions.
- Nav entries for Members, Disbursements, Treasury, Periods, Imports and Admin rendered as "coming next" placeholders, so the shell is complete and later slices only fill panels.

## Demo credentials (mock mode)

Sign in with any of the seeded mock officers, password `password`:
`admin@example.com` (super admin), `finance@example.com`, `manager.lagos@example.com`, `auditor@example.com` — each shows a different dashboard and navigation set.

## Design direction

Serious financial-institution look, not a generic SaaS template: deep ink/forest primary with a restrained gold accent, dense data-first tables, clear stage badges, generous but efficient spacing, and a system serif/grotesk pairing. All colours as semantic tokens in `src/styles.css` so a later brand handoff is a token change, not a rewrite.

## Technical notes

- `src/lib/api/client.ts` — typed fetch wrapper: base URL from `VITE_API_BASE_URL`, bearer token from an in-memory + localStorage store, the standard Laravel error envelope mapped to typed errors, and an `Idempotency-Key` header on mutations.
- `src/lib/api/mock/` — a mock transport implementing the same endpoint surface, seeded with two branches, users per role, members and disbursements. Chosen when `VITE_API_MODE=mock` (default until you supply a URL); no mock code is reachable in live mode.
- `src/lib/api/types.ts` — types derived from `backend-reference/frontend-contract/types.ts` and the Laravel API resources, so the mock cannot drift from the real contract.
- Auth context on the router (`createRootRouteWithContext`), `src/routes/_authenticated.tsx` gate with `beforeLoad` redirect to `/login?redirect=...`, plus `hasRole`/`hasAnyRole` helpers used for nav and dashboard branching.
- Routes: `src/routes/index.tsx` becomes a session-aware landing that sends signed-in users to `/dashboard`; `login.tsx`; `_authenticated/dashboard.tsx`; placeholder leaves for the remaining sections.
- Data reads via TanStack Query (`ensureQueryData` in loaders + `useSuspenseQuery`), so switching mock → live changes nothing in the components.
- Per-route `head()` metadata; the app is not publicly indexable beyond the landing page.

## Going live later

Set `VITE_API_MODE=live` and `VITE_API_BASE_URL=https://your-api/api`, and enable CORS on Laravel for the preview and published domains. No component changes required.

## Not in this slice

Members CRUD, the disbursement approval workflow, treasury, periods/reconciliation and imports — each becomes its own slice on top of this shell.
