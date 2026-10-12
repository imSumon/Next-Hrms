# Universal HRMS — Technical Decision Approval and Environment Readiness

Date: 2026-10-09

Step: 19A

Status: approved technical direction recorded. Local environment inspected. No database, table, migration, application, or data load was created.

This document records implementation-technology approval. It does not choose D01–D18 business values.

## Current database decision

The approved target database is **PostgreSQL 18.x**. That is the physical implementation target.

MySQL is the legacy production source only. Legacy production MySQL stays read-only. No production change, and no HRMS target database or table, is authorized on production.

The Step 19A inspection below recorded PostgreSQL as not installed. That inspection is historical. A later local validation, re-checked on 2026-10-09 without creating a database, found:

| Check | Result |
|---|---|
| PostgreSQL version | 18.6 |
| Windows service | `postgresql-x64-18`, running, start type Automatic |
| Port | 5432, listening |
| `psql` | `C:\Program Files\PostgreSQL\18\bin\psql.exe`, client reports PostgreSQL 18.6. Not on PATH. Login was already confirmed and was not repeated here |
| `pg_config` | Present. Reports PostgreSQL 18.6 |
| PHP | 8.3.30 |
| PHP configuration file | `D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini` |
| PDO PostgreSQL | `pdo_pgsql` and `pgsql` are loaded |
| PHP to PostgreSQL | Connection already confirmed OK. Not repeated in this document update |

No HRMS database, role, or table was created. `new_proedge_hrms` was not created.

## 1. Executive Summary

The Step 19 recommendations that this step was asked to approve are now **approved technical decisions**: PostgreSQL 18.x, Laravel, React + Vite, Flutter for Android and iOS, Nree as reference only, a 26-character ASCII ULID, PostgreSQL exclusion constraints for effective-date overlap, database triggers for raw-punch and finalized-payroll immutability, and Laravel migrations plus controlled migration tooling. MySQL remains the legacy production source only.

The web product is a Universal HRMS application with future areas for Admin, HR, Manager, and Employee Web / ESS. The first web area to implement is Employee Web / ESS. The backend and database stay universal, multi-company, and multi-role.

At the time of the Step 19A inspection, PostgreSQL was not installed, `psql` was not on PATH, no PostgreSQL Windows service was present, and `pdo_pgsql` was commented out. Laravel, the React application, and a new Flutter client were not created. That environment finding is historical. The current local validation is in the section "Current database decision" above.

The Step 19A gate was **NOT READY FOR STEP 20** because PostgreSQL was absent. The current Step 20 gate is `docs/UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_READINESS.md`.

## 2. Previous Step 19 Findings

`docs/UNIVERSAL_HRMS_TARGET_TECHNOLOGY_PHYSICAL_IMPLEMENTATION_DECISION.md` classified the engine, framework, ULID storage form, overlap mechanism, and immutability mechanism as recommendations. Its gate was **NOT READY FOR STEP 20** because those items were not yet approved and PostgreSQL was not installed.

Step 19B, `docs/UNIVERSAL_HRMS_NREE_MOBILE_APPLICATION_ASSESSMENT.md`, classified the existing Nree applications as Option B: reusable Flutter reference, not the Universal HRMS mobile client.

This step converts the listed Step 19 recommendations into approved technical decisions and re-checks the machine. It does not implement the schema that Step 18 and Step 19 withheld.

Items that Step 19 recommended and this step does **not** approve, because they were not in the approval list:

| Item | Step 19 recommendation | This step |
|---|---|---|
| TD-07 money and duration SQL types | `numeric` for money; integer seconds for durations | Still unspecified. A scale or unit must not become BD-D01 or BD-D10 |
| TD-08 `value_payload` | `jsonb` | Still a recommendation |
| TD-09 timestamps | `timestamptz` | Still a recommendation |
| TD-11 child `company_id` match | composite foreign keys | Still a recommendation |
| TD-14 active employee-code uniqueness | partial unique index; predicate still needs the employment-episode status | Still a recommendation |
| TD-15 queue | database queue, no Redis | Still a recommendation. Not required to install PostgreSQL |
| TD-17 project root | `D:\Project -All\Proedge HRMS\next-hrms\` | Still only a recommended location. Not created |
| TD-18 install PostgreSQL | install after engine approval | Engine is now approved. Installation is still not approved |

## 3. Approved Technology Decisions

These are **approved technical decisions**. They are not business-rule decisions.

| Area | Decision |
|---|---|
| Database engine | PostgreSQL |
| Backend | Laravel |
| Web frontend | React + Vite |
| Mobile | Flutter for Android and iOS |
| Nree | Reference and reusable source only. Not the Universal HRMS mobile client |
| ULID storage | 26-character ASCII text |
| Effective-date overlap | PostgreSQL exclusion constraints |
| Raw punch immutability | Database triggers |
| Finalized payroll immutability | Database triggers |
| Migration tooling | Laravel migration framework plus controlled migration tooling |
| Initial web implementation | Employee Web / ESS |
| Future web areas | Admin, HR, Manager |
| Backend and database scope | Universal HRMS, multi-company and multi-role. Not an ESS-only backend |

Laravel is the application and migration home. React + Vite is a separate web client. Flutter is a separate mobile client. Neither client narrows the database.

The exact Laravel, React, and Vite package versions below are the recommended baseline for the later project-creation step. They are not installed in this step.

## 4. Web Architecture and Phasing

Target web architecture:

```text
React + Vite
├── Admin          (later phase)
├── HR             (later phase)
├── Manager        (later phase)
└── Employee Web / ESS   (first implementation priority)
```

Employee Web / ESS is the first screen area. Admin, HR, and Manager remain part of the same web application and can be added later without a second backend or a second database.

The Laravel React starter kit, which uses Inertia, is not the approved shape. The approved shape is a Universal HRMS API with a separate React + Vite client.

No Admin, HR, Manager, or ESS page was created.

## 5. Mobile Architecture and Nree Position

Future mobile client: a new Flutter application for Android and iOS.

Nree is reference only (Step 19B Option B).

| Tree | Role |
|---|---|
| `D:\Project -All\Nree\nree.mobile` | Existing production-oriented hybrid client. Reference only |
| `D:\Project -All\Nree\nree.mobile.v2` | Existing mock-data client. Reference only |
| New Universal HRMS Flutter client | Not created in this step |

Neither Nree tree was opened for modification, and neither dependency set was changed. Nree is not connected to a new backend. iOS compilation is not possible on this Windows workstation; a Mac with Xcode is a later mobile prerequisite, not a Step 20 schema prerequisite.

## 6. Local Environment Inventory

Inspected 2026-10-09. No installer was run.

| Component | Result |
|---|---|
| OS | Microsoft Windows NT 10.0.26200 (Windows 11 25H2, build 26200.9550) |
| Shell | PowerShell |
| Git | 2.49.0.windows.1 |
| PHP | 8.3.30, ZTS, Visual C++ 2019 x64, built 2026-01-13. Binary: `D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe` |
| PHP extensions present and loaded | `curl`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `tokenizer`, and the other modules reported by `php -m` |
| PHP PostgreSQL extensions | `php_pdo_pgsql.dll` and `php_pgsql.dll` exist. Both `extension=` lines are commented in `php.ini`. Not loaded |
| Composer | 2.8.8 (2025-04-04), using the PHP 8.3.30 binary above |
| Node.js | v22.22.3 |
| npm | 12.0.0 |
| PostgreSQL client | Not installed. `psql` is not on PATH |
| PostgreSQL server | Not installed. No Windows service matching `postgres` |
| Flutter | 3.44.7, stable channel, `C:\src\flutter`, framework revision 84fc5cbb22 (2026-07-17) |
| Dart | 3.12.2 (stable) |
| Android SDK | Installed at `C:\Users\ASUS\AppData\Local\Android\Sdk`. Version reported by Flutter: 36.1.0. Platforms present: android-31, android-34, android-35, android-36. Build-tools 36.1.0. Emulator 36.6.11.0 |
| Android licenses | Not all accepted (`flutter doctor`) |
| Java on PATH | Java SE 1.8.0_501 |
| Android Studio JDK | OpenJDK 17.0.7, bundled at `C:\Program Files\Android\Android Studio\jbr`. Flutter uses this JDK for Android builds |
| Android Studio | Installed at `C:\Program Files\Android\Android Studio`. Data directory name `AndroidStudio2023.1`. The `product-info.json` version field is the placeholder `dev build`, so a marketing version number is not taken from that file |
| Xcode / macOS | Not applicable. This is a Windows workstation |

`flutter doctor` also reported Visual Studio C++ as absent. That affects Flutter Windows desktop builds. It does not affect the approved Android/iOS direction and does not block Step 20.

No credentials or connection strings were printed.

## 7. PostgreSQL Readiness

PostgreSQL is absent. This was re-checked in this step: no `psql` command and no PostgreSQL service.

Because it is absent, it was not installed.

| Topic | Recommendation |
|---|---|
| Major version | PostgreSQL 18 |
| Current minor at this check | 18.6, supported by the PostgreSQL project until 2030-11-14 |
| Version to avoid | PostgreSQL 19, which was still a beta on 2026-09-24 |
| Why 18 | Current stable major. Laravel 13 documents PostgreSQL 10.0+ as supported, so 18 is inside that range. Exclusion constraints and triggers, which are now approved, are core PostgreSQL features |
| Local installation method | EDB-certified Windows installer from the PostgreSQL Windows download page, or `winget` package `PostgreSQL.PostgreSQL.18`, and only after a separate install approval. Interactive install is the preferred local method so the operator sets the superuser password and sees the port |
| Required development components | PostgreSQL server, command-line client (`psql`, `pg_dump`), and the server libraries the installer includes. pgAdmin is useful and optional. Stack Builder add-ons are not required for the HRMS schema work |
| PHP follow-up after the server exists | Enable `extension=pdo_pgsql` and `extension=pgsql` in the Laragon PHP 8.3.30 `php.ini`. The DLLs are already in `ext\`. This file was not edited |
| Expected service | `postgresql-x64-18` |
| Expected port | 5432 |
| Database and user | A local development role and a local development database, created only in a later approved step. The superuser password is chosen at install time and is not recorded here |

`new_proedge_hrms` was not created. No application database, role, schema, or table was created. Local connection was not tested because there is no server to connect to.

**Installation approval for the next environment-preparation action: NOT GRANTED.**

The next environment action is a separate approval to install PostgreSQL 18 on this workstation and then enable `pdo_pgsql`. This document asks for that approval. It does not perform the install.

## 8. Laravel / PHP Readiness

Recommended Laravel line: **Laravel 13**.

| Fact | Source checked 2026-10-09 |
|---|---|
| Laravel 13 release | 2026-03-17 |
| PHP requirement | 8.3–8.5. This machine has PHP 8.3.30 |
| Application skeleton | `laravel/laravel` 13.x requires `php: ^8.3` and `laravel/framework: ^13.17` |
| Framework package | `laravel/framework` 13.35.0 published 2026-10-06, `php: ^8.3` |
| Bugfix / security window | Bug fixes until Q3 2027. Security fixes until 2028-03-17 |
| PostgreSQL | First-party support documented as PostgreSQL 10.0+ |
| Migrations | Laravel migrations are the approved schema home, together with controlled migration tooling for data movement |
| Laravel 12 | Bug-fix window ended 2026-08-13. Security support continues to 2027-02-24. It is not the recommended starting line |

Laravel 13 is the maintainable choice because the installed PHP already meets its minimum, PostgreSQL is a first-party driver, and the release is inside its bug-fix window. Laravel 11 is outside its security window as of 2026-03-12.

Authentication and API style are not selected here. A separate React client and a future Flutter client need a token or session API. Choosing Sanctum, Passport, or another package is a later technical decision. No auth package was installed.

Composer 2.8.8 is sufficient to create a Laravel 13 project later. The project was not created. `composer create-project` was not run.

Required PHP extensions for that later create step are already loaded (`ctype`, `curl`, `mbstring`, `openssl`, `tokenizer`, `xml`, `fileinfo`). `pdo_pgsql` must be enabled after PostgreSQL exists, before the application is pointed at PostgreSQL.

## 9. React / Vite Readiness

Node.js v22.22.3 and npm 12.0.0 are installed and are sufficient for a Vite project. No frontend project was created.

Recommended baseline for the later frontend step:

| Piece | Baseline |
|---|---|
| Runtime | Node.js 22, already installed |
| Package manager | npm, already installed |
| UI | React 19 |
| Build | Vite 8 |
| Language | TypeScript |
| First area | Employee Web / ESS |
| Later areas in the same application | Admin, HR, Manager |

Exact npm package versions are pinned when that project is created, not in this step. No page, route, or API client was scaffolded.

## 10. Flutter / Dart Readiness

The mobile toolchain is present for Android work and is not used to create a project in this step.

| Piece | Installed |
|---|---|
| Flutter | 3.44.7 stable |
| Dart | 3.12.2 |
| Android SDK | 36.1.0 |
| Android Studio | Present, with JBR 17 |
| iOS | Not available on Windows |

The installed Dart 3.12.2 matches the language constraint recorded for `nree.mobile.v2` (`^3.12.2`) and sits inside the `nree.mobile` range (`>=3.0.0 <4.0.0`). That match is environmental only. It does not make either Nree tree the new client.

Later mobile gaps, not Step 20 blockers:

- Android SDK licenses are not fully accepted.
- The Java binary on PATH is 8. Android builds already use the Studio JBR 17. PATH was not changed.
- iOS builds need a Mac and Xcode.
- A new Flutter project is a later step.

`flutter upgrade`, `flutter pub get`, and `flutter doctor --android-licenses` were not run. Nree was not modified.

## 11. Technical Decision Register

**Approved technical decision** means an implementation choice approved in this step.

**Business decision** means a D01–D18 policy value. Those stay open. This register does not assign them.

| Area | Decision | Status | Reason | Notes |
|---|---|---|---|---|
| Database engine | PostgreSQL 18.x | Approved technical decision | Exclusion constraints and trigger immutability are core PostgreSQL behavior. Legacy MySQL is the read-only production source, not the target engine | Local server validated at 18.6. No production database |
| Backend | Laravel 13 when the project is created | Approved technical decision for Laravel. Version baseline recorded, not installed | PHP 8.3.30 meets Laravel 13. PostgreSQL and migrations are first-party | Do not create the application in this step |
| Web | React + Vite | Approved technical decision | Separate client for the Universal HRMS web application | React 19 + Vite 8 + TypeScript is the baseline. Not scaffolded |
| Mobile | Flutter for Android and iOS | Approved technical decision | Step 19B found Flutter already in use and rejected Nree as the future client | New project not created |
| Nree | Reference only. Option B | Approved technical decision | Nree is a Proedge hybrid ESS client, not the universal multi-company client | `nree.mobile` and `nree.mobile.v2` unchanged |
| ULID | 26-character ASCII text | Approved technical decision | Crockford text avoids a private binary layout and stays indexable as text | Not `uuid`, not `BINARY(16)` |
| Effective-date overlap | PostgreSQL exclusion constraints | Approved technical decision | Overlap must be rejected by the database, not by query order | Half-open intervals stay as already specified. No SQL written here |
| Raw punch immutability | Database triggers | Approved technical decision | Application services are not a substitute for the stored-punch rule | Trigger body not written here |
| Finalized payroll immutability | Database triggers | Approved technical decision | A later attendance recalculation must not change finalized payroll | Reopen remains BD-D15 and is not designed here |
| Migration tooling | Laravel migrations plus controlled migration tooling | Approved technical decision | Schema changes live in Laravel migrations. Data movement stays a controlled, counted migration | No migration files written |
| Initial web implementation | Employee Web / ESS | Approved technical decision | First user-facing web area | Does not shrink the backend |
| Future web areas | Admin, HR, Manager | Approved technical decision | Same React application, later phases | No pages created |
| Universal backend and database | Multi-company and multi-role | Approved technical decision | ESS-first delivery must not produce an ESS-only schema | `company_id` tenancy stays as already decided |
| D01–D18 values | Unchanged and open. BD-D15 deferred | Business decision, not taken | Policy values are not technology choices | No value written |
| Money scale, duration unit, `jsonb`, `timestamptz`, composite foreign keys, active-code index predicate | Not in this approval list | Still recommendations from Step 19 | They were not part of the approved direction in this step | They must not be invented as defaults during a later DDL step without being named |
| PostgreSQL installation | Not performed | Approval required | The engine is approved. The workstation change is a separate action | See section 7 |

## 12. D01–D18 Protection Check

No requirements, architecture, schema, or migration document was edited in this step. No business value was selected.

| ID | Subject | This step |
|---|---|---|
| D01 | Grace | Unchanged |
| D02 | Incomplete punch | Unchanged |
| D03 | Shift authority | Unchanged |
| D04 | Late | Unchanged |
| D05 | Early out | Unchanged |
| D06 | Leave pay | Unchanged |
| D07 | Absence pay | Unchanged |
| D08 | OT eligibility | Unchanged |
| D09 | OT calculation | Unchanged |
| D10 | OT rounding | Unchanged |
| D11 | OT approval | Unchanged |
| D12 | OT pay / rate | Unchanged |
| D13 | Attendance freeze | Unchanged |
| D14 | Payroll freeze | Unchanged |
| D15 | Reopen | Deferred. Unchanged |
| D16 | Correction fields | Unchanged |
| D17 | Approval sequence | Unchanged |
| D18 | Payroll snapshot | Unchanged |

## 13. Environment Gaps

| Gap | Effect |
|---|---|
| PostgreSQL 18 server and `psql` are not installed | Blocks Step 20. Install approval is not granted |
| `pdo_pgsql` and `pgsql` are commented out | Blocks a Laravel connection after PostgreSQL exists. `php.ini` was not changed |
| Laravel project does not exist | Required before schema migrations can live anywhere. Creating it was forbidden in this step |
| React + Vite project does not exist | Required when ESS screens start. Not required to approve the technology, and not created here |
| TD-07, TD-08, TD-09, TD-11, and TD-14 remain unnamed in SQL | A later DDL step still has to name these physical details before it writes those column definitions. This step does not invent them |
| Android SDK licenses not accepted | Later Android build gap only |
| No Xcode / macOS | Later iOS build gap only |
| Java 8 is first on PATH | Android builds already use Studio JBR 17. PATH was left as found |

Legacy MySQL 8.4.3 remains the client for the existing legacy audit databases. It is not the approved target engine.

## 14. Step 20 Readiness Gate

The table below is the Step 19A inspection. PostgreSQL availability and `pdo_pgsql` have since been validated; see "Current database decision" at the top. The current Step 20 judgment is in `docs/UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_READINESS.md`.

Step 20, as understood in Step 19A, needed an approved target technology, PostgreSQL available, a Laravel project foundation, PHP and Composer, Git, and a documented version baseline. React + Vite is part of the approved web architecture and is required when web screens start; it is not a substitute for the database.

| Prerequisite | State |
|---|---|
| Approved target technology | Recorded in this document |
| PostgreSQL available | No |
| Laravel project foundation | No. Not created, by instruction |
| React + Vite project foundation | No. Not created, by instruction |
| PHP / Composer | Yes. PHP 8.3.30, Composer 2.8.8. `pdo_pgsql` still disabled |
| Git | Yes. 2.49.0.windows.1 |
| Documented version baseline | Yes. PostgreSQL 18, Laravel 13, React 19, Vite 8, Flutter 3.44.7, Dart 3.12.2 |

**NOT READY FOR STEP 20**

The environment prerequisite is local PostgreSQL 18, with `psql` available and `pdo_pgsql` enabled. Until that exists, Step 20 must not create `new_proedge_hrms`, tables, or Laravel migration files.

## 15. Recommended Next Action

Approve, in a separate instruction, the installation of PostgreSQL 18 on this workstation by the EDB Windows installer or `winget` package `PostgreSQL.PostgreSQL.18`, on port 5432, Windows service `postgresql-x64-18`, including the server and command-line tools.

After that install is approved and completed, the following environment step is: enable `pdo_pgsql`, confirm a local connection, and only then create the Laravel 13 project foundation outside the legacy tree. Do not create `new_proedge_hrms` or HRMS tables in that same breath unless a later step explicitly says to.

Do not start schema DDL while TD-07, TD-08, TD-09, TD-11, and TD-14 are still unnamed.

## Final Safety Statement

Production modified: NO

Legacy databases modified: NO

Legacy data imported: NO

Nree modified: NO

new_proedge_hrms created: NO

HRMS tables created: NO

HRMS migrations executed: NO

HRMS application created: NO

D01–D18 changed: NO

Migration approval granted: NO

PostgreSQL installed: NO

PostgreSQL installation approval granted: NO, at the close of Step 19A

Later development-machine validation, not a production change: PostgreSQL 18.6 is installed. No HRMS database was created.
