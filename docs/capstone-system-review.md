# Capstone system review

Review date: October 12, 2026

## Assessment and scope

This Laravel barangay records system has a credible capstone foundation: admin, staff and resident portals; resident individual records; disaster/incident logbooks; emergency impact records; archives; activity logs; dashboard totals; and summary CSV exports. The next priority is correctness, traceability and a demonstrable end-to-end workflow, rather than additional decoration.

This is a source review of routes, controllers, models, request validation, migrations, views, JavaScript, configuration and existing tests. It is not a completed penetration test, visual browser audit or certification of production readiness. No application behavior was changed. The test command `php artisan test` failed before tests ran: the terminal PHP is 8.0.30 and installed dependencies require PHP >=8.2. Actual deployed database differences and running web-server PHP were not verified.

## Priority 1: fix before the defense

### 1. Repair resident report submission

Evidence: `app/Http/Controllers/Resident/ResidentEmergencyController.php`, `app/Models/Incident.php`, and `database/migrations/2025_02_20_000004_create_incidents_table.php`.

The resident controller saves `photos`, but the supplied migrations do not create a photos column. It also omits `location`, although the incidents table requires it. On a database built from these migrations, this insert is incompatible with the schema. Photos are uploaded before the database insert, so a failed submission can leave orphan files.

Add the appropriate migration, capture a usable location or address alongside coordinates, and implement file cleanup when saving fails. Provide a manual map pin/address fallback when GPS is unavailable or permission is denied. Show a submission reference and provide the resident with their own report history and status. Test valid submissions with and without photos, failed saves, denied GPS and invalid uploads.

Defense demonstration: resident submits a report, staff sees the same report and updates it, and the resident sees the changed status.

### 2. Prevent account deletion from destroying official records

Evidence: `UserController::destroy` and the foreign keys in the incident, activity-log and resident-user migrations.

User deletion is permanent. Incidents, activity logs and linked resident records use cascading user deletion. Deleting an account can therefore delete records and historical actions associated with that person.

Prefer account deactivation. Preserve record authorship and historical identity; use deliberate restrictive or nullable foreign-key behavior where appropriate. Test that disabling or retiring a staff account leaves all records and logs intact. Prevent removal or demotion of the last usable administrator.

### 3. Make account status and authentication enforceable

Evidence: `routes/web.php`, `app/Http/Middleware/RequireRole.php`, `UserController::index`, and `UserController::update`.

Login checks email and password but does not check `is_active`. Role middleware also ignores account activity. The active-user count is assigned the total number of users. No login rate limiting is attached to the login route in the reviewed source. Logout calls `Auth::logout()` without the usual session invalidation and CSRF-token regeneration.

Enforce activity at login and on protected requests, implement working enable/disable controls, calculate real active totals, add login attempt throttling, and fully invalidate the session at logout. Add `role:resident` to resident routes, which currently require authentication only. Align login redirection with the role rules: the current linked-resident check can route a staff/admin account into the resident portal.

Acceptance checks: disabled users cannot log in or continue using an existing session; cross-role requests fail; repeated failed logins are throttled; logout ends access.

### 4. Fix profile edits and resident-account linking

Evidence: `resources/views/resident/profile.blade.php`, `ResidentDashboardController::updateProfile`, `ResidentController::store`, `app/Models/Resident.php`, and `DatabaseSeeder.php`.

The profile form offers suffix, gender, phone, email and address fields, but its handler only validates and saves first/middle/last names, birthdate and civil status. Users can receive a success message while other changes are ignored. It also returns success when no resident record is linked. The seed resident account is explicitly created without a resident record, and the reviewed resident-creation flow does not establish an account link.

Create an explicit admin-controlled account-to-resident linking workflow; enforce one resident record per account where intended. Map form names to stored fields consistently, apply the same validation as administrative records, recompute derived name/address values, and show a clear result when a profile is not linked. Decide which official details residents may change directly and which require staff approval.

### 5. Complete or accurately scope public alerts

Evidence: `routes/web.php`, `EmergencyRecordController::save`, `AlertController`, `ResidentDashboardController::alerts`, and `resources/views/resident/partials/alerts_feed.blade.php`.

New emergency records are correctly private: `is_record=true` and `is_active=false`. Residents only see active, non-record alerts. However, the reviewed routes provide creation of private emergency records and editing/toggling existing alerts, but no dedicated public-alert creation route. A fresh installation therefore lacks a complete public-alert authoring workflow. The resident feed reads `severity` and `type`, while the alert model uses `level` and `emergency_type`; severity presentation can fall back instead of reflecting the saved level.

If public alerts are in scope, provide a separate authorized compose/publish/expire workflow with publication history and consistent fields. Keep casualties, personal details and internal response notes private. If this capstone is principally an electronic records system, clearly document public notification as a limitation until implemented. Do not describe polling, a notification icon, or unused broadcast JavaScript as delivered SMS, push notification or real-time dispatch.

### 6. Establish a reproducible runtime

Use PHP 8.2+ compatible with the installed dependencies and required extensions. Confirm terminal PHP and web-server PHP separately. Run the complete suite, frontend build and a fresh migration in a disposable database, never against important records. Document versions, installation, configuration, storage permissions and recovery steps.

The application timezone is hardcoded to UTC in `config/app.php`. Choose a consistent storage/display strategy and display Philippine local time correctly. Date-only and datetime-local inputs need particular attention: future-date checks and monthly totals must use the intended timezone.

## Priority 2: strengthen reliability and defense evidence

### Traceable workflow and verification

`IncidentController::updateStatus` permits any of pending/ongoing/resolved without assignment, a resolution requirement or a transition history beyond generic logs. The legacy `StaffIncidentController::updateStatus` route updates status without an activity log. Emergency saving lets admin or staff mark a record verified and records the acting user as verifier; it does not establish independent approval.

Define who receives, assigns, acts on, resolves, verifies and archives a report. Require action taken/resolution notes where appropriate, log every route consistently, and record the responsible officer and relevant timestamps. If independent verification is an objective, enforce it explicitly; otherwise describe verification as a recorded staff action. Use optimistic concurrency or another conflict strategy so simultaneous edits do not silently overwrite each other.

### Consistent reporting

Summary reports currently filter by record creation time and show current status, which is disclosed in their output. They do not reconstruct historical status at a selected date. Emergency summaries query all Alert rows, combining public alerts and private emergency records. Dashboard counts include archived incidents while active incident listings exclude them.

Define each metric, label creation-date versus occurrence-date filtering, separate public alerts from emergency records when counting, and align archive inclusion across screens. Add occurrence-date and sitio/type breakdowns if stakeholders need them. Validate totals against a small manually counted dataset. Add official print layouts with date range, report author and signatory spaces if required by the barangay. CSV export already exists; it does not need to be presented as missing.

### Durable audit and backups

Use a transaction for each record mutation and its audit log. Emergency saves already do this; incident, resident and user operations generally do not. Record before/after changes, actor, record reference and time, while avoiding unnecessary sensitive data in log descriptions. Log alert activation and resident actions as well as the main staff flows.

No backup/restore implementation was found in the reviewed application source. Provide a documented backup process covering both the database and uploaded files, with restricted access and an actual restore exercise in a separate environment. A backup button is optional; demonstrated recoverability is the important outcome.

### Sensitive information and uploads

Resident records include identity, health, income and family fields. Document why each field is collected, who may view or edit it, how long it is kept and how corrections are handled. Consider masking identifiers and restricting sensitive fields beyond general staff access. Review uploaded incident photos, currently stored on the public disk, and serve sensitive evidence through authorized access where appropriate. This review makes no legal-compliance determination.

### Data quality

Add duplicate warnings based on a combination such as name, birthdate and address, with staff review rather than automatically rejecting people with identical names. Enforce unique resident-account linkage if one-to-one is intended. Standardize sitio/purok/zone mappings across forms. Preserve Unicode and compound names; avoid relying on splitting old full names to recover precise name parts. Add indexes based on measured list/report queries as the dataset grows.

### Error handling and performance

The dashboard catches database exceptions and substitutes zero values. This can make a failed query look like a valid empty dataset. Log failures and display an unavailable state. Consolidate repeated aggregate queries where useful and test pagination/search/report generation with realistic record volumes. Keep local copies of critical external fonts, icons and map assets if the demo or deployment must work without internet.

### Documentation and maintenance

Replace the stock Laravel README with the actual system purpose, supported roles, setup steps, module descriptions, ERD, test instructions, deployment and backup procedures. Separate demo seed accounts from production provisioning and remove predictable demo credentials from real deployment. Consolidate duplicated resources/public JavaScript and CSS to reduce inconsistent fixes. Settings currently displays configuration; describe it as read-only configuration rather than an implemented settings editor.

## Testing needed before presenting

Existing tests provide useful coverage of admin/staff records, validation, archives, role boundaries, rollback and summaries. Their presence is a strength, but they were not executable in the current terminal runtime.

Add or verify these higher-risk scenarios:

1. Resident report submission with and without attachments against a freshly migrated schema.
2. Disabled login, existing-session revocation, login throttling and logout invalidation.
3. Resident ownership restrictions and cross-role URL access.
4. Profile changes for every field offered by the form, including unlinked accounts.
5. Account retirement without deletion of records or audit history.
6. Complete public alert creation/publication if in scope; private records never appear publicly.
7. Audit consistency for legacy routes and transaction rollback on logging failure.
8. Concurrent record edits and server/upload failure recovery.
9. Reports checked against known totals, archive rules and date/time boundaries.
10. Backup restoration, mobile use, GPS denial and any promised offline behavior.

## Defense preparation

Present one clear problem statement supported by barangay interviews or observed processes: for example, retrieval delays, incomplete logbook records, inconsistent summaries or weak accountability. Obtain stakeholder confirmation of required forms, staff permissions and report formats. Explain the benefit of structured records, controlled access, traceability and reporting in terms of that confirmed problem.

Prepare these artifacts:

- Objectives-to-feature-to-test matrix.
- ERD and data dictionary explaining incidents, emergency records, public alerts and resident accounts.
- Use-case/workflow diagrams and role-permission matrix.
- Test results with actual pass/fail counts, environment versions and unresolved issues.
- User acceptance results with the participants, tasks, method and real feedback.
- Measured retrieval/data-entry/reporting times compared with the existing process, if available.
- Deployment requirements, backup/restore evidence and known limitations.

Do not fabricate evaluation scores, user counts, performance results or response-time improvements. Only claim implemented behavior that you can demonstrate.

Likely panel questions and evidence to prepare:

| Question | Evidence or demonstration |
| --- | --- |
| What problem does this solve beyond storing records? | Confirmed workflow problem, structured capture, retrieval/reporting improvement and stakeholder feedback. |
| Why separate disaster records, emergency records and alerts? | Clear definitions, ERD and one real example of each; explain the current theft/complaint versus disaster terminology. |
| Who can see personal or casualty information? | Permission matrix and direct URL-denial tests. |
| How do you prevent false reports? | Authenticated intake, staff verification, evidence review and audit history; authentication alone does not prove truth. |
| How does a resident know action was taken? | Personal report history and status updates, once implemented. |
| What happens when a staff member leaves? | Deactivation while records and authorship remain intact. |
| What happens if the server fails? | Database-and-file backup and an actual restoration exercise. |
| What happens without internet or GPS? | Demonstrated fallback and clearly stated limitations. |
| Are alerts real time and are responders dispatched? | Show the actual delivery mechanism and timing; state dispatch/SMS limitations honestly. |
| How do you know reports are accurate? | Known-data reconciliation and documented metric/date definitions. |
| How was the system evaluated? | Reproducible tests, realistic tasks and genuine user acceptance evidence. |

Suggested demo: sign in as staff, create a resident record, create a disaster logbook, show validation, update status with action notes, create an emergency impact record, demonstrate private/public separation, export a date-filtered summary, show the audit history, then demonstrate role denial and retained records after account deactivation. Include the resident submission/status sequence only after repairing and verifying it.

Recommended order: repair submission and data preservation; enforce account/role rules; fix profile linking; complete or narrow alerts; establish runtime and tests; verify backups and report totals; then polish the demo and documentation.
