# Universal HRMS — Stage 4A Employee Management Implementation Report

Date: 2026-10-12

Status: **PARTIAL**. The employee directory, create, edit, detail, and episode screens are implemented against the existing schema. The running application keeps them closed. No authenticated company membership exists, and this stage does not add one.

No legacy employee was imported. No company was approved for migration. D1, D2, D3, D4, and D6 stay unsigned. The Stage 3 draft was not treated as an approval.

## 1. Implementation plan

Inspection before coding:

- `employees` and `employment_episodes` already match Steps 20C and 20D. No column was missing for code, display name, company, ULID, episode status, or dates.
- `branches`, `location_nodes`, and `organization_nodes` exist. No employee assignment table exists. Step 20C named `employment_assignments` as a later schema candidate.
- `next-hrms` had no login, no users table, no policy, and no company membership. The welcome route was the only page.
- Unsigned decisions still include episode overlap, date order, inactive pay, and the migration dispositions.

Plan that was followed:

1. Reuse the six existing tables. Add no migration.
2. Write employees and episodes only through application services that take the company from a trusted context.
3. Refuse every employee route when that context is absent. Do not add a company picker or a role.
4. Keep employee code immutable after create, so separation cannot free it.
5. Allow the three stored episode statuses. Do not auto-separate, auto-rejoin, or enforce overlap or date order.
6. Test with synthetic companies in `proedge_new_hrms_test` on `127.0.0.1`. Leave `proedge_new_hrms` out of the test connection.

## 2. Files created and changed

Application, under `next-hrms`:

- Models: `app/Models/Company.php`, `Employee.php`, `EmploymentEpisode.php`, `app/Models/Concerns/HasCanonicalUlid.php`
- Services: `app/Employees/EmployeeRegistry.php`, `EmploymentEpisodeRegistry.php`
- HTTP: `app/Http/Controllers/EmployeeController.php`, `EmploymentEpisodeController.php`
- Requests: `StoreEmployeeRequest`, `UpdateEmployeeRequest`, `StoreEmploymentEpisodeRequest`, `UpdateEmploymentEpisodeRequest`
- Tenancy gate: `app/Tenancy/CompanyContext.php`, `app/Http/Middleware/RequireCompanyContext.php`
- Routes: `routes/web.php`
- Views under `resources/views/employees` and `resources/views/layouts/hrms.blade.php`
- Style: `public/css/hrms.css`
- Tests: `tests/Feature/EmployeeAccessTest.php`, `tests/Feature/EmployeeManagementTest.php`, `tests/EmployeeDatabaseTestCase.php`

Documentation:

- This report, in `docs` and `legacy-reference\hrms\docs`
- Project control center, both copies

No migration file was added or edited. `.env` was not edited.

## 3. Schema and components reused

`employees`: ULID `id`, `company_id`, required `employee_code`, required `display_name`, nullable `source_system` and `source_key`. Unique `(company_id, employee_code)` with no status predicate. No `employees.status`.

`employment_episodes`: ULID `id`, company and employee, required `joining_date`, nullable `last_working_date`, status `Open`, `inactive`, or `separated`. An Open episode cannot have a last working date. At most one Open episode per employee. The episode company and employee must match.

Laravel's ULID string is lowercase. The database check requires the uppercase Crockford form. Models uppercase the generated id. That is application formatting for the existing check, not a schema change.

`source_system` and `source_key` are not on the forms. New rows leave them null.

Organization and location tables were not given employee foreign keys. The detail page states that no assignment is stored.

## 4. Routes and authorization

| Route | Action |
| --- | --- |
| `GET /employees` | Company-scoped list, search `q`, 15 per page |
| `GET /employees/create`, `POST /employees` | Create code, name, and an optional episode |
| `GET /employees/{employee}` | Detail and episodes |
| `GET /employees/{employee}/edit`, `PUT /employees/{employee}` | Display name only |
| `GET/POST /employees/{employee}/episodes` | Add an episode |
| `GET/PUT /employees/{employee}/episodes/{episode}` | Edit that employee's episode |

`RequireCompanyContext` runs before the controller. If `CompanyContext` is not bound, the response is 403 and the unavailable page. The product never binds that context.

A lookup uses the context company id. Another company's employee id returns 404. `company_id` on a form is prohibited. `employee_code` on update is prohibited. Episode `company_id` and `employee_id` fields are prohibited. There is no delete route.

The browser check on `http://127.0.0.1:8000/employees` showed the unavailable page. Create and edit were not exercised in the browser because the gate is closed. Those paths were exercised by the feature tests with a context bound only inside the test process.

## 5. Implemented and deferred

Implemented when a company context is present:

- Directory, search, pagination, empty states
- Create and edit of the approved employee fields
- Episode create and edit within the schema checks
- Server-side validation messages for a duplicate code, a second Open episode, an Open episode with a last working date, and a status outside `Open`, `inactive`, and `separated`

Deferred:

- Authenticated company membership, which blocks opening the module
- Organization, branch, and location assignment
- Automatic separation, rejoining, overlap checks, and date-order checks
- Pay effect of `inactive`
- Legacy import and reconciliation

A reversed last working date can be stored. The screen says the date-order rule is not approved. That is not an approval of reversed dates.

## 6. Migrations

None. The required columns, unique code, composite employee foreign key, status check, Open-date check, and one-Open index are already present.

## 7. Tests

Command: `php artisan test` in `next-hrms`.

Result: **15 passed**, 56 assertions. Duration about 1.8 seconds.

Included:

- Closed directory, direct URL, create, and episode post without a company context
- List and search scoped to one company
- Empty directory
- Cross-company detail URL returns 404
- Required code and name
- Submitted `company_id` is rejected and creates no row
- Duplicate code in one company, including after a separated episode
- Same code in another company
- Display name edit, and rejection of a code change
- Open episode with a last working date, status `active`, and a second Open episode
- Reversed dates are stored, and the page states that the rule is not approved
- Pagination past 15 rows
- Unknown episode id returns 404
- The existing welcome-page example test

The employee tests used PostgreSQL database `proedge_new_hrms_test` on `127.0.0.1`. Rows were created inside a transaction and rolled back. The migrate command for that empty test database was run with `DB_DATABASE=proedge_new_hrms_test`. The development database named in `.env.example` is `proedge_new_hrms`. This stage did not point the tests at it.

## 8. Security and tenant isolation

The absence of company membership is the blocking finding. A hidden button would not have been sufficient, so the routes return 403 instead of listing every company.

Tests cover a forged company id, a direct id from another company, and search that does not return the other company's name. Those tests bind the context in the test process only.

## 9. Outstanding decisions

D1, D2, D3, D4, D5, D6, LIFE-OVERLAP, LIFE-ORDER, LIFE-INACTIVE-PAY, BD-D01 through BD-D18, and FY-BOUNDARY remain as recorded in Step 20R. BD-D15 stays deferred. No approval field was filled.

## 10. Confirmation

No production database was accessed. No legacy database was accessed. No legacy employee file was loaded. No package was installed. No foundation migration was edited. No company was marked migration-approved.

## 11. Next action

Add an authenticated company membership, then bind `CompanyContext` from that membership. Until then the employee screens stay closed.

STAGE 4A PARTIAL
