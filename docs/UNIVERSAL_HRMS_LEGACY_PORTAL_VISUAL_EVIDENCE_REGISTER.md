# Universal HRMS — Legacy Portal Visual Evidence Register

Date: 2026-10-16

Status: **BLOCKED PENDING AUTHORIZED LEGACY PORTAL ACCESS.** No screenshot has been captured. VIS-01 through VIS-12 remain planned captures. A row without a file is not visual verification. Source-code findings stay separate from anything that still needs a screen. The catalog of 25 modules, 101 submodules, and 416 controller records is the inventory baseline. It is not proof that every page has been seen or functionally audited.

## 1. Capture rules

- Filename pattern: `PORTAL_MODULE_PAGE_STATE.png`, with a numeric suffix when names collide. Examples: `ESS_LUNCH_Index_List.png`, `ESS_MOBILEBILL_Index_List.png`.
- Store files only in `next-hrms\docs\legacy-portal-visual-evidence\`.
- That folder exists. A count of its files on 2026-10-16 is 0. No PNG was written.
- Do not submit a form, approve, reject, or delete a row in order to take a picture.
- Do not photograph passwords, salary amounts, bank accounts, TINs, or passport numbers. If a page shows them, record the route and skip the file.
- Do not start MySQL and do not connect to production to obtain a picture.
- A visible button is not proof that the controller checks ownership. A controller action is not proof that a menu shows it.

## 2. Why this batch has no files

Ports 80, 443, and 3306 were already listening. The new application was listening on `127.0.0.1:8000`, and its frontend on `[::1]:5174`. Those are not an authorized legacy Admin or ESS session.

No legacy login was available. The portal was not opened. MySQL was not started by this task and was not queried. The blocker is the missing authorized legacy session, not a missing folder. The later consolidation did not add a session and did not add a file. Visual capture remains blocked. The new application on port 8000 is not the legacy portal.

## 3. Coverage key

| Status | Meaning in this register |
| --- | --- |
| Source-traced, not visual | A completed AUD document read the controller. No screenshot exists |
| Visual only | A screenshot exists and the controller was not traced. None in this batch |
| Both | Screenshot and source trace. None in this batch |
| Inaccessible | No authorized session, so the page was not opened |
| Not inventoried here | Not selected for this capture plan |

## 4. Planned captures

These rows are plans. The filename column is the name to use later. The path is empty because the file is not present.

| ID | Portal | Module | Page | Route from source | Planned filename | Audit | Visual | Source trace | Sensitive data | Blocker |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| VIS-01 | ESS | ESS home | Landing and navigation | `/ess` | `ESS_HOME_Navigation.png` | — | Not captured | Not traced as a page | Skip any personal header that shows salary | No authorized session |
| VIS-02 | ESS | Profile | Own profile | ESS default profile | `ESS_PROFILE_View.png` | AUD-08 | Not captured | Source-traced | Profile can show personal fields. Capture only with a test employee | No authorized session |
| VIS-03 | ESS | Payroll | Own payslip | ESS payroll payslip | `ESS_PAYROLL_Payslip.png` | AUD-08 | Not captured | Source-traced | Do not retain salary figures | No authorized session |
| VIS-04 | ESS | Documents | Own document list | ESS documents index | `ESS_DOCUMENTS_List.png` | AUD-09 | Not captured | Source-traced | Do not open a file that contains an identity document | No authorized session |
| VIS-05 | ESS | Dashboard | Team list and one detail shell | ESS dashboard index | `ESS_DASHBOARD_List.png` | AUD-11 | Not captured | Source-traced | Detail pages print salary in source. Do not capture the salary panel | No authorized session |
| VIS-06 | ESS | Overtime | Request list | ESS otmanagement index | `ESS_OVERTIME_List.png` | AUD-11 | Not captured | Source-traced | None expected on the empty form | No authorized session |
| VIS-07 | ESS | Admin | Employee list | `/hrms/ess/admin` | `ESS_ADMIN_Employee_List.png` | AUD-12 | Not captured | Source-traced | The source grid prints a password column. Do not capture that column | No authorized session |
| VIS-08 | ESS | Hourly leave | Own leave list | `/ess/hourlyleave` | `ESS_HOURLYLEAVE_List.png` | AUD-13 | Not captured | Source-traced | None expected on the list shell | No authorized session |
| VIS-09 | ESS | Ticketing | Ticket form | ESS ticketing index | `ESS_TICKETING_Form.png` | AUD-14 | Not captured | Source-traced | The ESS view folder was not in the tree | No authorized session |
| VIS-10 | ESS | Lunch | Enrollment list | `/hrms/ess/lunchmanagement` | `ESS_LUNCH_Index_List.png` | AUD-15 | Not captured | Source-traced | The list is filtered in the view. A picture would not prove the delete action checks the employee | No authorized session |
| VIS-11 | ESS | Mobile bill | Application list | `/hrms/ess/mobilebillmanagement` | `ESS_MOBILEBILL_Index_List.png` | AUD-16 | Not captured | Source-traced | The list shows limit amounts. Capture the shell only, or skip the amount columns | No authorized session |
| VIS-12 | Admin | PIMS | Employee update | PIMS employees update | `ADMIN_PIMS_Employee_Edit.png` | AUD-10 | Not captured | Source-traced | The form contains salary fields. Do not capture a filled salary form | No authorized session |

## 5. Source and screen notes already known without a picture

- AUD-15: the lunch daily view shows Delete only for status `New`. `actionDelete` does not test status.
- AUD-16: the mobile-bill view shows Delete only for status `0` and Print for every listed row. `actionDeletea` and `actionPrint` do not compare the row with the session employee.
- AUD-17: the bill index view shows Delete only when status is `New`. `actionDelete` does not test status or employee. The approval views filter by approver. The approval actions update by `bid` without that comparison. This is source evidence, not a captured screen.
- AUD-12: the salary-certificate grid prints `username.password`. A future capture must not include that column.
- AUD-14: `actionDeleteleave` deletes a leave row, not a ticket. A ticket screen, if one appears, would not by itself prove that delete target.

These are source observations. They are not visual confirmations.

## 6. Not in this capture plan

Admin modules other than the one PIMS update, ESS bill approval, provident fund, recruitment, and PMS are not rows above. They stay inventory-only until a later batch names them. Ninety-one submodules were already recorded as inventory-only. This register does not change that count.

## 7. Next capture batch

When an authorized local legacy session exists, capture VIS-01, VIS-10, and VIS-11 only: navigation, the lunch list shell, and the mobile-bill list shell. Do not submit either form. Do not photograph amounts or passwords. Do not start MySQL for that batch if it is not already running, and do not connect to production.

VISUAL EVIDENCE REGISTER CREATED. NO SCREENSHOTS CAPTURED.
