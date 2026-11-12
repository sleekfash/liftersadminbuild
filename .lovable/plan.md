# Handover Notifications, History, and Escalation

## Outcome
Add timely, role-scoped handover communications and a branch-specific audit-history view while preserving Laravel as the source of truth and keeping branch records attached to their branch.

## Work
1. **Notification delivery**
   - Persist in-app notices for claim submission, incoming-manager acceptance or dispute, administrator approval, scheduled reminders, and overdue escalation.
   - Add a notification inbox/bell with unread counts and read state; expose only notices the signed-in user is entitled to see.
   - Send matching event-specific app emails to the directly involved decision-makers and administrators where appropriate. Do not use bulk/list sends.
2. **Branch handover history**
   - Add a dedicated history view filtered to the branch, displaying each transfer’s current status, outgoing and incoming managers, and timestamped decision/audit timeline.
   - Reuse the authorized Laravel handover and audit data; preserve branch scoping for managers and cross-branch access rules for administrators.
3. **Reminders and escalation**
   - Schedule idempotent reminders at 1 and 3 days for the current decision owner.
   - At 5 days, escalate the still-pending handover to administrators and notify the branch decision chain. Stop reminders once the handover is completed or disputed.
   - Record each reminder/escalation in the audit trail and avoid duplicate sends across scheduler retries.
4. **Validation**
   - Add backend tests for recipient scoping, each decision event, exact reminder/escalation thresholds, duplicate prevention, and stopping notices after resolution.
   - Add frontend tests for notification read state and branch history rendering; verify the mock flow and run project build/tests.

## Technical approach
- Keep decision and reminder rules enforced in Laravel, where the authenticated handover state and scheduler live; use the existing branch-level authorization and transaction/audit conventions.
- Use Laravel's persisted notification channel for in-app delivery; do not add an email queue or email-specific database tables.
- Use Lovable managed app email templates for single-recipient event mail. The sender domain is not configured yet, so email delivery requires setting up a domain the project owner controls before template scaffolding and live sending.
- Keep preview/mock support aligned with live event names, recipients, timestamps, unread/read state, and deduplication behavior.

## External prerequisite
Set up a sender domain owned by the project owner before enabling app email delivery. The in-app notifications, history, and reminder/escalation workflow can otherwise be completed and verified independently.
