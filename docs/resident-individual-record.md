# Resident individual record form

The existing Resident Records page uses its existing Add and Edit modal containers, expanded into five steps. No resident table or record was deleted. The previous temporary blank-list override was removed so stored residents, counts, search, pagination, and Edit can work again. No sample residents were added.

## Files

Modified:
- `app/Http/Controllers/admin/residentController.php`: shared validated save/update data; additional civil statuses; existing create/details URLs open the existing module's modals.
- `app/Models/Resident.php`: additional fillable fields and JSON/boolean/decimal casts.
- `resources/views/admin/resident_record.blade.php`: shared Add/Edit forms, full edit data, live listing restored, dedicated form assets.
- `resources/views/admin/partials/resident-filters.blade.php`: all seven civil statuses supported.
- `resources/views/admin/summary_reports.blade.php`: removed the obsolete note about temporarily hidden residents.

Added:
- `app/Support/ResidentForm.php`: shared fields, labels, defaults, and choices.
- `app/Http/Requests/ResidentRecordRequest.php`: server validation, conditional cleanup, legacy name/address compatibility.
- `resources/views/admin/partials/resident-individual-modal.blade.php`: shared five-step modal markup.
- `public/js/resident-individual-form.js`: steps, field conditions, review, saved values, validation recovery, keyboard handling, and details.
- `public/css/resident-individual-form.css`: wider modal, scrolling, responsive columns, step indicator.
- `database/migrations/2026_09_18_000001_expand_residents_for_individual_record.php`.
- `tests/Feature/ResidentIndividualRecordTest.php`.
- This guide.

## Database changes

No new tables. The migration adds these nullable columns to `residents`:

| Group | Columns |
| --- | --- |
| Address | `barangay`, `sitio_purok`, `house_number`, `street`, `subdivision` |
| Personal | `philsys_card_number`, `birthplace`, `sex`, `religion`, `citizenship`, `ethnicity`, `educational_attainment`, `school_location`, `labor_force_status`, `occupation` |
| Income | `monthly_income` (decimal), `income_sources` (JSON array) |
| Target groups | `target_groups` (JSON array), `pwd_classification`, `pwd_other`, `immunization_status`, `senior_citizen_number`, `out_of_school_youth` and `can_read_write` (nullable booleans) |
| Contact and identity | `email`, `blood_type`, `height_cm` and `weight_kg` (decimals), `registered_voter` (nullable boolean), `last_year_voted` (integer), `voter_location`, `mother_last_name`, `mother_first_name`, `mother_middle_name` |

Existing `civil_status` changes from a four-option enum to a nullable string so all seven choices fit without discarding old values. Existing name, address, contact, birthdate, zone, and status columns remain in use. Structured address entries generate the existing display/search address; an older unstructured address remains available for editing. Blank optional yes/no fields remain null rather than being recorded as No.

The local migration has already been applied. The existing resident count and a checksum across all original columns matched before and after migration.

## Commands

No commands are needed on this local installation; refresh the browser. On another installation with these changes, run from the project root:

```powershell
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan test --compact --filter="ResidentIndividualRecordTest|SummaryReportTest"
```

The new JavaScript and CSS are served from `public`, so these form changes do not require an npm build. Do not use `migrate:fresh` or `migrate:refresh` on a database containing resident records.

## Test Add

1. Open Resident Records and choose Add New Resident.
2. Complete Address, then Personal Information. First and last name are required; resident status defaults to Active, barangay to Doyong, citizenship to Filipino.
3. Select multiple income sources and target groups. Select PWD and Others to show the required classification textbox. Select Senior Citizen to show its number.
4. In Contact & Identity, select Registered Voter = Yes to display year and registration location.
5. Reach Review and inspect all fields. Only Review shows Save Resident. Back allows corrections; Cancel closes without saving.
6. Save and confirm the new row, counts, search results, and summary report totals.
7. Verify that missing required names, future birthdates, invalid email, and negative income are rejected. Server validation reopens the correct modal with submitted values and errors.

## Test Edit

1. Click Edit on a stored resident. Confirm all saved fields, multi-select checks, and conditional sections load.
2. Change several fields; inspect Review and Save Changes.
3. Reopen Edit and verify persistence. Unchecking PWD or Senior Citizen, or selecting voter No, clears the now-inapplicable saved details.
4. For a legacy record, confirm its original address is retained unless replaced with structured address fields. Missing new fields can remain blank.
5. View Details opens the same module in a read-only review modal.

## Verification and scope

Automated coverage includes save/update, JSON arrays, conditional cleanup, future dates, invalid values, validation recovery, legacy records, live listing, civil-status search, create/details navigation, summary totals, and report authorization. JavaScript syntax and Blade compilation are also checked. Interactive browser testing of the full wizard remains a manual check.

The existing page design and dashboard were not redesigned. The old create/details routes remain, but now redirect into the existing page because the requested workflow uses modals. The prior details route referenced a missing Blade view. No archive implementation was added or removed.
