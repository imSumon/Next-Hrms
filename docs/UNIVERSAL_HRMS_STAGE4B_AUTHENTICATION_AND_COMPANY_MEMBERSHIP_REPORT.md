# Universal HRMS — Stage 4B Authentication and Company Membership

Date: 2026-10-12

Status: **COMPLETE FOR THIS STAGE**. Login, logout, and explicit company membership are in place. Employee routes use them. The universal role list is not approved, so it was not invented.

No legacy employee was imported. No company was approved for migration. No production database was opened.

## 1. Inspection and plan

`next-hrms` is Laravel 13. The session guard and the `users` provider were already configured. No authentication package was installed, and no `users` table existed. Stage 4A closed `/employees` when `CompanyContext` was unbound.

The approved schema already has `companies`, `employees`, and `employment_episodes`. SEC-CORE-001 requires server-side checks on state-changing actions. SEC-CORE-002 rejects another company's data. SEC-CORE-004 says permissions are granted by role, and that role list is not approved.

Plan that was followed:

1. Add `users` and `company_memberships` in a new migration. Do not edit the foundation migrations.
2. Use Laravel's session guard. Hash passwords with the existing `hashed` cast. Do not add a package.
3. Resolve the company only from an active membership stored in the session after that check.
4. Let an active membership open the directory. Require a separate module flag, `can_manage_employees`, before create, edit, or episode changes.
5. Provide a local-only Artisan command for one synthetic user and one existing company. No registration screen. No self-service grant.
6. Keep Stage 4A employee rules: company-scoped unique code, immutable code, ULID, and one Open episode.

## 2. Authentication

No dependency was added.

- `GET /login` and `POST /login` are for guests. The post is throttled.
- A wrong email and a wrong password return the same message: "Those credentials are not valid."
- Login regenerates the session. Logout invalidates it and regenerates the CSRF token.
- Passwords are hashed and are hidden on the user model. The login action does not write the password to a log.
- There is no registration route.
- Guests are redirected to `/login`. A JSON request receives 401.
- CSRF stays on the framework `web` group (`PreventRequestForgery`). The login form includes the token.

## 3. Schema

Migration `2026_10_12_120000_create_users_and_company_memberships.php`.

`users`: ULID `id`, `name`, unique `email`, `password`, nullable `remember_token`, timestamps. The id check matches the existing uppercase ULID pattern.

`company_memberships`: ULID `id`, `user_id`, `company_id`, `status` of `active` or `inactive`, `can_manage_employees` default false, timestamps. Unique `(user_id, company_id)`. Foreign keys to `users` and `companies`.

`can_manage_employees` is a grant for this module only. It is not a role, and it is not global administration.

Applied on local `proedge_new_hrms` and on `proedge_new_hrms_test`, both at `127.0.0.1`. Only this new migration ran. No table was dropped.

## 4. Provisioning

Command, only when `APP_ENV` is `local`:

```text
php artisan hrms:provision-dev-user --name="Synthetic Clerk" --email="clerk@example.test" --password="<at least 12 characters>" --company="<existing company id>"
```

`--manage-employees` is optional. Without it, the membership can view and cannot change employees. The command does not create a company, does not grant a second company, and does not change an existing user. It does not print the password. Outside `local`, including the test environment, it fails before writing.

There is no HTTP endpoint for creating a user or a membership.

## 5. Company context

`RequireCompanyContext` runs after `auth` on employee routes.

- It loads that user's active memberships.
- The session company id is kept only when it is one of those ids.
- One active membership is selected automatically.
- More than one, and none selected, redirects to `/company`.
- The chooser and the header list contain only active memberships. The posted id is checked again.
- A query string or a hidden company id on an employee form does not change the company.
- An inactive membership does not count. No active membership returns 403.

## 6. Employee routes

Unchanged Stage 4A behavior, now behind login and membership:

- List, search, and detail require an active membership.
- Create, edit, and episode routes also require `can_manage_employees`.
- Without that flag the response is 403: "Employee changes are not authorized."
- An employee or episode outside the session company is 404.
- Employee code remains immutable. Separation still does not release a code.

## 7. Tests

`php artisan test` in `next-hrms`: **27 passed**, 125 assertions.

Covered: guest redirect and JSON 401, valid and invalid login, session regeneration, logout, web-group CSRF middleware, no membership, inactive membership, view without the write grant, forged company id, another company's employee and episode, multiple memberships, duplicate membership, no registration or membership HTTP route, and the provisioning command both outside `local` and inside it.

Stage 4A uniqueness, immutability, one Open episode, invalid status, unknown episode, and company boundary tests still pass. They now sign in a synthetic user on `proedge_new_hrms_test`.

The browser at `http://127.0.0.1:8000/employees` redirected to login. A failed login stayed on the form and showed "Those credentials are not valid." A successful browser login was not performed, because that would create a membership on a local company and no development user was provisioned.

## 8. Limitations

- SEC-CORE-004 is not implemented. There is no approved role list.
- SEC-CORE-003 branch, department, and employee scope is not implemented.
- `can_manage_employees` must not be copied onto payroll, attendance correction, or period close.
- Users cannot be given membership in the browser.
- The development command is refused unless `APP_ENV` is `local`.

## 9. Confirmation

No legacy or production database was accessed. No real employee data was imported. `.env` was not changed. No package was installed. No foundation migration was edited.

## 10. Next action

Approve a role list before this module flag is treated as a general permission. Decision capture in Step 20R is still required before a policy value or an employee migration.

STAGE 4B COMPLETE FOR THIS STAGE
