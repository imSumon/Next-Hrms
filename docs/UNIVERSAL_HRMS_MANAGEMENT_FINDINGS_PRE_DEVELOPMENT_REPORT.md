# Universal HRMS — Management Findings before Development

Meeting paper. It records what the audits already established, what is still a management decision, and what must be agreed before employee or attendance development proceeds.

No company is approved for production migration. Employee figures below are offline SQL-dump findings, not a live production census.

## 1. Executive summary

Five problems should be settled before full development.

| Problem | Business impact | Why it comes first |
| --- | --- | --- |
| Employee identity is not the employee code | Duplicate codes, empty names, and conflicting active/left flags make attendance and pay attach to the wrong person, or to nobody | The new system already has company records. It does not yet have an approved rule for names, zero-dates, conflicting flags, duplicate codes, or employment history |
| Raw punches are shared and only partly traceable | About 13.9 million punches sit in one shared log with no company key on the traced days. The device vendor is unknown | A wrong company match misstates presence, lateness, and overtime |
| Several attendance engines overwrite the day | The same product calculates Proedge, Viyellatex, and other companies differently, then replaces the stored day. Middle punches and earlier results disappear | Payroll and disputes cannot be reconstructed from the surviving day alone |
| Overtime and pay are not the same number | Calculated hours, approved hours, and money paid do not match in the studied months | Paying from the wrong figure, or changing a day after pay is stored, creates payroll disputes |
| Month locks do not prove a period was closed | Lock tables exist. The studied months were not locked by those tables | A “closed” month can still change unless the new system protects a finalized pay snapshot |

**Verified audit findings.** Dump counts, the shared punch log, the studied months, and the local company records described below.

**Risks inferred from those findings.** A duplicate code or an unmatched punch card can attach time or pay to the wrong person. That outcome is a risk. The audits did not claim it happened for every row.

**Proposed redevelopment requirements.** Stable identity, one calculation engine, immutable punches, separate overtime figures, and a protected pay snapshot. These are recommended controls. They are not a statement that development has started.

**Decisions still awaiting management approval.** Display name, zero-dates, conflicting flags, duplicate codes, employment episodes, and the open attendance decisions D01–D18. None of those values has been selected in this paper.

Local progress that does **not** authorize development of employee migration: three company records now exist for Proedge, Viyellatex, and Atlas. Employee and employment-history tables are empty. D1–D6 remain proposals.

## 2. Employee identity and duplicate codes

Source: Step 20G offline parse of the company SQL dumps. Not a live database census.

| Company | Employee rows | Distinct employee codes |
| --- | ---: | ---: |
| Proedge | 160 | 160 |
| Atlas | 151 | 151 |
| VTL | 1,420 | 1,413 |

Verified exceptions in those dumps:

- VTL has 7 duplicate employee-code groups, 14 rows. The codes are `10024`, `15575`, `132058`, `6119`, `10023`, `10027`, and `10034`. Each code is on two different employee records. Proedge and Atlas had no duplicate codes in these dumps.
- VTL employee id 437, code `PAL1234`, has all three name fields empty.
- Atlas employee id 7175, code `EB9091`, has both “active” and “discontinued” set to 1, with a discontinue date of 2026-08-25.
- Both flags are 1 on 19 Proedge rows, 74 Atlas rows, and 1,186 VTL rows. At VTL that is most of the file. It is a common stored state, not one damaged row.

The employee code is the business number people see. It is not a safe universal identity. The same number can repeat inside one company. A punch card can differ from the employee code. Attendance matching, payroll history, and migration all need the person, the code, and the card kept as separate facts.

Implications:

- Attendance matching on code alone can join two VTL people, or miss a person whose card number is different.
- Payroll history tied only to a code cannot be trusted where two records share that code.
- A person marked both active and discontinued cannot be given a single employment status until management decides the rule. `EB9091` is the named Atlas example. September attendance after the discontinue date is recorded in the closed-month audit and was not re-counted in the later dump audit.
- An empty name cannot be printed on a register or a payslip until a name is supplied or the row is explicitly excluded.

The proposed handling, not yet approved, is: keep every source row, do not merge people, do not silently change a code, and hold duplicate, nameless, and conflicting-status rows until a named reviewer disposes of them.

## 3. Attendance system findings

Sources: the architecture assessment, the Proedge August 2026 and Viyellatex September 2026 audits, the Atlas September 2026 closed-month audit, and the migration workbook. No separate file titled as an attendance-rules matrix was found. The open rules are D01–D18 in the technology decision.

### Shared punch log

**Established.** Traced punches for the studied months are in `legacy_master.all_att_log`, about 13.9 million rows in the audited environment. On the traced days the company column is empty. `area` and `balid` are filled and their meaning is unknown. The in-repo upload paths did not write those traced rows. The external writer is unknown.

**Audited example.** Atlas employee EB9006 on 2026-09-02 has eight punches in the shared log. The company column is empty. The day result kept only the first and last times.

**Impact.** A punch cannot be given to a company from the log alone. Volume makes a wrong match expensive to correct after the fact.

**Recommended control.** Keep each raw punch immutable, with its source and timestamp. Assign the company only through that company’s punch-card map. Hold punches that match nobody or more than one company.

### Punch-card mapping

**Established.** Each company has `emp_code_to_punch_card`. The employee code and the card are different fields. Atlas September had 65 cards: 8 equalled the employee code and 57 did not. The workbook treats a card that maps to two companies, or to none, as an exception.

**Audited example.** EB9006’s September punch identity is card 9006, not the code `EB9006`.

**Impact.** Matching on employee code alone misses or mis-assigns punches.

**Recommended control.** A company-scoped card map with an active period. Unmatched and double-matched cards stay exceptions. They are not guessed.

### More than one calculation engine

**Established.** One codebase selects the attendance engine, status words, shift source, overtime writer, and payslip from the company alias. Proedge and Viyellatex were traced and differ. Atlas and CEVA have separate process actions in source. Those two operated months were not traced. Their policies are unknown.

**Audited contrast, not a universal rule.** Proedge August uses word statuses and the employee’s default shift. Viyellatex September uses letter statuses and a shift calendar. A missing out is Present-plus-text at Proedge and Atlas, and Absent at Viyellatex.

**Impact.** A single future code branch per company would recreate the current product. Companies would keep drifting.

**Recommended control.** One calculation engine. Company differences are policy data, not a separate program.

### Daily result ledger and overwrite

**Established.** `attendance_detail` is the stored day. The processors delete and insert that employee-day. They keep the earliest and latest punch and drop the middle. Earlier versions of the day are often gone and must not be invented.

**Audited example.** Viyellatex card 126065 on 2026-09-02 had five punches. The day stored 08:17:41 and 17:34:36. The middle punches remained only in the raw log.

**Impact.** A dispute about a middle punch cannot be answered from the day result. A reprocess can replace the day the payroll reader saw.

**Recommended control.** Raw punches stay. Each calculation writes a new day-result version. Recalculation does not erase the previous version.

### Corrections and approvals

**Established.** Corrections live in `attendance_approval_file`. Status numbers differ by company. The approver is often a name or an employee code, not a user account. Some corrections change the displayed in or out to the shift boundary. The raw punch is not the thing being edited. A before-image is often missing.

**Audited examples.**

- Viyellatex 2026-09-17, employee 03129487: displayed in became the shift start 09:00:59, while the actual in was 14:58:20.
- Viyellatex 2026-09-13, employee 05000356: displayed out became the shift end, and actual out was 00:00:00. The original out is gone.
- Proedge August, employee 10318: the approval asked for 08:00 and 17:00, and the day stayed Absent with no punches.

**Hypothetical, not claimed as production.** If a later reprocess replaced the displayed times after pay had been calculated, the payslip would no longer match the day. The studied payroll files do not contain an attendance snapshot, so that link cannot be proven from them.

**Impact.** Approvers and employees cannot see what the day was before the change. Boss and grand-boss fields are not a controlled workflow.

**Recommended control.** Store original, requested, approved, and final values, with the user and the time. A correction never edits the raw punch. Which fields may change, and the approval steps, remain open decisions.

### Late, early, leave, incomplete punch, and midnight shifts

**Established.** Late is stored differently by company. Proedge’s Late label appears after roughly 16 minutes. Viyellatex counts from the first minute. Atlas September stores late as seconds after 09:30:59, and a punch at 10:01:44 was Present while 10:04:55 was Late. The exact Atlas threshold was not measured. Early-out is often a label without a stored duration. Incomplete punches are detectable. What they become is not agreed. Atlas September shifts did not cross midnight. The target still allows a shift that does.

**Audited example.** Atlas missing in or out is stored as Present plus text such as “Out punch missing”. Viyellatex stores a missing out as Absent.

**Impact.** The same physical event can be Present in one company and Absent in another. Copying either rule as the product standard would be wrong.

**Recommended control.** Detect the incomplete day. Let each company configure the outcome, the shift winner, grace, and late start. Do not fill those values until management decides them.

### Month lock

**Established.** `attendance_month_lock` and `salary_month_lock` exist. Proedge’s tables were empty for the August study. Viyellatex’s last attendance Yes was September 2020 and its last salary Yes was June 2023, not the studied September 2026. Atlas had one attendance Yes for January 2025, not September 2026. A Yes flag is not proof that the studied month was closed.

**Impact.** Staff can believe a month is closed while the result row can still be replaced.

**Recommended control.** Period close is an explicit event. A finalized payroll snapshot does not change when attendance is later recalculated. Who may reopen a period is still deferred.

## 4. Overtime and payroll risks

The audits separate three ideas that the legacy data mixes.

| Figure | What the studied months show |
| --- | --- |
| Calculated overtime | Proedge August stores seconds after shift end on 50 rows for 3 employees. Viyellatex has 173 September rows with hours 1 through 6, each on a day that also has an attendance correction, while a code path blanks overtime for that company. Atlas September has no calculated overtime on the attendance rows |
| Approved or payable overtime | No separate payable store was found for these months. An “eligible for overtime” flag does not match who was paid at Proedge, and is off for every Viyellatex employee even where hours exist |
| Paid overtime | Proedge August paid an Over Time head of 6,158.25 across 6 employees. That money is not the attendance seconds. Viyellatex and Atlas September have no overtime pay head in the studied salary rows |

Payroll for these months is the stored `employee_salary` amounts. The table has no copy of the attendance inputs. The payslip reads those amounts. Historical pay must be loaded as stored money, not recalculated from a new attendance rule.

Risk, supported by that gap: if attendance is changed after payroll is calculated, nothing in the legacy salary row records which attendance result was used. The new system’s rule, already accepted as architecture and not yet built for payroll, is that a finalized snapshot keeps the figures it used. A later attendance change does not alter that snapshot. Changing it requires an authorized reopen. Whether a company uses a cutoff, and who may reopen, are still open.

## 5. Recommended controls for approval

These are requirements to approve. They are not approved business values.

| Control | Class | Notes |
| --- | --- | --- |
| Stable employee identity, separate from the employee code | CORE | Already the target rule. A new identity key per person. The code is an attribute |
| Employee code unique inside one company, including after a person leaves | CORE | Already decided for the target table. A code is not silently reused or rewritten |
| Quarantine duplicate, empty-name, and conflicting-status rows until a person disposes of them | BUSINESS DECISION | The hold is proposed as D1, D3, and D4. Not signed |
| Immutable raw punches, with a controlled withdrawal and an audit trail | CORE | The punch is not edited by a correction or a recalculation |
| One attendance calculation engine | CORE | Company differences are not separate programs |
| Company attendance policies | CONFIGURABLE | Grace, shift winner, incomplete-punch outcome, late start, leave pay, and absence pay are D01–D07. Values are empty |
| Controlled correction, recalculation, approval, and period close | CORE for the mechanism. BUSINESS DECISION for the values | Fields, steps, cutoff, and reopen are D13–D17. Reopen authority stays deferred |
| Calculated, approved, and payable overtime kept apart | CORE for the split. BUSINESS DECISION for the formula | Eligibility, formula, rounding, approval, and rate are D08–D12 |
| Finalized payroll snapshot does not follow a later attendance change | CORE | Draft pay may be rerun. Final pay changes only through an authorized reopen |
| Company-wise reconciliation and a source key on every migrated row | CORE | Source count equals loaded rows plus approved exceptions. Not yet run |
| Alias-selected engines, salary keys 5 and 26 as tenants, and uninterpreted `area` / `balid` | LEGACY ONLY | Not carried forward as product behavior |
| Client payslip layouts and modules such as Atlas Bills | CLIENT EXTENSION | Off until that company enables them |

## 6. Management decision register

No row below is approved by this paper. The owner column is the function that should decide. It is not an assignment of a named person.

| ID | Business question | Proposed direction, not a decision | Risk if unresolved | Recommended owner | Priority |
| --- | --- | --- | --- | --- | --- |
| D1 | How are the three name fields joined, and what happens when all are empty? | Trim usable parts, join with one space, hold a row with no name. VTL id 437 is the known case | Payslips and registers have a blank or invented name | HR operations | P0 |
| D2 | What is a stored `0000-00-00` date? | Keep it as its own exception. Do not invent a date and do not treat it as an ordinary empty date | Joining and leaving dates are wrong or hidden | HR operations | P1 |
| D3 | What status applies when active and discontinued are both yes? | Do not infer working, inactive, or left. Hold the status. Includes Atlas `EB9091` and 1,186 VTL rows | Most of VTL is classified by a guess | HR operations | P0 |
| D4 | How are duplicate employee codes resolved? | Hold the whole group. Keep both rows. Do not merge and do not renumber | Attendance and pay attach to the wrong person | HR operations | P0 |
| D6 | When does one current employee row become an employment episode? | One episode only when the dates and an approved status support it. Do not invent earlier jobs from a department change | History is fabricated or everyone has no employment record | HR operations | P1 |
| BD-D02 | What is the outcome of an incomplete punch? | Detect it. Do not copy Present or Absent from one company | The same missed punch is paid differently without a decision | HR operations | P0 |
| BD-D03 | Which shift wins when roster and default differ? | Keep both candidates. Do not pick a winner in migration | Days are assigned to the wrong shift | HR operations | P0 |
| BD-D09 | How is overtime calculated? | Keep calculated hours separate from money. Do not adopt a legacy formula | Overtime is paid from the wrong quantity | HR and Finance | P0 |
| BD-D11 | Is overtime approved separately from attendance? | The approval link stays optional until decided | Hours beside a time correction are treated as approved overtime | HR and Finance | P0 |
| BD-D12 | What rate turns payable overtime into money? | Paid money stays a snapshot amount. No rate is implied | A print formula becomes the payroll rule | Finance | P1 |
| BD-D16 | Which attendance fields may a correction change? | The field list is not fixed yet | Corrections rewrite status, shift, or pay inputs without authority | HR operations | P0 |
| BD-D17 | What is the approval sequence? | No boss or grand-boss step is seeded | Approvals cannot be audited to a user | HR operations | P1 |
| BD-D13 | Does attendance have a cutoff? | A legacy Yes flag is not a cutoff | Results change after managers believe the month is closed | HR operations | P1 |
| BD-D14 | Does payroll have a close? | A stored salary is not a close policy | Pay is recalculated from later attendance | Finance | P0 |
| BD-D15 | Who may reopen a closed period? | Deferred. No reopen role is defined | Either nobody can correct a true error, or anyone can | Management | P1 |
| BD-D18 | Which extra lines belong on the pay snapshot? | None are required yet | Optional facts are invented during migration | Finance | P2 |

D01 grace, D04 late start, D05 early-out pay, D06 leave pay, D07 absence pay, D08 overtime eligibility, and D10 rounding are also open. They are P1 policy values. They should not be copied from Proedge or Viyellatex.

## 7. Development priorities and gates

| Stage | What it means here | State |
| --- | --- | --- |
| Audit | Employee dumps, three company months, and the architecture assessment are written | Complete for the evidence cited above. Atlas and CEVA engines were not month-traced. The punch vendor is unknown |
| Design approval | Identity rules D1–D4 and D6, and the attendance decisions in section 6 | Not approved |
| Implementation readiness | Company records exist locally. Employee tables exist and are empty. Attendance and payroll tables are not built in this step | Company seed only. Not readiness to load people or time |
| Migration approval | Reconciliation run, exceptions signed, quarantine at zero | Not granted for any company |

Recommended order:

1. Confirm employee identity and the three company mappings. The local company records for catalog 24, 20, and 10 exist. The identity rules do not.
2. Approve the core attendance and overtime decisions in section 6, especially incomplete punch, shift winner, overtime calculation and approval, and payroll close.
3. Define punch traceability and the employee-card match, including the exception for unmatched cards.
4. Establish calculation, correction, and version rules before any attendance screen is built.
5. Define what payroll copies at calculation time, and that a finalized snapshot stays fixed.
6. Validate migration with a company-wise count: source rows equal loaded rows plus approved exceptions.
7. Start implementation of a domain only after that domain’s gate is approved.

Building screens on the legacy alias rules would lock the new product to today’s exceptions.

## 8. Evidence limits

- Employee counts are dump findings from Step 20G, not a live census.
- The 13.9 million punch figure is the architecture assessment’s description of the audited shared log, not a new count.
- September attendance and missing September pay for `EB9091` were not re-read after the closed-month audit.
- No standalone attendance-rules-matrix document was found. D01–D18 in the technology decision are the decision list.
- This paper did not connect to a database and did not run a migration.

MANAGEMENT REPORT COMPLETE
