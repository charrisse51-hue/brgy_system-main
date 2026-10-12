# Proposed position/designation storage

Inspection of the live MySQL `users` table confirmed that it has `role` but no separate position or designation column. The system uses `role` (`admin`, `staff`, and existing resident accounts) for backend permissions. It must not be repurposed.

## Approved additive migration

Add one nullable column:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('position', 100)->nullable();
});
```

Existing accounts keep their roles, passwords, relationships, and other values. Existing positions remain NULL; no position is guessed from role and no automatic Captain/Secretary backfill is performed. No records or tables are deleted.

Position will be optional to support existing accounts and people without a supplied designation. Empty or whitespace-only input will be stored as NULL; supplied values will be trimmed and limited to 100 characters on both frontend and backend. It will be editable only through administrator Create/Edit User Account forms. Typing a position will never change permissions.

User Role will show only Administrator or Staff Member. Create retains the authorized role selector. Edit displays the existing role read-only; backend validation rejects attempts to change it through this form. Position appears separately in User Management, My Profile (read-only), and the shared account display. My Profile self-editing will not permit changes to position or role.

## Deployment after approval

1. Back up the live MySQL database to private storage and verify the backup exists.
2. Prepare and run only the additive Laravel migration above, then deploy the corresponding model, forms, validation, and account-display updates.
3. Verify creation and editing for admin and staff, value persistence, maximum length, blank values, rejection of role tampering, and unchanged authentication/permissions.
4. Run the relevant account/profile tests and responsive browser checks.

Rollback: revert the application changes first. Keep the nullable column unless its values have been exported and dropping it is explicitly wanted; dropping it would discard newly entered positions. Existing account data remains intact.

The user approved this migration. Applied `2026_10_12_000003_add_position_to_users_table.php` after creating private backup `storage/app/private/backups/before-user-position-20261012-152452.sql`. All preexisting account attributes were compared before and after: unchanged. Existing positions remain NULL (zero populated automatically).

Implemented the forms, validation, model mapping, and separate position display in User Management, My Profile, and the sidebar account button. Profile self-editing still cannot change position or role. The position is optional; the edit role is enforced as immutable on the backend. Validation: 157 tests passed (1,545 assertions); Create/Edit dialogs and profile pages passed desktop, tablet, and mobile browser checks.
