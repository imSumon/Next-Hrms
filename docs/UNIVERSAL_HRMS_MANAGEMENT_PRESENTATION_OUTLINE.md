# Management meeting outline — 10 minutes

Companion to `docs/UNIVERSAL_HRMS_MANAGEMENT_FINDINGS_PRE_DEVELOPMENT_REPORT.md`.

Purpose: agree the problems to settle before implementation. No decision in this outline is already approved.

| Minute | Slide | Say this |
| --- | --- | --- |
| 0–1 | Why we are here | The audits are far enough to choose rules. They are not far enough to load people, time, or pay. No company is approved for production migration. |
| 1–3 | Five problems | Identity is not the employee code. Punches are shared and the vendor is unknown. Several engines overwrite the day. Overtime hours and overtime money do not match. A lock flag did not close the studied months. |
| 3–5 | People | Dump figures, not live counts: Proedge 160 codes, Atlas 151, VTL 1,420 rows and 1,413 codes. Seven VTL codes are duplicated. One VTL row has no name. Atlas `EB9091` is both active and discontinued. Both flags are on for 1,186 VTL rows. |
| 5–7 | Time and pay | About 13.9 million shared punches, with no company on the traced days. The day keeps first and last punch. Corrections can replace the displayed time. Proedge paid overtime money that is not the attendance seconds. Salary rows do not store the attendance they used. |
| 7–9 | Decisions we need | P0: name rule, conflicting flags, duplicate codes, incomplete punch, shift winner, overtime calculation and approval, correction fields, and payroll close. Do not pick Proedge or Viyellatex as the standard. |
| 9–10 | What happens next | Company records for the three tenants exist locally. Employee tables are empty. Implementation of a domain starts only after that domain’s decisions are signed. Ask the meeting to name owners for the P0 rows. |

Do not claim a hypothetical correction-after-payroll case happened in production. The studied salary files do not contain an attendance snapshot, so that link is a risk, not a traced event.
