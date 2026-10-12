# Universal HRMS — AUD-04 Punch Writer, `area`, and `balid` Audit

Status: **SOURCE AUDIT COMPLETE**. The external writer of the closed-month rows remains **UNKNOWN**. No vendor is named. No database was queried.

PUNCH-APPROVED stays in force: a correction does not edit the raw punch, and recalculation writes a new day-result version. This audit does not approve a deletion exception.

## 1. Executive findings

The repository contains two writers of `all_att_log`. Neither writes `area` or `company`.

| Writer | What it stores | What it does not store |
| --- | --- | --- |
| `AllattlogController::actionUpload` | `date`, `emp_code` from a bracket-delimited file | `area`, `balid`, `company`, `update_datetime`, `location`, `remark` |
| `DefaultController::actionTransferlocationdata` | `date`, `emp_code` (punch card), `update_datetime` (today’s date), `balid` = `location_access_data.id` | `area`, `company`, `location`, `remark` |

The closed-month rows already on record had `area` filled and `update_datetime` null. The transfer writer always sets `update_datetime`. The file upload never sets `area`. Those traced rows were not produced by either writer. The code that did produce them is not in this repository. No device API, scheduled job, or synchronization client that inserts `all_att_log` was found.

`area` is never assigned in PHP. Its meaning stays UNKNOWN. It is not established as a device, a site, a branch, or a company.

`balid` has one demonstrated meaning, and only on the transfer writer: it copies `location_access_data.id`. Day processing does not read `balid`. A populated `balid` on a row this writer did not create stays UNKNOWN.

Company attribution of a shared punch is the company database’s `emp_code_to_punch_card`, already recorded in the workbook. These writers do not store catalog ids 24, 20, or 10, and they do not store the company column.

`location_area` on `location_access_data` is a different column. It is not `all_att_log.area`.

## 2. Writers

Searched PHP, JavaScript, SQL, XML, and JSON under `legacy-reference\hrms` for `all_att_log`, excluding nothing that matched. Backup trees and the dated `12-7-2020` copies do not reference the table. No console command references it.

### 2.1 File upload into the shared log

`backend\modules\appadmin\controllers\AllattlogController.php`, `actionUpload`, from line 132.

Input: each line of a file under `uploads/logs/`, split on `]`. The left token, after replacing `[`, `-`, and `/`, becomes `date`. The right token, trimmed, becomes `emp_code`. There is no punch-card lookup and no company lookup.

Destination: `AllAttLog` on the default connection. `setDb` on that model is commented out, so this is not the session company database.

Validation: if a row with that `date` and `emp_code` already exists, the line is skipped and the text “already has on db (dup)” is echoed. `save(false)` skips the model validators, including the unique rule on `date` plus `emp_code`. There is no check that the date parses. There is no transaction. The file is deleted, and `exit()` runs after the first file in the directory.

The model rules require `date` and `emp_code` and declare uniqueness of that pair. The rule is application validation. This source does not contain the table’s unique index. `save(false)` does not run the rule. The explicit `find()` is what skips a duplicate on this path.

`actionCreate` and `actionUpdate` can save `date`, `emp_code`, and `update_datetime` through the safe attributes. They do not assign `area` or `balid`. `actionDelete` deletes the row by id. AUD-03 already recorded that this controller does not test `user_level`.

### 2.2 Transfer from location access

`backend\modules\attendance\controllers\DefaultController.php`, `actionTransferlocationdata`, lines 2561–2582.

Input: `location_access_data` on `Yii::$app->db` where `att_log` is 0 and `company_name` equals the session `companydbname`. For each row, `emp_code_to_punch_card` is read on the session company connection where `emp_code` equals `employee_id`. The stored punch value is `ltrim` of `punch_card_no`. The stored time is `time_date`.

Destination: `INSERT IGNORE` into `all_att_log` on `Yii::$app->db` (the default connection, not the company alias):

- `date` = `time_date`
- `emp_code` = the punch card number
- `update_datetime` = the calendar date of the run
- `balid` = `location_access_data.id`

Then `att_log` is set to 1 on that location row. The update runs after `execute()` with no test of whether a row was inserted. A second run will not select the row, because `att_log` is no longer 0. If the insert was ignored, the location row is still marked transferred. There is no transaction and no level test in this function.

If the punch-card lookup returns nothing, the next line reads `punch_card_no` on that result. There is no branch for a missing map.

The rows this action copies are created by the location APIs, not by a device client in this repository:

- `frontend\controllers\ApiController.php` `actionSavelocationdata` inserts `company_name`, `employee_id`, `employee_name`, `session_string`, `time_date`, `checkin`, `location_area`, `lat`, `lon`.
- `frontend\controllers\ApinewController.php` `actionSavelocationdata` inserts those fields plus `remark` and `confirmed` 0, or updates the latest unconfirmed row.

Those inserts do not write `all_att_log`.

### 2.3 Company-database `att_log`, not the shared log

`AttlogController::actionUpload` and `actionUploadOld` write `att_log` through `AttLog::setDb` on the session company connection. `AttlogismlController::actionUpload` writes `AttLogIsml` on that connection, deletes an existing `date` plus `emp_code` first, and copies `emp_id_no` from `emp_code_to_punch_card`. `SiteController::actionUpload` writes the `actions` catalog. None of these is an `all_att_log` writer.

The workbook already recorded that company `att_log` was empty in the VTL September trace. This audit does not reopen that month.

### 2.4 Readers that are not writers

`AttendancedetailController` process actions, `ApuattendanceComponent`, `EmpshiftcalendarController`, and the unused support-service download view select `MIN(date)` and `MAX(date)` from `all_att_log` by `emp_code` inside a punch window. They delete and rebuild `attendance_detail`. They do not insert, update, or delete `all_att_log`.

`actionProcessproedge` sets `$connect` to `Yii::$app->db` and sets `EmpCodeToPunchCard` to the session company database. The log read uses the punch card number, not the employee code and not a company id.

## 3. `area` and `balid`

| Use | File | Operation | Derived value | Meaning in this code |
| --- | --- | --- | --- | --- |
| `all_att_log.area` | No assignment in the repository | Not written | Not derived | UNKNOWN. Not a configured map |
| `all_att_log.balid` | `DefaultController.php` line 2576 | Insert | `location_access_data.id` | The location-access row id, on this writer only |
| `all_att_log.balid` | `AllattlogController.php` lines 258–269 | Copy onto `all_att_log_old`, then delete the live row | The value already stored | Copy. Does not define the value |
| `all_att_log.location`, `all_att_log.remark` | `AttendancedetailController` process queries | Read, on the non-weekend branch | Columns selected with `MIN(date)` | Used as day-location text when present. The weekend and holiday branches set location and remark to empty. These are not `area` |
| `location_access_data.location_area` | API inserts, and process loops for `aesbd`, `aesbdos`, and `proedge` | Write on the API. Read onto `attendance_detail.location` | Posted `location_area` | A location-access label. Not copied into `all_att_log.area` |

`AllAttLog` and `AllAttLogOld` do not list `area`, `balid`, or `company` in rules or labels. `actionArchivelogs` still assigns `balid` before `save(false)`, so a physical column can be copied without being in the rules. That is not a definition of the column.

Prior traced samples remain evidence of stored text, not of meaning: Atlas area text `Atlas`, a Proedge traced area `VSL1(4)`, a VTL traced area `Head Office`. This source never assigns those strings. They stay uninterpreted. They are not a device id, a branch id, or `source_event_id`.

`actionProcessatlas` and `actionProcessceva` read `location_access_data` with `company_name='proedge'`. `actionProcessaesbd` uses `aesbd`. `actionProcessaesbdos` uses `aesbdos`. That hard-coded name affects the location text copied onto the day. It does not write `area`.

## 4. Employee and company attribution

| Path | Employee link | Company link |
| --- | --- | --- |
| File upload | The file token is stored as `emp_code`. No card map | None. Default connection. `company` is not set |
| Location transfer | `employee_id` is looked up in the session company’s `emp_code_to_punch_card`. The card number is what is stored | `company_name` on the location row must equal the session database alias. The punch row itself has no company column |
| Day process | `emp_code_to_punch_card.emp_code` = `employees.emp_id_no` in the session company database. The log is filtered by `punch_card_no` | The company database is the session alias. The log query has no company predicate |

Catalog tenant ids are not read or written. A dump-local company id is not written on the punch. The null `company` column on the traced days remains unused, as the workbook already says.

Collision: the shared log distinguishes rows by `emp_code` plus `date`. `emp_code` here is a punch card number taken from whichever company map the transfer used, or a raw file token. Two company maps that contain the same card number would select the same log rows, because the log has no company predicate. This source does not prove that such an overlap exists. It also does not prevent it.

A missing card on the transfer path is not turned into an exception row. The action dereferences the lookup.

## 5. Duplicates, delay, and deletion

| Case | Behavior in source |
| --- | --- |
| Same `date` and `emp_code` on the file upload | Skipped after a `find()`. The existing row is not updated |
| Same pair on the transfer insert | `INSERT IGNORE`. A duplicate is ignored only when the database raises a duplicate-key error. This repository does not declare that key. The location row is still marked `att_log=1` |
| Repeated transfer | The `att_log=0` filter stops a second copy. It also stops a retry after an ignored insert |
| Out-of-order or delayed punches | Not rejected at insert. A later process rebuilds the day from `MIN(date)` and `MAX(date)` inside the shift window. Middle punches stay in the log and stay off the day, which the closed-month audits already recorded |
| Invalid timestamp | Not tested. `save(false)` and the SQL insert store the string |
| Missing card map | Not handled on the transfer path |
| Reprocessing | Rebuilds `attendance_detail`. Does not edit the log |
| Day-result correction | AUD-02. The `proedge` and `vtl` approval path does not rewrite the day. The Atlas and CEVA grand-boss path rewrites the day. None of those paths update `all_att_log` |
| Raw deletion | `actionDelete` deletes the log row. `actionArchivelogs` copies `id`, `date`, `emp_code`, `update_datetime`, and `balid` to `all_att_log_old` and then deletes the live row. No transaction wraps the copy and the delete |

Same-second drop of an event remains a prior finding about the stored log. It was not re-tested here.

## 6. Classification

| Finding | Class |
| --- | --- |
| Raw punch is kept, and a correction does not edit it | CORE. Already PUNCH-APPROVED. Legacy delete and archive are not an approved exception |
| Card map inside one company database | CORE for attribution. Already in the workbook. Not a new rule |
| File upload and location transfer as ingestion paths | LEGACY ONLY. They are not the closed-month writer |
| `balid` = `location_access_data.id` on the transfer insert | LEGACY ONLY. Do not use it as `source_event_id` or as a device id for other rows |
| `area` on `all_att_log` | UNKNOWN |
| `balid` on rows this transfer did not write | UNKNOWN |
| External writer of the traced rows | UNKNOWN. Not a vendor requirement |
| `location_area` overlaid onto the day, including `company_name='proedge'` inside the Atlas and CEVA process actions | LEGACY ONLY |
| Who may withdraw or delete a raw punch | BUSINESS DECISION. Unsigned. The legacy delete actions are not that decision |
| Null `company` as tenant 10, 20, or 24 | Not supported. Do not carry it forward |

## 7. Proposed acceptance checks

These checks are not approved requirements.

- A loaded punch keeps its source timestamp. Processing does not replace it.
- A punch is attached to a company only through that company’s card map. A null company column is not read as a tenant.
- `area` and a `balid` that is not the location-access id stay on an uninterpreted note. They are not a device, a branch, or `source_event_id`.
- An unmatched card is an exception. It is not inserted under another company.
- A second delivery of the same source event does not create a second event and does not erase the first.
- Withdrawal, if an owner signs it, marks the event. It does not delete the row and does not change `event_at`.
- The unknown external writer is not given a product name.

## 8. Files inspected

- `backend\modules\appadmin\controllers\AllattlogController.php`
- `backend\modules\appadmin\models\AllAttLog.php` and `AllAttLogOld.php`
- `backend\modules\appadmin\models\LocationAccessData.php`
- `backend\modules\attendance\controllers\DefaultController.php` (`actionTransferlocationdata`)
- `backend\modules\attendance\controllers\AttlogController.php` (company `att_log` upload)
- `backend\modules\attendance\controllers\AttendancedetailController.php` (process reads, card map, location overlay)
- `backend\modules\attendance\models\AttLog.php` and `EmpCodeToPunchCard.php`
- `backend\modules\proedgedata\controllers\AttlogismlController.php` (company log, not `all_att_log`)
- `frontend\controllers\ApiController.php` and `ApinewController.php` (`actionSavelocationdata`)
- `backend\controllers\SiteController.php` `actionUpload` (catalog actions, not punches)
- Repository search for `all_att_log` and `balid`
- Workbook and closed-month statements for the traced `area` texts, null `company`, and null `update_datetime`. Those months were not re-read.

Not inspected as current writers: `00backup00`, `00no need modules00` beyond the support-service select, and the dated attendance controller copies. They do not reference `all_att_log`.

## 9. Still open

The program that wrote the traced rows, including their `area` text and their `balid` values, is not in this repository. Whether `all_att_log` has a unique key on `date` and `emp_code` was not read from a database. Overlap of punch card numbers across company maps was not counted. Withdrawal authority stays unsigned.

## 10. Next audit

Next planned source audit: **AUD-05**, Atlas and CEVA attendance engines, only where a result can add a policy or an extension. DB-01 still waits until MySQL is already running.

## 11. Statement

No database was connected. MySQL was not started. No application code, schema, migration, or environment file was changed.

AUD-04 SOURCE AUDIT COMPLETE
