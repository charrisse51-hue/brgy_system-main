# User Management implementation

Personnel accounts support Administrator (Barangay Captain) and Staff Member (Barangay Secretary). Resident portal accounts remain separate and are preserved. Administrator routes enforce authorization on the server. Staff report access additionally checks the account's report authorization setting.

Account administration includes real account counts, searchable and paginated personnel records, role/status filters, separate name fields, unique usernames, contact information, activation/deactivation, and email password reset. Account deletion now deactivates the account and preserves its records and audit history. The last active administrator cannot be deactivated or demoted. Role changes, password changes, and deactivation revoke sessions and remembered logins. Audit entries identify the actor and affected account.

## Database compatibility and migration

The existing database did not store separate personnel name components, usernames, last login, session versions, or report permissions. The additive migration `2026_10_12_000002_add_account_administration_fields_to_users.php` adds these fields without changing existing relationships or deleting information. Existing names are preserved without guessing their components. Missing usernames display “Not set”; missing login timestamps display “Not recorded”. Existing email login remains available. New successful logins populate the timestamp. Existing staff report access defaults to allowed to preserve prior behavior.

The local MySQL database was backed up before applying this migration. Backup: `storage/app/private/backups/before-account-management-20261012-100053.sql`. Keep this private because it contains account and system data.

For another installation:

1. Back up its database and confirm the backup is readable. Deploy the application and migration together during maintenance.
2. Run `php artisan migrate --path=database/migrations/2026_10_12_000002_add_account_administration_fields_to_users.php --force` using a PHP version compatible with the project.
3. Confirm existing email login, administrator access, and account counts. Assign usernames and structured names through Edit Account when the actual values are known.
4. Keep the backup. A rollback drops only newly added fields but loses values entered in those fields; prefer a forward fix after accounts have been updated.

## Password reset delivery

The Laravel password broker supplies expiring, single-use email tokens, hashes the replacement password, and revokes prior sessions. No existing password is displayed. Inactive accounts cannot reset their password or log in.

This installation currently uses the `log` mail driver. Actual reset email delivery remains unavailable until an operational mail transport is configured. Configure the project's `MAIL_*` environment settings and a correct reachable `APP_URL`, clear cached configuration if applicable, and verify delivery with an authorized test account. The interface reports unavailable delivery instead of claiming an email was sent. Do not commit mail credentials.

## Verification

Feature tests cover account creation, duplicate usernames, password confirmation and hashing, staff authorization, report permissions, legacy accounts, activation/deactivation, session revocation, last-administrator protection, audit entries, username/email login, password-reset tokens, summary counts, filtering, and pagination. Browser checks use rendered test fixtures and real application assets at desktop, tablet, and mobile widths; they do not add sample users to the production database.

Final validation: 133 tests passed with 1,298 assertions. Browser checks passed at 1440px, 1024px, and 390px.
