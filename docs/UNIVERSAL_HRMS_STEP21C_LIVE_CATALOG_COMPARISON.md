# Universal HRMS — Step 21C Live Catalog Comparison

Status: **LIVE COMPARISON NOT PERFORMED**. The local MySQL server was not running. It was not started.

No controller, submodule, module, grant, or aggregate count was read from `legacy_master`. Every live comparison below is **UNVERIFIED**.

## 1. What was checked

Read:

- `docs/UNIVERSAL_HRMS_STEP21A_LEGACY_MODULE_SUBMODULE_INVENTORY.md`
- `docs/UNIVERSAL_HRMS_STEP21B_LEGACY_CATALOG_INTEGRITY_AND_AUDIT_RECONCILIATION.md`
- `docs/UNIVERSAL_HRMS_STEP21_REQUIREMENTS_COVERAGE_AND_GAP_REPORT.md`

The matching copies under `legacy-reference\hrms\docs` are the same documents.

Availability check on 2026-10-11:

| Check | Result |
| --- | --- |
| TCP 3306 | No listener |
| TCP 3307, 3308, and 33060 | No listener |
| Windows service whose name contains `mysql`, `maria`, or `laragon` | None listed |
| Process named `mysql` or `mariadbd` | None listed |
| Data directory `D:\laragon\data\mysql-8.4\legacy_master` | The directory exists |
| Client directory `D:\laragon\bin\mysql\mysql-8.4.3-winx64` | The directory exists |

The data directory is not a query result. Table files were not opened and were not treated as rows. No client connection was attempted, because no local server was listening. Production was not contacted. Credentials were not printed.

## 2. Aggregate counts

| Table | Offline dump, Step 21A | Live `legacy_master` |
| --- | ---: | --- |
| `modules` | 25 | UNVERIFIED |
| `sub_modules` | 101 | UNVERIFIED |
| `controllers` | 416 | UNVERIFIED |

## 3. Requested ids

The dump values below are the Step 21A and Step 21B findings. The live column is unverified for every id.

### 3.1 Controllers

| id | Dump finding from Steps 21A and 21B | Live result |
| ---: | --- | --- |
| 122 | `module_id` 10, submodule 36. Module 10 is absent. Unresolved | UNVERIFIED |
| 123 | `module_id` 10, submodule 36. Module 10 is absent. Unresolved | UNVERIFIED |
| 136 | `module_id` NULL, submodule 52, parent module 5. Classified | UNVERIFIED |
| 276 | `module_id` NULL, submodule 30, parent module 4. Classified | UNVERIFIED |
| 287 | `module_id` NULL, submodule 74, parent module 1. Classified | UNVERIFIED |
| 300 | `module_id` NULL, submodule 74, parent module 1. Classified | UNVERIFIED |
| 325 | `module_id` NULL, submodule 38, parent module 12. Classified | UNVERIFIED |
| 351 | `module_id` NULL, submodule 84, parent module 12. Classified | UNVERIFIED |
| 352 | `module_id` NULL, submodule 84, parent module 12. Classified | UNVERIFIED |
| 365 | `module_id` NULL, submodule 54, parent module 5. Classified | UNVERIFIED |
| 205 | Both foreign ids NULL. Unresolved | UNVERIFIED |
| 213 | Both foreign ids NULL. Unresolved | UNVERIFIED |
| 214 | Both foreign ids NULL. Unresolved | UNVERIFIED |
| 267 | `module_id` 4, submodule 71, parent NULL. Partially classified | UNVERIFIED |
| 268 | `module_id` 4, submodule 71, parent NULL. Partially classified | UNVERIFIED |
| 270 | `module_id` 4, submodule 71, parent NULL. Partially classified | UNVERIFIED |
| 339 | Placeholder `cname` / `ctitle`, both ids 0 | UNVERIFIED |
| 433 | Placeholder `cname` / `ctitle`, both ids 0 | UNVERIFIED |
| 472 | Placeholder `cname` / `ctitle`, both ids 0 | UNVERIFIED |

### 3.2 Submodules

| id | Dump finding | Live result |
| ---: | --- | --- |
| 36 | Name `Notification`, parent module 10, and that module row is absent | UNVERIFIED |
| 71 | Name `CMA`, parent `module_id` NULL | UNVERIFIED |

### 3.3 Modules

| id | Dump finding | Live result |
| ---: | --- | --- |
| 7 | Absent, and unreferenced in the assignment module-id columns | UNVERIFIED |
| 10 | Absent. Referenced by submodule 36 and controllers 122 and 123 | UNVERIFIED |
| 11 | Absent, and unreferenced in the assignment module-id columns | UNVERIFIED |
| 14 | Absent, and unreferenced in the assignment module-id columns | UNVERIFIED |
| 15 | Absent, and unreferenced in the assignment module-id columns | UNVERIFIED |
| 17 | Absent, and unreferenced in the assignment module-id columns | UNVERIFIED |
| 26 | Absent. Two `company_modules` rows, one user-module row, and three controller grants still reference it | UNVERIFIED |

Grants and assignment rows identified in Step 21B were not re-read from a live database.

## 4. Classification

No comparison reached MATCH, DIFFERENCE, LIVE-ONLY, or DUMP-ONLY. There is no live row to agree or disagree with the dump.

Step 21A and Step 21B do not need a superseding addendum. Their conclusions remain dump findings. This step does not confirm them and does not change them.

## 5. What remains

The next live comparison is the same id list, run with read-only queries after the local MySQL server is already running. Starting the server was outside this step.

## 6. Statement

No database connection was opened. MySQL was not started, stopped, or restarted. No schema, data, application code, or environment file was changed.

LIVE COMPARISON NOT PERFORMED
