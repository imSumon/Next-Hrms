# Universal HRMS — Step 20H Employee Migration Blocker and Decision Register

Documentation only. No database connection, dump import, schema change, or application change was made. No company is migration-approved.

Classes used in this register:

- **VERIFIED** — established from the named dump parse or from the implemented target catalog described in an earlier step report.
- **DOCUMENTED ONLY** — an approved audit states it. This step did not re-read that source.
- **BUSINESS DECISION REQUIRED** — an authorized product decision is still required. Options are listed. None is selected here.
- **TECHNICAL VERIFICATION REQUIRED** — a fact still to be read from a named local source.
- **MIGRATION EXCEPTION** — a source row or row set that stays undisposed. Automatic merge, recode, or status assignment is prohibited.

Step 20E left Atlas and VTL physical column names unresolved. Steps 20F and 20G closed those names from the dumps: `employees.id`, `emp_id_no`, `joining_date`, `disconrinue`, and `discontinue_date`. This register does not reopen them.

## 1. Documents reviewed

- `docs/UNIVERSAL_HRMS_STEP20B_EMPLOYEE_LIFECYCLE_DESIGN_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20C_EMPLOYEE_LIFECYCLE_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20D_EMPLOYEE_LIFECYCLE_INTEGRITY_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20E_EMPLOYEE_MIGRATION_MAPPING_AND_RECONCILIATION_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20F_LEGACY_EMPLOYEE_SOURCE_SCHEMA_VERIFICATION.md`
- `docs/UNIVERSAL_HRMS_STEP20G_LEGACY_EMPLOYEE_IDENTITY_AND_EXCEPTION_AUDIT.md`
- `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` sections 3 and 17
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_VALIDATION.md`
- `docs/UNIVERSAL_HRMS_PROEDGE_AUGUST_2026_DRY_RUN_REPORT.md`
- `docs/UNIVERSAL_HRMS_THIRD_REFERENCE_COMPANY_CLOSED_MONTH_AUDIT.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` sections 4.3 and 4.4, as cited by Steps 20B and 20E
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`, as cited by Step 20E

## 2. Verified evidence summary

Dump findings below are from Step 20G’s offline parse of:

- `legacy-data/companies/proedgea_newproedgehrm.sql`
- `legacy-data/companies/proedgea_atlas.sql`
- `legacy-data/companies/vtl/proedgea_vtlhrm.sql.gz`

They are **VERIFIED** for those files. They are not a live-database census. The stopped Laragon copies were not compared (Step 20F).

| Source dump | Employee rows | Distinct employee codes | Class |
| --- | ---: | ---: | --- |
| Proedge | 160 | 160 | **VERIFIED** |
| Atlas | 151 | 151 | **VERIFIED** |
| VTL | 1,420 | 1,413 | **VERIFIED** |

Also **VERIFIED** in Step 20G:

- VTL has 7 duplicate `emp_id_no` groups, 14 rows. The codes are `10024`, `15575`, `132058`, `6119`, `10023`, `10027`, and `10034`. Each group is two `employees.id` values. Proedge and Atlas have 0 duplicate groups.
- VTL `employees.id` 437, code `PAL1234`, has `emp_first_name`, `emp_mid_name`, and `emp_last_name` all empty strings.
- `active` = 1 and `disconrinue` = 1 on 19 Proedge rows, 74 Atlas rows, and 1,186 VTL rows.
- Atlas code `EB9091` is `employees.id` 7175. Both flags are 1. `discontinue_date` is `2026-08-25`. `joining_date` is `2021-05-02`. `emp_unit` is 26.
- Dump-local `companies.id` is 5 in the Proedge file (name Pro-edge Associates Limited) and 5 in the VTL file (name Viyellatex Group). Atlas dump-local `companies.id` is 26 (name Atlas Copco Bangladesh Ltd.). The same number in two databases is not one universal tenant.
- `old_id` is `Yes`, `No`, empty, or `NULL`. It is not unique and is not an identity key.
- `employees` has no `company_id`. The only employee index in each `CREATE TABLE` is `PRIMARY KEY (id)`.
- `employment_history` is a text column. No employment-episode table was confirmed.

**DOCUMENTED ONLY**, not re-read here:

- Catalog company 24, Pro-edge Associates Limited, alias `proedge`, database `proedgea_newproedgehrm`, `previous_db_id` 5. Printed in the August 2026 dry-run report from `legacy_master.companies`.
- Catalog company 5 named as UL VS Bangladesh Ltd. in that same dry-run paragraph. The catalog row is not reprinted there.
- Catalog company 20, Viyellatex Group, alias `vtl`, database `proedgea_vtlhrm`. Mapping validation, from its prior catalog query.
- Catalog company 10, Atlas Copco Bangladesh Ltd. Catalog company 26 named as Grameen Telecom Trust, a different company. Third-company audit.
- Workbook rule: salary `company_id` 5, 5, and 26 remap to catalog 24, 20, and 10 and are not tenants. Step 20E marks that rule **APPROVED**. Target company rows are not loaded.
- EB9091 September attendance and the absence of a September salary row. Third-company audit. Step 20G did not re-read those month facts.

Target rules already approved and implemented, cited so this register does not redecide them:

- Target `employees.id` is a new ULID. Legacy `employees.id` is `source_key`. `emp_id_no` is copied to `employee_code` and is not the identity. `UNIQUE (company_id, employee_code)` covers every row. Separation does not release the code. Rejoin is a new episode on the same identity and the same code.
- Episode status tokens are `Open`, `inactive`, and `separated`. The active token is `Open`.
- One `Open` episode per employee. An `Open` episode has no `last_working_date`. A `separated` episode may have a null `last_working_date`.
- Employee and episode reconciliation are separate: `SOURCE COUNT = LOADED + APPROVED EXCEPTIONS`. Quarantined rows are not on the right-hand side and must be zero before migration approval.

## 3. Business decision register

These items need an authorized decision. This register does not choose an option.

### BD-01 — `display_name` composition

| | |
| --- | --- |
| Question | How do `emp_first_name`, `emp_mid_name`, and `emp_last_name` become one required `display_name`, and what happens when every component is unusable? |
| Evidence | Schema and workbook require a non-empty display name and an empty-name exception. They do not define composition. Yii `getName()` joins the three fields with spaces and is not an approved migration rule. Step 20G: Proedge 0 rows with no usable name, Atlas 0, VTL 1 (`id` 437, `PAL1234`). Empty components are common and are empty strings, not `NULL`. **VERIFIED** census. Rule **BUSINESS DECISION REQUIRED**. |
| Why it matters | `display_name` is `NOT NULL`. A load cannot invent a name. VTL id 437 cannot load until a name exists or the row is an approved exception. |
| Type | **BUSINESS DECISION REQUIRED** |
| Options | A. Use the three columns in that order, drop `NULL`, empty, and whitespace-only parts, join the rest with one space, and exception a row with nothing left. B. Use only `emp_first_name`, and exception a row whose first name is unusable even when another part has text. C. Require a reviewer-supplied name for every legacy row. D. Join all three with spaces even when a part is empty, matching `getName()`. |
| Risks | A changes no letters and still needs a reviewer for id 437. B drops middle and last names that are the only usable text on some rows. C puts every employee in review. D can store a string of spaces, which satisfies `NOT NULL` and fails the empty-name exception in substance. |
| Next action | Record one option, or a stated variant, as an approved rule. |
| Blocks | Dry run and final approval. The employee load writes `display_name`. |

### BD-02 — `0000-00-00` dates

| | |
| --- | --- |
| Question | Is MySQL `0000-00-00` a missing date, a rejected row, or something else? |
| Evidence | Step 20G counted it separately from `NULL`. Joining date zero: Proedge 1 (employee id 1), Atlas 3, VTL 61. Joining date `NULL`: Proedge 0, Atlas 0, VTL 6. Leaving date zero: Proedge 1, Atlas 0, VTL 81. Target `joining_date` is `date NOT NULL` (Step 20C). The approved rule does not invent a date. No document says whether the zero token is “missing”. |
| Why it matters | A zero token has no approved representation in the target `date` column. Folding it into `NULL` without a decision would hide the census Step 20G kept separate. |
| Type | **BUSINESS DECISION REQUIRED** |
| Options | A. Treat `0000-00-00` as a missing date: identity may load, the episode is an exception until a reviewer supplies a real date or approves exclusion of the episode. B. Exclude the employee and the episode until a reviewer supplies a real date. C. Keep a distinct exception reason, `zero-date`, separate from SQL `NULL`, with the same hold on the episode. |
| Risks | A and C still need a reviewer before that episode loads. B holds the identity as well, including Proedge id 1. Storing `0000-00-00` in the target `date` column is not an available option under the implemented type. |
| Next action | Choose A, B, or C for both `joining_date` and `discontinue_date`. |
| Blocks | Dry run and final approval wherever a zero date would be written into `joining_date` or `last_working_date`. |

### BD-03 — Both `active` and `disconrinue` are 1

| | |
| --- | --- |
| Question | What episode status, if any, is stored when both flags are 1? |
| Evidence | Step 20G: 19 Proedge, 74 Atlas, 1,186 VTL. Atlas id 7175, `EB9091`, is in the Atlas set, with `discontinue_date` 2026-08-25. Step 20E already quarantines this pair: no automatic `Open`, `inactive`, or `separated`. Both-true rows with a `NULL` leaving date: Proedge 0, Atlas 5, VTL 137. |
| Why it matters | VTL’s both-true set is most of the file. An automatic status would classify the majority of VTL episodes. The standing rule forbids inferring `inactive` from an unclear pair. |
| Type | **BUSINESS DECISION REQUIRED** |
| Options | A. Keep the Step 20E quarantine: the identity may load, the episode stays undisposed, and no status token is written. B. Authorize `separated` only where `discontinue_date` is a real calendar date, and quarantine the rest. C. Require a per-row disposition before any both-true episode is written. |
| Risks | A leaves 1,186 VTL episodes quarantined, which blocks final approval until each has a disposition, and it blocks a dry run that requires every episode to load. B would be a new rule and would still leave the null-date subset undisposed. C is the same hold as A with an explicit reviewer step. Assigning `inactive` is not an option under the standing prohibition. |
| Next action | Confirm A, or approve B or C in writing. Do not assign `inactive`. |
| Blocks | Episode dry run for these rows, and final approval while any of them is quarantined. Identity load of the same person can proceed only under the separate employee equation. |

### BD-04 — Discontinued, not active, leaving date absent

| | |
| --- | --- |
| Question | When `active` is 0, `disconrinue` is 1, and the leaving date is missing, may the episode be `separated` with a null `last_working_date`? |
| Evidence | Step 20E: the workbook allows an empty leaving date, and it does not say the discontinued flag alone is enough. The pair is unresolved there. Flag pair `0` / `1` counts from Step 20G: Proedge 91, Atlas 18, VTL 1. The intersection of this pair with a null or zero leaving date was not counted. |
| Why it matters | `separated` with a null `last_working_date` is physically allowed. Using it for this flag pair is not yet authorized. |
| Type | **BUSINESS DECISION REQUIRED** |
| Options | A. Quarantine the episode until a reviewer accepts `separated` without a date or supplies a date. B. Approve `separated` and a null `last_working_date` for this flag pair whenever `joining_date` is a real date. |
| Risks | A delays those episodes. B applies one status to every such row without a leaving date. |
| Next action | Choose A or B after TV-04 reports how many rows are in the intersection. |
| Blocks | Episode mapping for that intersection. It does not, by itself, block rows that already match an approved pair. |

### BD-05 — How many episodes a current employee row may create

| | |
| --- | --- |
| Question | What is loaded for employment history when no episode table is confirmed? |
| Evidence | Step 20E: one current legacy row supports at most one episode for the known joining and leaving values, and it does not reconstruct earlier rejoins. `employment_history` is text (Proedge 51 non-empty, VTL 11, Atlas 0). Service-book and organogram tables were not adopted as episodes (Step 20F). Department, designation, and location changes do not create episodes. That prohibition is **APPROVED**. |
| Why it matters | Parsing the text, or inventing rejoins, would create episodes the source has not structured. Loading nothing leaves every person without an episode. |
| Type | **BUSINESS DECISION REQUIRED** |
| Options | A. Adopt the Step 20E single current span: at most one episode when an approved flag pair and a real joining date exist. Do not parse `employment_history`. B. Load identities only, and quarantine every episode until a structured history source is approved. C. Parse `employment_history` or the service-book text into episodes. |
| Risks | A preserves one span and leaves earlier employment out. B loads no episodes. C has no approved parser or approval policy. |
| Next action | Confirm A, or explicitly replace it with B. C needs a separate approved parser before it can be used. |
| Blocks | Any episode dry run. Option A is the current design statement and still needs to be confirmed as the history rule for this load, because the text column and the service-book table could otherwise be mistaken for history. |

### BD-06 — Episode date rules left undecided in Step 20B

| | |
| --- | --- |
| Question | May episode dates overlap, must `last_working_date` be on or after `joining_date`, and may `inactive` store `last_working_date`? |
| Evidence | Step 20B marks all three **UNDECIDED**. The implemented table has one `Open` episode per employee and forbids `last_working_date` only while status is `Open`. No overlap exclusion and no date-order check were added. Step 20C and Step 20D record that omission as intentional. |
| Why it matters | A dry run that writes one episode per current row does not need an overlap rule. A later rejoin, or a both-true rule that writes `inactive` with a date, does. |
| Type | **BUSINESS DECISION REQUIRED** |
| Options | Leave the three items undecided and do not add checks. Or approve one or more checks before any load that can create two episodes or an `inactive` row. |
| Risks | Adding a check now would change the approved physical schema. Leaving them open means a future second episode can overlap or end before it starts unless a later decision adds the check. |
| Next action | Keep them out of the employee dry run. Decide them before any load that writes a second episode or uses `inactive`. |
| Blocks | Final approval of a multi-episode or `inactive` load. Does not block a single-span dry run that never writes `inactive` and never writes a second episode. |

### BD-07 — Pay effect of `inactive`

| | |
| --- | --- |
| Question | What pay result does `inactive` have? |
| Evidence | Step 20B: LIFE-CORE-006 and schema 4.4 leave the pay effect unchosen (D06/D07). No pay column exists on `employment_episodes`. |
| Why it matters | Payroll meaning is open. Employee identity and the tokens `Open` and `separated` do not require this answer. |
| Type | **BUSINESS DECISION REQUIRED** |
| Options | Decide inside D06/D07 before any payroll load. Do not add a column in the employee dry run. |
| Risks | Using `inactive` as a pay instruction before D06/D07 would invent a pay rule. |
| Next action | Leave it on the D06/D07 list. |
| Blocks | Payroll interpretation of `inactive`. Does not block an employee identity dry run that does not write `inactive`. |

## 4. Technical verification register

These are evidence gaps. They are not business decisions.

### TV-01 — Catalog rows 5, 10, 20, 24, and 26

| | |
| --- | --- |
| Question | Do the local catalog rows still show the names, aliases, database names, and `previous_db_id` values cited in the audits? |
| Evidence | The mapping rule is **APPROVED** in the workbook and Step 20E. The row text is **DOCUMENTED ONLY**. These company dumps do not contain `legacy_master.companies`. Their local `companies.id` values are 5, 5, and 26, which is a different table from the catalog. |
| Why it matters | Database-local id 5 is Proedge in one dump and Viyellatex in another. Atlas local id 26 is the Atlas file’s own company row. Catalog 24, 20, and 10 are the approved target tenants. Catalog 5 and catalog 26 are documented as other companies. Those two number spaces must stay separate. A dry-run gate that requires a verified company mapping is not met by the dump-local ids alone. |
| Source and method | Local `legacy_master` only, after that server is already running and the connection is confirmed local and read-only. Select `id`, `company_name`, `db_alias`, `db_name`, `previous_db_id` for ids 5, 10, 20, 24, and 26. Do not start MySQL for this register. Do not open production. |
| Blocks | The dry-run checklist item “verified company/source mapping”. It does not reopen the approved rule that 5 and 26 are not tenants. |

### TV-02 — Dump file versus Laragon copy

| | |
| --- | --- |
| Question | Do `legacy_proedge`, `legacy_atlas`, and `legacy_vtlhrm` match the three dump files used in Step 20G? |
| Evidence | Step 20F found the data directories and did not read them. The server was not running. Step 20G counts are dump counts. |
| Why it matters | A dry run must name its extract. If the extract is the dump, the Step 20G counts are the source counts. If the extract is Laragon, those counts are not yet the source counts. |
| Source and method | When the local server is already up: read-only session, `information_schema` for `employees`, then the same aggregate definitions as Step 20G. No writes. No production. |
| Blocks | Calling the Step 20G counts a census of the Laragon copies. It does not block a dry run whose declared source is the dump files themselves. |

### TV-03 — September facts for Atlas `EB9091`

| | |
| --- | --- |
| Question | Are the September 2026 attendance rows and the absence of a September salary row still present for `employees.id` 7175? |
| Evidence | Flags, code, and `discontinue_date` are **VERIFIED** in the Atlas dump. The month facts are **DOCUMENTED ONLY** in the third-company audit. |
| Why it matters | The both-flags conflict is already established without those month facts. The month facts matter only if a disposition uses attendance after `2026-08-25` or the missing September pay as evidence. They are not required to prove that both flags are 1. |
| Source and method | Offline parse of the Atlas dump, limited to attendance and salary rows whose employee key is 7175 or code `EB9091`, counting rows in September 2026 and not printing pay amounts or personal fields. Or a later read-only local query with the same limit. Not required before BD-03 option A. |
| Blocks | A disposition that depends on the month facts. Does not block BD-03 option A. |

### TV-04 — Leaving date on the `0` / `1` flag pair

| | |
| --- | --- |
| Question | How many `active` = 0 and `disconrinue` = 1 rows have a null leaving date, a zero leaving date, or a real leaving date? |
| Evidence | Pair counts are **VERIFIED**. The leaving-date split for that pair was not counted. Both-true with a null leaving date was counted. |
| Why it matters | BD-04 applies only to the missing-date subset. |
| Source and method | Re-parse the three dumps offline with the Step 20G field rules. No database. |
| Blocks | The size of BD-04. The decision itself remains BD-04. |

### TV-05 — Meaning of `dis`

| | |
| --- | --- |
| Question | What does `employees.dis` mean in each company? |
| Evidence | Step 20G: `int DEFAULT '0'`, no index. Values are only `NULL`, `0`, and, on two Proedge rows, `1`. The Yii label is `Earn Leave Balance` for aliases `vtl`, `vtlw`, and `vtlnms`, and `Dis` otherwise. VTL’s stored values are `0` or `NULL`, which does not show a leave balance. There is no approved target column. |
| Why it matters | The column cannot support identity. Mapping it into the employee or episode load would invent a target field. |
| Source and method | Offline read of writers of `employees.dis` in the legacy application. A database is not required to finish that reading. If the code still does not define it, leave the meaning unresolved and omit the column from the employee load. |
| Blocks | Nothing in the identity or episode dry run, because no target field is approved. It would block any later request to load `dis` as a business attribute. |

### TV-06 — `companies.source_system`

| | |
| --- | --- |
| Question | What value, if any, is stored on the company row’s `source_system`? |
| Evidence | Step 20E: the workbook names `source_key` as the catalog id and does not name `source_system` for `companies`. The column is nullable (Step 20A). Employee `source_system` values `legacy_proedge`, `legacy_vtlhrm`, and `legacy_atlas` are already specified. |
| Why it matters | Company traceability is weaker while the column is empty. Employee traceability does not depend on it. |
| Source and method | A short decision or a documented convention. No new source system needs to be queried to know the column is nullable. |
| Blocks | Company-row traceability if a later gate requires `source_system`. Does not block employee `source_key`. |

## 5. Migration exception register

Principles already approved. This step adds no exceptions and disposes none.

- Do not merge identities.
- Do not recode a duplicate `emp_id_no`.
- Do not use the employee code as the stable identity. `source_key` is `employees.id`. The target primary key is a new ULID.
- Do not infer `inactive` from an ambiguous flag pair.
- Do not create an episode from a department, designation, or location change.
- A missing or ambiguous value stays a traceable exception. An undisposed row is quarantined, not an approved exception.
- Duplicate source rows count as two source rows.

| ID | Source | Condition | Rows | Class | Disposition |
| --- | --- | --- | --- | --- | --- |
| EX-01 | VTL | Duplicate `emp_id_no` groups listed in section 2 | 7 groups, 14 rows | **MIGRATION EXCEPTION** | Process is already approved in Step 20E: both source keys stay in the source count, neither loads until a reviewer disposes each row. No disposition exists yet. |
| EX-02 | VTL | `id` 437, code `PAL1234`, all three name fields empty | 1 | **MIGRATION EXCEPTION** | Empty name. Cannot load until BD-01 is decided and a reviewer supplies a name or approves exclusion. |
| EX-03 | Proedge, Atlas, VTL | Both flags 1 | 19, 74, 1,186 | **MIGRATION EXCEPTION** | Episode quarantined under Step 20E until BD-03 is decided. Includes Atlas id 7175, `EB9091`. |
| EX-04 | Proedge | `id` 1, `emp_id_no` text `emp_id_no`, flags `0`/`0`, joining date `0000-00-00`, `emp_unit` 0 | 1 | **MIGRATION EXCEPTION** | Dry-run template row, confirmed in the dump. Reviewer decides whether it is a person. Flags `0`/`0` are not stored as `inactive`. |
| EX-05 | Proedge | Flags `0`/`0` | 6, including EX-04 | **MIGRATION EXCEPTION** | Step 20E: no episode status. Do not store `inactive`. |
| EX-06 | All three | `joining_date` `NULL` or `0000-00-00` | `NULL`: 0, 0, 6. Zero: 1, 3, 61 | **MIGRATION EXCEPTION** | Episode held. Zero versus `NULL` waits on BD-02. |
| EX-07 | Proedge, Atlas, VTL | Flags `0`/`1` with a missing leaving date | Count **TECHNICAL VERIFICATION REQUIRED** (TV-04) | **MIGRATION EXCEPTION** | Held until BD-04. Rows in this pair that have a real joining date and a real leaving date follow the approved `separated` rule. |

Proedge and Atlas have no duplicate-code groups in these dumps. That is a dump result, not a permanent waiver if another extract differs.

## 6. Dry-run readiness checklist

A controlled dry run means a non-production load into the local target, with quarantined rows still allowed to exist as reported exceptions. Final migration approval is stricter: quarantined count is zero, and an independent check repeats both equations.

| Requirement | Dry run | Final approval | Now |
| --- | --- | --- | --- |
| Company/source mapping verified for the declared extract | Required. Dump-local ids 5 and 26 are not enough. Catalog 24, 20, 10 must be the tenant statement, with TV-01 done or the prior catalog extract explicitly accepted as the verification. | Same, plus target `companies` rows loaded with those catalog ids in `source_key`. | **TECHNICAL VERIFICATION REQUIRED** for the catalog text. Rule itself is approved. Target companies are not loaded. |
| Display-name rule and invalid-date rule | BD-01 and BD-02 decided | Same | **BUSINESS DECISION REQUIRED** |
| Duplicate codes and both-flags | Disposition process is already approved. A dry run may load only rows outside EX-01 and may leave EX-03 quarantined if BD-03 option A is confirmed. | Every EX row disposed. Quarantine = 0. | **MIGRATION EXCEPTION** undisposed |
| Deterministic identity | New ULID, `source_key` = legacy `employees.id`, `employee_code` = exact `emp_id_no`, `source_system` = `legacy_proedge`, `legacy_atlas`, or `legacy_vtlhrm` | Same | **VERIFIED** as a rule. Not executed. |
| Employment history and episodes | BD-05 confirmed. Approved flag pairs only. No text parsing. No attribute-change episodes. | Same, and BD-04 closed for any `0`/`1` row that is loaded as `separated` | **BUSINESS DECISION REQUIRED** |
| Record-level exception register | One row per quarantined or approved exception, keyed by source system and source key | Same, and each row has a reviewer disposition | Not built |
| Reconciliation contract | Employees and episodes counted separately. Duplicate codes count as two source rows. | `SOURCE COUNT = LOADED + APPROVED EXCEPTIONS` for each company and each of the two equations. Quarantine = 0. | Defined in Step 20E. **Not executed.** |
| Independent validation | The dry run records both equations. | A second pass repeats them from the register and the target keys. | **Not executed.** |

Neither gate is met.

## 7. Recommended resolution sequence

1. Decide BD-01, BD-02, BD-03, and BD-05. These four determine whether any employee or episode row can be written.
2. Run TV-04 offline from the dumps, then decide BD-04.
3. Run TV-01 when a local read-only `legacy_master` session is available. Until then, keep catalog 5 and catalog 26 as documented other companies, and keep dump-local 5 and 26 out of `companies.source_key`.
4. Build the exception register for EX-01 through EX-07. Do not load rows while building it.
5. Decide the declared extract (these dumps, or the Laragon copies after TV-02).
6. Only then authorize a non-production dry run of identities that already have a display name and a unique code, with episodes limited to approved flag pairs and real joining dates.
7. Leave BD-06 and BD-07 until a load needs a second episode, `inactive`, or pay.
8. Run TV-03 only if a both-true disposition uses September attendance or pay.
9. Leave TV-05 closed for this load: do not map `dis`.

## 8. Items explicitly not approved

- Migration of Proedge, Atlas, or VTL.
- Any automatic identity merge or employee-code recode.
- Use of `emp_id_no`, `old_id`, dump-local `companies.id`, `emp_unit`, or salary `company_id` as the target tenant or the target employee primary key.
- `inactive` for both-true, both-false, or any other unresolved flag pair.
- Episodes from `employment_history` text, the service-book table, or department, designation, or location changes.
- A display-name formula, a zero-date treatment, or a both-true status. Step 20G’s composition suggestion is an option under BD-01, not a decision.
- Treating Step 20G counts as the current live database.
- Payroll, attendance, leave, or assignment loads.

BUSINESS DECISIONS REQUIRED
