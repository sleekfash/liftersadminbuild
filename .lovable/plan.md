# Live Admin Login Access

## Goal

Provide a secure super-admin sign-in for the live Laravel system using `admin@lifterscenter.com`, with a generated temporary password that is shown only once and never placed in frontend code, browser storage, logs, or source control.

## Plan

1. **Confirm live connection and provisioning path**
   - Configure the live Laravel API base URL for the app’s live mode.
   - Use the existing protected administration mechanism rather than creating a client-side admin shortcut.
   - If the live system is not reachable or no provisioning permission is available, stop before creating credentials and report the exact missing prerequisite.

2. **Create the administrator securely**
   - Provision `admin@lifterscenter.com` as an active user with the canonical `SUPER_ADMIN` role.
   - Keep the user unassigned to a branch unless the live agency policy requires an initial branch assignment.
   - Generate a strong temporary password server-side or through the Laravel deployment process; never hardcode it in the frontend or commit it.
   - Preserve the backend’s existing behavior of not overwriting an existing administrator password silently.

3. **Make first sign-in safe**
   - Confirm the login response returns the administrator’s role and current account state.
   - Ensure the live app uses the Laravel token flow, not the preview’s mock account.
   - Add or document the required password-change step after first sign-in if the Laravel system supports it; do not expose the temporary password again after the initial handoff.

4. **Verify access**
   - Test sign-in with the generated credentials against the live API.
   - Confirm the administrator reaches the protected dashboard and Administration area.
   - Confirm non-admin users cannot access administration actions.
   - Confirm failed credentials do not reveal whether an email exists.

## Required before provisioning

The live Laravel URL and an authorized deployment/admin provisioning path must be available. The current project contains the admin seeder and login route, but the live backend itself cannot be changed from the preview without that connection and permission.

## Verification

- Test the live login endpoint and role-bearing response.
- Test the browser login flow in live mode.
- Run the frontend production build after any configuration or code changes.
- Report the generated temporary password only in the secure handoff response, once.

## Not included

Changing the existing role model, moving users between branches, enabling MFA, or creating additional user accounts is outside this access request.