# Universal HRMS — Requirements Traceability Reconciliation

Stage 2. The row-level matrix remains `docs/UNIVERSAL_HRMS_STEP21_REQUIREMENTS_TRACEABILITY_MATRIX.md`. This file records discrepancies and the one new ID. It does not replace that matrix and it does not approve a policy.

## 1. ID change

| Previous | Current | Reason |
| --- | --- | --- |
| 166 IDs: 111 CORE, 32 CONFIGURABLE, 4 CLIENT EXTENSION, 17 BUSINESS DECISION, 2 DEFERRED | 167 IDs: 111, 33, 4, 17, 2 | FY-CONF-001 added. No existing ID was renumbered |
| D01–D18 | Unchanged | Financial-year months are FY-BOUNDARY, not a new BD-D number |

FY-CONF-001: the company configures financial-year start and end months. Calendar year, company financial year, payroll period, and leave year stay separate. Example ranges are not company settings. The months are unsigned.

## 2. Audit-to-requirement links

| Requirement | Audit evidence | Classification stays | Wording risk |
| --- | --- | --- | --- |
| LEAVE-CORE-004 | F-01. Hourly leave is outside this path | CORE for day leave | Do not add hourly leave to the shall list |
| LEAVE-CONF-001, BD-D06 | F-03, F-05, F-06 | Value unsigned | Status 3 is not the universal code |
| APP-CORE-001, BD-D17 | F-04, F-06 | Steps unsigned | Boss and grand-boss columns are not the signed chain |
| SEC-CORE-001 | F-10, F-11, F-12 | CORE capability | Legacy gaps are not the design |
| PUNCH-APPROVED, PUNCH-WITHDRAW | F-14, F-19 | Immutability approved. Withdrawal unsigned | Legacy delete does not amend the approved rule |
| ATT-CONF grace, BD-D01, BD-D05 | F-21 | CONFIGURABLE | 16 and 31 are not defaults |
| BD-D02, BD-D03 | F-23 | Unsigned | Present-plus-text and Absent are company outcomes |
| OT-CORE-001 through 003, BD-D08–D12 | F-24 | Split approved as architecture. Formulas unsigned | Do not freeze a fraction or a zero-pay result |
| PAY-CONF-001, BD-D07 | F-25, F-26 | Unsigned | Divisor 30 and omitting `disconrinue=1` are not defaults |
| PER-CORE and PER-CONF, BD-D13, BD-D14 | F-20 lock, closed months | Capability CORE. Use unsigned | A Yes flag in another month is not a close |
| FY-CONF-001 | F-28 | CONFIGURABLE, value unsigned | Leave year is not the financial year |

No duplicate requirement IDs were found in the appendix count. The matrix still describes some rows as partial analysis. Those rows now cite AUD-02 through AUD-06 where Stage 2 updates were applied earlier. They were not rewritten again here.

## 3. Statements that are not current

| Statement | Treatment |
| --- | --- |
| Requirements section 46, architecture and development not started | Left in the requirements file. Stage 2 added a note that the control center is current |
| Step 21D, zero functionally analyzed catalog rows | Still true as a catalog grain. It does not cancel AUD-01 through AUD-06 |
| Control center before Stage 2, requirements only in the legacy docs folder | Corrected. Both folders now hold the requirements file |
| 166 IDs | Superseded by 167 after FY-CONF-001 |

## 4. Approval

CORE capabilities in the requirements text are written requirements. They are not a new management signature from Stage 2. Business-decision rows stay unsigned except where Step 20R section 2 already lists an item in force. FY-CONF-001 is a written configurable capability. Its months are not approved.

Implementation of attendance, leave, overtime, and payroll tables is not started. Foundation and lifecycle tables from Steps 20A–20O are the exception, and they are not an employee load.
