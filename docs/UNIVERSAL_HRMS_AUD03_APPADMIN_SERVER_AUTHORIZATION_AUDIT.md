# Universal HRMS — AUD-03 Appadmin Server Authorization Audit

Status: **SOURCE AUDIT COMPLETE**. No database was queried. This is the backend admin application. It is not a catalog module. ESS authorization from AUD-01 and AUD-02 is a separate session and was not re-audited.

Observed checks are legacy behavior. They do not sign a role list, an approval hierarchy, or a tenant-access policy.

## 1. Executive findings

Authentication and authorization are custom session checks. Yii `AccessControl` is imported in `backend\controllers\SiteController.php` and is not attached as a filter. `backend\config\main.php` configures `user.identityClass` as `common\models\User` and does not configure `authManager`. Login does not call `Yii::$app->user->login()`.

The level strings in source are `supadmin`, `admin`, and `admin_user`. A missing or other value is rejected only where an action tests it.

`moduleAccess()` in `backend\components\ApuadminComponent.php` checks a module grant. Every call inside `backend\modules\appadmin\controllers` is commented out. The method does not read the controller or the action. `user_companies_module_controllers` is written from the user screen and is read by grid views to choose buttons. That view check is not a server check.

Eight appadmin controllers contain no `user_level` test. `UserController::actionUpdate` has its level test commented out, and both the AJAX and non-AJAX paths save the posted user. `SiteController::actionBackend` sets a session from a username lookup and does not compare a password.

No `appadmin` controller branches on `atlas`, `vtl`, `proedge`, or another company database name. Company scope, where it exists, is a `user_companies` row plus a session `db_alias`.

The written requirement that the server authorizes the action has no matching legacy action check. That requirement stays CORE. The legacy level names and grant tables stay LEGACY ONLY. Which roles and which tenant boundaries the new product uses stay unsigned.

## 2. Authentication and authorization

### Session identity

`ApuComponent::sesdata()` in `frontend\components\ApuComponent.php` returns `user.` plus the field name from the PHP session. The backend component `apu` is that same class (`backend\config\main.php`).

`SiteController::actionLogin` looks up `user` with the posted username and password in one SQL string. A row with `status` 10 stores:

| Session key | Source column |
| --- | --- |
| `user.username` | Posted username |
| `user.userid` | `user.id` |
| `user.user_level` | `user.user_level` |
| `user.panel` | `user.panel` |
| `user.zone` | `user.zone` |
| `user.companydbcon`, `user.companydbname`, `user.companyid`, `user.company_old_id` | Cleared |

Inactive status and a missing row render the login page. They do not set those keys.

`actionLogout` calls `session_destroy()`. The Yii user logout call above it is commented out.

`actionHome` and `actionProbationers` then set `user.companyid`, `user.companydbname` to `companies.db_alias`, `user.companydbcon` to that Yii connection, and `user.company_old_id` to `previous_db_id`. `GroupsController::actionGrouppanel` sets `user.group_id`.

This identity is an admin `user` row. It is not the ESS employee id used by leave and attendance.

### What is not used

- No `authManager` in `backend\config\main.php`.
- `common\models\User` maps the `user` table and implements `IdentityInterface`. The login action does not use `findByUsername` or a password hash method.
- The appadmin module class has an empty `init()`. It adds no filter.
- Controller `behaviors()` that exist attach `VerbFilter` so delete, and sometimes bulk-delete, must be POST. They do not attach `AccessControl`.
- `GroupcompanydepartmentsController` places `behaviors()` inside a comment, so that controller has no verb filter either.

Loading `ApuadminComponent.php` runs file-level code before the class: if `sesdata('userid')` is empty it calls `die()`, then `session_destroy()`. That runs when something first touches `Yii::$app->apuadmin`. Appadmin actions that never touch that component do not run it.

## 3. Controller and action trace

Twenty-three controllers under `backend\modules\appadmin\controllers` were classified by their level tests. The backup tree `backend\modules\00backup00\appadmin` was not treated as current code. Every action body in all twenty-three files was not line-read. The eight controllers with no level test were opened at their index and state-changing actions. The fifteen with a level test were classified from every `if($sesdb` in those files, and the user, company, module, and group actions were read.

### Level required

| Controllers | Level that passes | Failure |
| --- | --- | --- |
| `DefaultController`, `CompaniesController`, `ModulesController`, `SubmodulesController`, `ControllersController`, `ActionsController`, `CompanymodulesController` | `supadmin` | Session destroyed and redirect to logout, except `CompanymodulesController` actions whose `if` has no `else` and then return nothing |
| `UserController` index, home, view, create, delete | `supadmin` or `admin` | Logout. `actionBulkDelete` allows `supadmin` only |
| `GroupsController`, `GroupsdivisionsController`, `GroupdepartmentsController`, `GroupemployeesController`, `GroupofcompaniesController`, `GroupdesignationController`, `GroupdivisiondesignationController` | `supadmin` or `admin` | Logout on the group controllers that have an `else`. `GroupdesignationController` and `GroupdivisiondesignationController` have no `else`; a failed check ends the action |

`admin_user` is accepted by `actionLogin`, `actionIndex`, `actionHome`, and `moduleAccess()`. It is not accepted by the user-administration and catalog actions above.

`UserController::actionIndex` limits an `admin` list to `created_by` equal to the session user id. Session user id `10` skips that filter. The matching ownership test in `actionView` is commented out.

### No level test

These controllers do not compare `user_level`. Their `moduleAccess()` calls are commented out.

- `AllattlogController` — index, view, create, update, delete, upload, archivelogs
- `AllattlogoldController`
- `LocationaccessdataController`
- `GroupcompanydepartmentsController`
- `GroupcompanydesignationsController`
- `GroupdivisiondepartmentsController`
- `GroupdivisionemployeesController`
- `GrouporganogramController`

`AllattlogController::actionDelete` deletes by id. `actionUpload` accepts a POST upload with no level test. `LocationaccessdataController::actionView` returns JSON for an AJAX request with the same absence of a level test. AJAX and a normal request share one path. Neither path checks a grant.

### Direct URL

The sidebar in `backend\views\layouts\leftside.php` shows System Settings only when the level is `supadmin`, and System Users when the level is `supadmin` or `admin`. Location Access is shown when the level is `supadmin` or the username is `abir`, and it is omitted for a fixed username list (`uladmin`, `poshora`, `VSLSS`, `atlashr`, `pms2022`, `ottoadmin`, `astrapfloan`, `fordemo`). Those are display conditions.

`LocationaccessdataController` does not repeat the username or level test. A direct request is not stopped by the hidden menu item.

`UserController::actionUpdate` comments out the `supadmin` or `admin` test (the block begins at the commented `if` and ends at the commented `else` that would have logged out). The AJAX branch saves a loaded POST. The non-AJAX branch saves a loaded POST and only afterwards uses the level to choose a redirect. A GET form is limited to `supadmin`, or to `admin` / `admin_user` when the id is their own user id. The save itself is not inside that GET branch.

Delete remains inside a live `supadmin` or `admin` test. VerbFilter still requires POST for delete. POST alone is not an authorization check.

### Module grant, outside this module

`moduleAccess()` is called from other backend modules, including leave and payroll. Inside it:

1. The current Yii module id is looked up in `modules.mname`.
2. For `admin_user`, it counts `user_companies_modules` for the session company, that module, and the session user.
3. For any other level, it counts `company_modules` for the session company and that module.
4. If the level is `supadmin`, `admin`, or `admin_user` and the count is 0, it redirects to `site/home` for that company id.
5. Any other level destroys the session and redirects to logout.

The comments name the controller id and the action id. The method does not query them. A company-module row or a user-module row therefore satisfies every action in that Yii module.

`UserCompaniesModuleControllers` is assigned in `UserController` when modules are saved. `backend\modules\pims\views\employees\_columns.php` reads it only when the level is not `admin` or `supadmin`, and uses the stored action string to build `{view}`, `{update}`, and `{delete}` button tokens. `admin` and `supadmin` receive all three tokens without that read. The same pattern appears in other `_columns.php` files. Hiding a grid button does not stop the action URL.

## 4. Company scope

`actionHome` and `actionProbationers`: `supadmin` may open any company id. `admin` and `admin_user` must have a `user_companies` row for that user and company id, or the session is destroyed.

The opened company selects `db_alias` as the session database name. Later modules use that connection. Appadmin group actions that mention `companydbname` have the `setDb` call commented out, so those screens stay on the default connection even after a company is opened.

No appadmin controller contains a branch on a company database name such as `atlas`, `vtl`, or `proedge`. The username list in the sidebar is a display filter, including `atlashr`. It is not a company-id authorization rule, and the location-access controller does not enforce it.

Boss and grand-boss checks are not in these controllers. AUD-02 remains the approval-chain evidence.

## 5. Enforcement gaps

| Gap | Evidence | Limit |
| --- | --- | --- |
| Password compared in SQL text, stored and posted as the same value | `SiteController::actionLogin` | No live account was queried |
| Session created from a username with no password comparison | `SiteController::actionBackend` | The action is on the admin site controller, which is what appadmin then trusts |
| Eight controllers serve index and state-changing actions with no level test | Named in section 3 | Not every line of `GrouporganogramController` was read. Its opened actions do not test the level, and the file has no `if($sesdb` |
| User update saves without the level test | `UserController::actionUpdate`, commented `if` | Delete and bulk-delete still test the level |
| User id `10` skips the creator filter | `UserController::actionIndex` | No claim about who that row is |
| Module grant is not an action grant | `moduleAccess()` | Other modules call it. This audit did not read every one of those call sites |
| Controller grant affects buttons | PIMS `_columns.php` and sibling grids | Not proof that every screen hides buttons this way |
| Menu visibility is separate from the URL | `leftside.php` versus `LocationaccessdataController` | Gii and Debug links are in the `supadmin` menu. Those modules are not registered in the `modules` array of `backend\config\main.php` |

## 6. Classification

| Finding | Class |
| --- | --- |
| The server must allow or refuse the action, independent of the screen | CORE. Already written. Legacy does not do this at action granularity |
| One admin identity is not an employee identity | CORE distinction. ESS binding stays the existing ESS requirement |
| Level names `supadmin`, `admin`, `admin_user` | LEGACY ONLY. The new role list is a BUSINESS DECISION and is unsigned |
| `user_companies` as the company list for non-`supadmin` | LEGACY ONLY. Whether a user may hold more than one company is unsigned |
| `company_modules` and `user_companies_modules` as module switches | LEGACY ONLY. A per-company module switch can be CONFIGURABLE only after an owner signs the list |
| `user_companies_module_controllers` as button visibility | LEGACY ONLY. It is not server authorization |
| Hard-coded user id `10` and hard-coded usernames in the sidebar | LEGACY ONLY. Do not copy them |
| Alias-specific authorization inside appadmin | Not found. Do not invent a Proedge, VTL, or Atlas admin rule from this module |
| Which actions a role may call, and whether a role is global or company-scoped | BUSINESS DECISION. Unsigned |

## 7. Proposed acceptance checks

These checks are not approved requirements.

- An unauthenticated request to an admin action is refused before a read or a write.
- The permission check names the action, not only the module, and it runs for a direct URL and for AJAX.
- A hidden button is not the check. Removing the button still leaves the action refused.
- A user opened against one company cannot read or write another company's data by changing an id.
- `supadmin`-style cross-company access exists only if an owner signs that role. It is not the default copied from this code.
- Password verification does not use a SQL string comparison, and a username lookup alone does not create a session.
- Grant tables that only hide a button are not treated as the authorization record.

## 8. Files inspected

- `backend\config\main.php`
- `backend\controllers\SiteController.php`
- `backend\components\ApuadminComponent.php` (`moduleAccess` and the file-level session test)
- `frontend\components\ApuComponent.php` (`sesdata` only)
- `common\models\User.php`
- `backend\modules\appadmin\module.php`
- All 23 controllers under `backend\modules\appadmin\controllers`, at the depth in section 3
- `backend\views\layouts\leftside.php`
- `backend\modules\pims\views\employees\_columns.php`
- Step 21B for the absence of Yii RBAC tables in the catalog work already done, and for `user_companies_module_controllers` as an assignment table. This audit did not re-parse the dump and did not count rows.

Not inspected as current behavior: `backend\modules\00backup00\appadmin`, ESS controllers beyond the AUD-01 and AUD-02 citations, and every `moduleAccess()` call site outside appadmin.

## 9. Next audit

DB-01 stays blocked until MySQL is already running.

Next source audit in the plan: **AUD-04**, the punch writer. This audit does not change that order.

A remaining authorization limit, not a new signed task: non-appadmin actions were not exhaustively read. What was verified is that `moduleAccess()` is module-scoped and that sampled grids use the controller grant for buttons.

## 10. Statement

No database was connected. MySQL was not started. No application code, schema, migration, or environment file was changed.

AUD-03 SOURCE AUDIT COMPLETE
