# Universal HRMS — AUD-09 ESS Document Identity and Ownership

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** Source inspection is not runtime verification. No request was sent and no file was uploaded or deleted. Classifications are proposals, not approved requirements. This batch does not sign a decision or authorize implementation.

AUD-08 named these controllers as the next batch and did not open them. AUD-01 through AUD-07 were not repeated. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. Controllers and actions

Three ESS controllers exist. `DocumentsController` has only `actionIndex`. The other two implement list, view, create, update, and delete.

| Controller | Actions read | Not a separate feature |
| --- | --- | --- |
| `DocumentsController` | `actionIndex` | No upload or delete action in this file |
| `DocumentController` | `actionIndex`, `actionView`, `actionCreate`, `actionUpdate`, `actionDelete`, `findModel` | Standard generated CRUD. Delete is limited to POST by `VerbFilter` |
| `DocumentbyempController` | `actionIndex`, `actionIndexea`, `actionView`, `actionCreate`, `actionUpdate`, `actionDelete`, `actionCreatea`, `actionViewa`, `actionUpdatea`, `actionDeletea`, `findModel` | `actionIndexa` was not line-read past its opening. It is the same search shape |

No JavaScript caller was required to see the authorization result. The actions take the id from the route.

## 2. How identity is applied

| Action | Lines | Identity check | Ownership check |
| --- | --- | --- | --- |
| `DocumentsController::actionIndex` | 28–48 | Session user level must be present. Module name must include `documents` | `document.employee_id` is forced to `sesdata('employee_id')`. The file-cabinet search that follows has no employee filter in the lines read |
| `DocumentController::actionIndex` | 41–57 | User level must equal `clientAdmin`. Module name must include `documents` | No employee filter. `DocumentSearch` is pointed at the session database name |
| `DocumentController` view, create, update, delete | 90–145 | None in the action | `findModel` is `Document::findOne($id)` and does not call `Document::setDb`. It does not compare `employee_id` |
| `DocumentbyempController::actionIndex` and `actionIndexea` | 42–56 and 89–102 | User level is read | If the level is not `clientAdmin`, the list is `document_by_emp.employee_id` equal to the session employee. If it is `clientAdmin`, `actionIndex` keeps rows whose `uploaded_by` is not the session username. `actionIndexea` adds `company_id` from `user.company_id` to that admin filter |
| `DocumentbyempController` view, update, delete, and the `a` copies | 137–171, 249–282, 323–335, 345–353, 395–464 | The session database name is selected | `findModel` and `findOne($id)` do not compare `employee_id` or `uploaded_by` |
| Create and `actionCreatea` | 179–203 and 359–383 | Session database name is selected. No user-level test in the action | The posted form is saved with `save(false)`. The lines read do not set `employee_id` from the session |

`Document` and `DocumentSearch` each keep their own `setDb`. Setting the search connection does not set the model connection. A view opened by id therefore does not inherit the index action's database selection unless something else set it. What the default connection is was not read. That default is **UNRESOLVED**.

## 3. File handling

Employee uploads use the original file name. The extension is calculated and then not used. The random-name line is commented out.

| Step | Evidence | What was not proven |
| --- | --- | --- |
| Save path | `$_SERVER['DOCUMENT_ROOT'] . '/hrmsdata/documents/emp_uploaded/'` plus `$image->name` | No allow-list of extensions was found in these actions. That is an observed gap, not a demonstrated upload |
| Replace | Update unlinks the stored `file_location`, then saves the new original name | Whether `file_location` can contain a path segment was not tested |
| Delete | `actionDelete` and `actionDeletea` unlink that same folder plus the stored `file_location`, then delete the row | No runtime deletion was performed |
| Validation | `save(false)` skips model validation | The model rules were not audited |
| Audit log | No audit write was found in these actions | Absence in these actions only |

The file-cabinet query on `DocumentsController::actionIndex` is a second list with no employee condition in the lines read. Whether that cabinet is meant to be shared is **UNRESOLVED**. It is not evidence that every document list is shared.

## 4. Findings

| ID | Observed behavior | Proposal | Why it is not a confirmed exploit |
| --- | --- | --- | --- |
| DOC-01 | `DocumentsController` list forces `document.employee_id` to the session employee | **KEEP** that list rule | The action was not executed |
| DOC-02 | `DocumentbyempController` list uses the session employee when the user level is not `clientAdmin` | **KEEP** that list rule for a non-admin | View by id does not repeat the filter |
| DOC-03 | View, update, and delete load by primary key only | **REDESIGN** | A posted or routed id is accepted. No request was issued to prove another employee's file is returned |
| DOC-04 | Create saves the posted model, including whatever `employee_id` the form sends, and stores the original file name | **REDESIGN** | `save(false)` and the path are visible. No file was uploaded |
| DOC-05 | `DocumentController` view, create, update, and delete contain no user-level test. Index allows only `clientAdmin` and does not filter by employee | **REDESIGN** | Missing checks are source facts. Reachability from a menu was not verified |
| DOC-06 | A `clientAdmin` list hides rows uploaded by that username, and one variant also filters `company_id` | **UNRESOLVED** as a business rule. **REDESIGN** as an ownership control, because the id actions ignore it | The purpose of "not my username" was not found |
| DOC-07 | Alias-specific document rules for company ids 24, 20, and 10 were not present in these actions | **UNRESOLVED** | Do not infer a company policy from the file names |

No finding is **REMOVE**. The screens were not shown to be unused. **CONFIGURE** is not used: the differences here are missing checks, not a verified company policy. **ENHANCE** is not used: the id actions do not have a check to improve. They have no ownership comparison.

## 5. Relation to AUD-08

AUD-08 found that profile, personal files, and the studied payslip reads use the session employee. Document lists can do the same. Document view, update, delete, and upload do not. That is a different result, not a contradiction. Personal-files index remains as AUD-08 described. It was not reopened.

SEC-CORE-007, one employee in one company, is the requirement these id actions do not meet in source. This audit does not sign that requirement into a build. The access-model brief and BD-D16 and BD-D17 stay unsigned.

## 6. Next batch

The unread ESS payroll actions named in AUD-08: `actionTaxslip`, `actionPfdeduction`, `actionPfstatement`, `actionNoc`, and `actionSalcer`, limited to whether they keep the session employee. PF content stays deferred under SCOPE-01. After that, one admin PIMS employee update, which AUD-03 did not read.

## 7. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The active control center gained one completed row for this batch. No gate was signed.

AUD-09 SOURCE TRACE COMPLETE FOR THIS BATCH
