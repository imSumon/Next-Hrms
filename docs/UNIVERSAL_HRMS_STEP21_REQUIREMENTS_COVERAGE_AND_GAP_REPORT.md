# Universal HRMS — Step 21 Requirements Coverage and Gap Report

Companion to `docs/UNIVERSAL_HRMS_STEP21_REQUIREMENTS_TRACEABILITY_MATRIX.md`.

Requirements analysis is **partially complete**. Step 21D records the grain: 5 of 25 catalog modules and 8 of 101 submodules have partial behavior evidence. None is functionally analyzed. Policy values remain unsigned. The live catalog comparison remains pending.

No company is approved for employee migration.

## 1. How the counts were calculated

| Count | Method | Result |
| --- | --- | ---: |
| Requirement IDs | Appendix A of `docs/UNIVERSAL_HRMS_REQUIREMENTS.md`, which states each ID once | 166 |
| CORE / CONFIGURABLE / CLIENT EXTENSION / BUSINESS DECISION / DEFERRED | Same appendix | 111 / 32 / 4 / 17 / 2 |
| P0 rows | Same appendix: 47 capability or configuration rows plus BD-D02, BD-D03, BD-D09, BD-D16, and BD-D18 | 52 |
| Catalog modules | Step 21A offline parse of `modules` in `proedgea_payrollmaster.sql` | 25 |
| Catalog submodules | Same parse of `sub_modules` | 101 |
| Catalog controllers | Same parse of `controllers` | 416 |
| Catalog controller rows that pass the three logical link checks | Step 21A section 6. The tables have no foreign key | 397 |
| Backend module directories | Directories under `legacy-reference\hrms\backend\modules` whose names do not start with `00`. Filesystem count from Step 21. Not the catalog | 25 |
| PHP controllers in those directories | `*Controller.php` directly inside each module’s `controllers` folder | 346 |
| ESS controllers | `frontend\modules\ess\controllers` | 24 |

The parent `docs` folder does not contain `UNIVERSAL_HRMS_REQUIREMENTS.md`. The copy used is `legacy-reference\hrms\docs\UNIVERSAL_HRMS_REQUIREMENTS.md`.

The catalog and the filesystem each have 25 entries. They are not the same list. The catalog includes `averydata` and `wppfmanagement`. The directory list includes `appadmin` and `reports`. The dump has 101 submodule rows. A stored count of 99 was not found. Two of the 101 rows have a missing or null parent module. Subtracting them equals 99, and that arithmetic is not evidence of an earlier filter. Step 21B is the superseding note for that claim and for the claim that all 416 relationships were valid. The row inventory remains `docs/UNIVERSAL_HRMS_STEP21A_LEGACY_MODULE_SUBMODULE_INVENTORY.md`. The classification is `docs/UNIVERSAL_HRMS_STEP21B_LEGACY_CATALOG_INTEGRITY_AND_AUDIT_RECONCILIATION.md`.

Backup and “no need” directories were excluded from the filesystem directory count. They are old copies, not extra scope.

## 2. Coverage by functional area

| Area | Status | Why |
| --- | --- | --- |
| Organization and company configuration | Partially analyzed | Boundary and catalog mapping are approved. Three company rows exist. Org tree is not loaded. Branch override is unsigned |
| Employee master and lifecycle | Partially analyzed | Dump census and target tables exist. D1, D2, D3, D4, and D6 are unsigned. `dis` is unexplained |
| Recruitment and onboarding | Not analyzed | `recruitment` is one of the 25 directories. The requirements in-scope list does not name it. Treat it as a possible later scope item, not as a verified legacy workflow |
| Attendance, shifts, rosters, punches | Partially analyzed | Proedge August 2026, VTL September 2026, and Atlas September 2026. Vendor, `area`, and `balid` are unknown. Atlas and CEVA engines were not month-traced |
| Leave and approvals | Partially analyzed | Day effects and correction samples in the studied months. Hourly leave and the full approval chain are not analyzed |
| Overtime and payroll | Partially analyzed | The three studied months separate calculated hours from paid money. Formulas are unsigned |
| ESS and management reporting | Partially analyzed | Requirements exist. A mobile assessment exists. The 24 ESS controllers and the reports module were not page-audited here |
| Security, roles, and audit history | Partially analyzed | Server authorization is a written requirement. The appadmin role screens were not audited |
| Integrations, notifications, administration | Not analyzed, with named unknowns | Punch writer unknown. A missing-punch email was not confirmed. Bill, lunch, document, archive, and PF modules were not behavior-audited |
| Provident fund, PMS, assessments, bills | Not analyzed | Directories exist. They are not in the requirements in-scope list as universal core. PF amounts in one Atlas month are not a module audit |

## 3. Requirements adequately supported by evidence

These are specified, and the legacy contrast that justified them is already audited. The policy value inside them may still be open.

- Company isolation and policy-as-data, rather than an alias.
- Employee identity separate from employee code, with no merge and no silent recode.
- One effective shift per work date, without choosing the winner.
- Raw punch retained, with company taken from the card map.
- Incomplete punch detected, without freezing Present or Absent.
- Correction history in four values, without editing the punch.
- Calculated, payable, and paid overtime kept apart.
- Payroll snapshot that does not follow a later attendance change.
- Payslip read from stored results.
- A lock flag is not proof of close.
- Server-side authorization.
- Migration reconciliation: source count = loaded + approved exceptions.

## 4. Partially analyzed and not analyzed

Step 21A separates catalog inventory from behavior evidence. A module with a catalog row is inventory-covered. Only attendance, leave day-effects, overtime, payroll amounts, employee identity, and company tenancy for the three reference companies have partial behavior analysis. Inventory coverage is not requirements-analysis coverage.

Partially analyzed: attendance, leave day-effects, overtime, payroll amounts, employee identity, and company tenancy for the three reference companies.

Not analyzed as behavior: appadmin, assessment, bills, archive, documents, gfmanagement, hourly leave, lunch, mobile bills, multi-rater, personal files, PF workflows, PMS, proedgedata, recruitment, tonicdata, and most payroll controllers beyond the studied salary rows.

Not applicable to universal core, on present evidence: NMS payslip logic, Atlas Bills, and alias-selected engines. They remain extension slots or legacy-only. That classification is from the architecture and workbook, not a new decision.

## 5. Missing evidence and the next investigation

| Gap | Next investigation, when authorized |
| --- | --- |
| Live catalog confirmation | Step 21C found the local MySQL server not running and did not start it. The `legacy_master` data directory exists. No live row was read. Repeat the Step 21C id list only when the server is already running |
| 19 catalog link exceptions | Classified in Step 21B. Eight null-module rows and three placeholder rows are classified. Three CMA rows still have a null submodule parent. Five controller rows stay unresolved. Do not treat all 416 as linked |
| Step 1A–1E source of the 99-count | No Step 1A–1E report is in either docs directory. Do not reconstruct 99 by dropping submodule ids 36 and 71 |
| Punch writer, `area`, `balid` | A read-only trace of who inserts `all_att_log`. Do not guess a vendor |
| Atlas and CEVA engines | One closed month each, only if the result could add a policy or an extension |
| `dis` | A business definition, or leave it unmapped |
| `EB9091` September attendance and pay | Re-read only if a disposition uses those facts |
| Hourly leave versus day leave | Read the hourly-leave workflow before calling leave analysis complete |
| Notification delivery | Confirm whether the missing-punch email is sent |
| Role and scope model | Audit appadmin only when security implementation is in scope |
| Joiner and leaver pay | A month that is not a full month, before freezing payable days |

Do not repeat the three closed-month attendance audits unless one of those questions is the missing piece.

## 6. Unresolved business decisions

Still unsigned in Step 20R: D1, D2, D3, the D4 group hold, D6, the meaning of `dis`, episode overlap, date order, inactive pay, and D01–D18. D4’s prohibition on merge and silent recode stays approved. BD-D15 stays deferred.

## 7. P0 blockers

| Blocker | Depends on | Effect |
| --- | --- | --- |
| D1 display name | Signed rule, then a disposition for VTL id 437 | Blocks an employee load |
| D3 both flags | Signed rule. 1,186 VTL rows plus Atlas `EB9091` | Blocks episode status |
| D4 group hold | Signed hold, then 14 row dispositions. No merge | Blocks those rows |
| BD-D02 incomplete punch | A company outcome that is not copied from one client | Blocks attendance status rules |
| BD-D03 shift winner | A winner rule for go-forward days | Blocks shift resolution |
| BD-D09 overtime formula | A formula that is not taken from one studied month | Blocks overtime calculation |
| BD-D16 correction fields | An allowed-field list | Blocks the correction form |
| BD-D18 snapshot lines | Which optional facts are stored | Blocks optional snapshot content |
| Reconciliation not run | The decisions above, plus a signed exception register | Blocks migration approval |

D2 and D6 block migration of dates and episodes. They are P1 in the management paper and still block a load.

## 8. First release versus later

First-release candidates, already in the requirements in-scope list, once their gates are signed: company boundary, employee and lifecycle, shift and holiday rules, punch ingestion, attendance day result, leave day mark, correction, approval, overtime split, payroll snapshot and payslip, optional period close, server authorization, and migration of the three reference companies.

Later-phase candidates, because they are directories without a behavior audit and they are outside that in-scope list: recruitment, PMS, PF workflows, assessments, bills, lunch, documents, archive, and client extensions such as NMS and Atlas Bills. This is a scope observation, not a decision to drop them.

## 9. Conclusion

Requirements analysis is **partially complete**.

Evidence for that conclusion:

- The requirements document already classifies 166 IDs and leaves D01–D18 open on purpose.
- Three company months support the attendance, overtime, and payroll contrasts.
- Employee dump findings and the target employee schema exist. The load rules are unsigned.
- Step 21D is the coverage assessment: 5 of 25 modules and 8 of 101 submodules are partially analyzed. Zero are functionally analyzed. 91 submodules are inventory only. Submodules 36 and 71 remain unknown.
- Step 21A documents 25 catalog modules, 101 catalog submodules, and 416 catalog controllers from the master dump. Most of those rows have no behavior audit.
- The filesystem count remains 25 directories and 346 PHP controllers, and that set is not the catalog.
- Nineteen catalog controller rows do not pass the logical module and submodule checks.
- No company is migration-approved.

## 10. Statement

No database was connected. No schema, configuration, or application code was changed. Historical audit reports were not overwritten.

COVERAGE REPORT PREPARED
