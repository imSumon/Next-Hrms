# Universal HRMS Nree Mobile Application Assessment

Status: assessment only. No source, dependency, configuration, database, or API was changed.

`docs/UNIVERSAL_HRMS_BUSINESS_RULE_DECISION_FRAMEWORK.md` is not in the workspace. D01–D18 remain the open decisions already recorded in the approved requirements and the Step 19 technology document.

---

## 1. Executive Summary

Two local Flutter applications exist. They are not one product.

| Tree | What the source shows |
|---|---|
| `nree.mobile` | Version `7.0.10+1070010`. Native GPS and geo punch against the legacy HRMS, then a WebView shell into that same HRMS. Company context is `db_alias`. |
| `nree.mobile.v2` | Version `1.0.0+1`. Sprint 1.5 mock prototype. Its own architecture document says there are no Yii2 calls. Login, attendance, leave, and notifications are in-memory mocks. |

Nree is not ready to be the Universal HRMS mobile client. The evidence supports **Option B**: keep both trees as reference code, and build the Universal client later against the new API. `nree.mobile` is coupled to the legacy alias, WebView, and punch endpoints. `nree.mobile.v2` has a cleaner module shape, and its attendance and leave numbers are hardcoded demo behavior, including a local late rule after 09:05.

Flutter is the mobile technology already used. That is evidence for a later Flutter Android and iOS client. It is not an approval, and it does not change the Step 19 gate. Schema implementation stays **NOT READY FOR STEP 20**.

## 2. Nree Project Location

Searched under `D:\Project -All`. Flutter projects with `pubspec.yaml` were found at:

| Role | Exact root |
|---|---|
| Legacy operational client | `D:\Project -All\Nree\nree.mobile` |
| Mock rewrite | `D:\Project -All\Nree\nree.mobile.v2` |

`nree.mobile` contains `lib/`, `android/`, `ios/`, `assets/`, `test/`, plus `linux/`, `macos/`, `web/`, and `windows/`. `nree.mobile.v2` contains `lib/`, `android/`, `ios/`, `assets/`, `test/`, and `docs/`. It does not contain desktop or web runners.

Other `pubspec.yaml` files under Sales Platform and ORO Inventory are different products and were not assessed.

## 3. Technology Inventory

### `nree.mobile`

| Item | Evidence |
|---|---|
| Package name | `nree_app_new` |
| Description | “Nree HRMS Mobile Application.” |
| App version | `7.0.10+1070010` |
| Dart SDK constraint | `>=3.0.0 <4.0.0` |
| Flutter pin | Not declared in `pubspec.yaml`. `.metadata` channel is `stable`, revision `05db9689081f091050f01aed79f04dce0c750154` |
| State | GetX (`get`) |
| HTTP | `http` and `dio` |
| Local storage | `shared_preferences`, `get_storage` |
| Location | `location`, `geolocator`, `geocoding`, `google_maps_flutter` |
| Firebase | `firebase_core`, `firebase_crashlytics`. No Firebase Messaging package in `pubspec.yaml` |
| Other | `webview_flutter`, `file_picker`, `in_app_update`, `package_info_plus`, `cached_network_image`, `url_launcher`, `logger`, `intl` |
| Absent from `pubspec.yaml` | SQLite, Hive, secure storage, camera plugin, local notifications, biometric plugin, connectivity plugin |

### `nree.mobile.v2`

| Item | Evidence |
|---|---|
| Package name | `nree_hrms_v2` |
| Description | “NREE HRMS Mobile V2 — native Flutter HRMS client.” |
| App version | `1.0.0+1` |
| Dart SDK constraint | `^3.12.2` |
| Flutter pin | Not declared. `.metadata` channel is `stable`, revision `84fc5cbb223bc12f83d65b647ff8a56caf779ffd` |
| State | `flutter_riverpod` |
| Routing | `go_router` |
| HTTP | `dio`, constructed and unused by the feature repositories |
| Storage | `flutter_secure_storage` |
| Models | `equatable`. Freezed is a dev dependency and is not used for the domain entities that were read |
| UI | Material 3, Lottie, bundled Brandon Grotesque and Noto Sans JP |
| Absent from `pubspec.yaml` | GPS, maps, Firebase, WebView, SQLite, Hive, camera, notifications plugin, biometric plugin |

No flavors are declared in Gradle. v2 selects `dev`, `uat`, or `production` with `--dart-define=ENV=`. All three resolve to the same legacy base URL, and `useMockData` is hard-set to true.

## 4. Flutter and Dart Assessment

Both applications are Flutter. The README for `nree.mobile` calls that tree a scaffold of an older app. The source is not only a scaffold: it contains sign-in, GPS attendance, geo attendance, office selection, and a WebView dashboard.

`nree.mobile.v2\docs\Architecture.md`, dated 2026-07-23, states the goal is to replace the WebView app with a native client, and that Sprint 1 uses mock repositories only.

Neither tree declares a Flutter SDK version beside the Dart constraint and the `.metadata` revision. Those revisions were not converted into a marketing version number here.

## 5. Android Assessment

| Item | `nree.mobile` | `nree.mobile.v2` |
|---|---|---|
| Application id | `com.proedgepayroll.Nree` | `com.nree.nree_hrms_v2` |
| Label | `Nree` | `nree_hrms_v2` |
| minSdk / targetSdk | `flutter.minSdkVersion` and `flutter.targetSdkVersion`. No numeric pin in the app Gradle file | Same |
| Permissions in the app manifest | `INTERNET`, `FOREGROUND_SERVICE` | No `uses-permission` entries in the app manifest |
| Cleartext | `android:usesCleartextTraffic="true"` | Not set in the manifest that was read |
| Release signing | Release signing reads `key.properties` | Release uses the debug signing config |
| Firebase | Google services and Crashlytics Gradle plugins are applied | Not present |
| Deep link | Scheme `dashboard-diary`, host `ikigai-diary.com` | None found |

Android projects exist for both. A numeric minimum SDK is not stored in these Gradle files.

## 6. iOS Assessment

| Item | `nree.mobile` | `nree.mobile.v2` |
|---|---|---|
| iOS project | `ios/` present | `ios/` present |
| Deployment target in `project.pbxproj` | `13.0` and `15.6` both appear | `13.0` |
| Bundle id | `com.proedge-payroll.Nree` | `com.nree.nreeHrmsV2` |
| Location usage strings | When-in-use, always, and always-and-when-in-use | No location usage keys found |
| Camera usage string | Present | Not found |

iOS projects exist. This assessment did not build either app and did not open a signing account.

## 7. Architecture Assessment

### `nree.mobile`

The folders are `api`, `controllers`, `models`, `screens`, `services`, `viewmodels`, and `widgets`. The running pattern is GetX controllers bound to screens.

Representative path for GPS check-in:

```
gpsattendance_screen
→ GpsAttendanceController
→ HTTP GET attendancestatus and office locations
→ device GPS
→ compare distance with office radius_meter
→ HTTP save of GPS attendance
```

After login, if the company flag `location_permission` is `"1"`, the app requests location and then opens a WebView. The WebView is the ESS shell. That is a hybrid app, not Clean Architecture.

A `viewmodels/user_login_viewmodel.dart` file exists beside `controllers/signin_controller.dart`. The sign-in screen path that was read uses the controller.

### `nree.mobile.v2`

The source matches the architecture document’s feature layout:

```
presentation → Riverpod notifier → repository interface → mock repository
```

`repository_providers.dart` binds every feature to a mock. Dio is constructed in `ApiClient` and is not used by those repositories. Domain, data, and presentation folders exist. Use-case classes were not found; notifiers call repositories directly.

This is a feature-based layered prototype. It is not yet a client of any HRMS API.

## 8. API Assessment

No production API was called.

### `nree.mobile`

Two legacy bases are hardcoded:

- Login: `https://proedge-asso.com/hrms/api/login/`
- Other calls and the WebView landing: `https://proedge-asso.com/hrms/apinew/`

Endpoints named in `APIConstants` include `login/`, `vaidateaccesstocken/`, `savelocationdata/`, `geo-check/`, `attendance/`, `latestlocation`, `confirmedlocationsbydate`, `confirmattendance`, `getofficelocations`, `latestgpslocation`, `savegpsattendance`, `confirmedgpslocationsbydate`, `attendancestatus`, `default/profile`, `default/notifications`, `default/changepassword`, `default/help`, `default/cloudpunch`, and `apilanding`.

`GpsAttendanceController` sends `employee_id`, `company_name`, and `source=gps`. The company value is `db_alias`. The same controller comment says the button state comes from `attendancestatus`, and that production appeared to return the geo table even for `source=gps`.

`ApiClient` is a Dio wrapper with a 30-second timeout and a debug log interceptor that prints request and response bodies. No refresh-token call was found in that client. A refresh-token preference key exists. Login uses `package:http` form posts, not that Dio client.

The API is tightly coupled to the legacy HRMS. Endpoint names, `db_alias`, `emp_id_no`, and the WebView landing are legacy contracts.

### `nree.mobile.v2`

`AppConfig` points dev, uat, and production at `https://proedge-asso.com/hrms/apinew/`. `useMockData` is true, with the comment “Flip when Yii2 repositories land.” `AuthInterceptor` would add `Authorization: Bearer` from secure storage. No feature calls it. There is no refresh flow and no API versioning.

The network shell could be reused. The configured host and the planned Yii2 repositories are legacy coupling, not a Universal HRMS API.

## 9. Authentication and Authorization

### `nree.mobile`

| Behavior | Evidence |
|---|---|
| Login | Company name, username, and password posted as form fields `c`, `username`, and `password` |
| Session | `access_token` from the legacy user payload, kept on `UserModel` and in preferences when remember-me is on |
| Employee | `emp_id_no` and legacy name fields |
| Company | `company_name`, `db_alias`, `location_permission` |
| Modules | Legacy module rows, including `ess` and `ess_lock` |
| Password storage | Remember-me writes the password into `SharedPreferences` |
| WebView authority | The access token is placed on the `apilanding` query string |
| Logout and refresh | A refresh key exists. A full refresh-token exchange was not found in the files read |
| Biometric | No biometric package |

The server returns the user and the company alias. The app then treats `db_alias` as the company key and opens the legacy web ESS. Button state for GPS is requested from the server. Distance versus office radius is calculated on the phone. The phone is not a neutral capture client.

### `nree.mobile.v2`

Login accepts any non-empty company id and employee id when the password matches a hardcoded demo password. The user record is a fixed demo person. Companies are three invented rows, including a “ProEdge” row that is not catalog company 24. The token stored in secure storage is a mock string. Logout clears that session. The biometric button only shows a snackbar that enrollment is not available. There are no roles and no permission checks.

v2 does not authorize payroll, attendance, or approvals. It also does not ask a server. A mock that accepts a local password is not server-side authorization.

## 10. Attendance Assessment

Attendance calculation for the Universal HRMS stays on the server. The table records what the apps actually contain.

| Feature | Exists | Source | API | Legacy coupled | Reusable | Notes |
|---|---|---|---|---|---|---|
| Check-in / check-out | Yes in v1. Mock in v2 | `GpsAttendanceController`, `GeoAttendanceController`, v2 `AttendanceRepositoryMock` | v1: `attendancestatus`, `savegpsattendance`, geo confirm endpoints. v2: none | v1 yes. v2 demo | v1 flow is a reference. v2 late rule is not | v1 asks the server for the next action. v2 stores check-in in memory |
| Punch creation | v1 location save. v2 none | GPS and geo controllers | `savelocationdata`, `savegpsattendance` | Yes | Capture pattern only | These are location posts, not Universal `punch_events` |
| Punch history | v1 confirmed locations by date | GPS and geo controllers | `confirmedgpslocationsbydate`, `confirmedlocationsbydate` | Yes | Partial | Not a raw punch log |
| Attendance history | v2 mock list. v1 WebView may show the legacy ESS | v2 history tab | v2 none | v2 no API. v1 web shell yes | UI shell only | v2 history is generated in the mock |
| Status display | v2 local Present / Late / Absent / Half day. v1 button state from API | v2 `getTodayAttendance` | v2 none | v2 rule is local | No | v2 marks late when check-in is after 09:05 |
| Missing punch | Not found as a native rule | — | — | — | — | — |
| Correction | Not found as a native screen | — | — | — | — | — |
| Attendance approval | Not found as a native workflow | — | — | — | — | — |
| Calendar | v2 calendar tab over mock data | `attendance_calendar_tab.dart` | None | Demo | UI only | — |
| Shift display | v2 timeline label “Shift started” at 09:00 | Mock repository | None | Demo | No | Not a shift assignment |
| Holiday / weekend | v2 enum includes weekend and holiday. Mock history uses weekend days | Entities and mock | None | Demo | No | No holiday calendar |
| Leave on the attendance day | Not linked | Separate v2 leave feature | None | Demo | No | — |

v1 after login opens `apilanding` in a WebView with `m=ess`. Any further attendance screens inside that WebView are the legacy web ESS, not native Flutter rules.

## 11. GPS and Location Assessment

### `nree.mobile`

| Behavior | Evidence |
|---|---|
| Permission strings | iOS when-in-use and always. Android manifest does not list location permissions; plugins may merge them at build time. That merge was not inspected |
| Foreground capture | `geolocator` current position, then reverse geocoding |
| Background | `FOREGROUND_SERVICE` is declared. No background-location plugin was found in `pubspec.yaml`. An always-location usage string exists on iOS |
| What is stored | Latitude, longitude, address, office id, remark, and the server’s pending location id |
| Geofence | Office list comes from `getofficelocations`. The phone computes meters with `Geolocator.distanceBetween` and compares with `radius_meter` |
| Upload | `savegpsattendance` and `savelocationdata` |
| History | Confirmed locations by date from the server |
| Offline queue | Not found |
| Anti-spoofing | Not found |

The server supplies offices, radius, and the next check-in or check-out action. The phone decides whether the current point is inside the radius before submit. Location is captured and used as an attendance gate. It is not only a map display.

Classification against the Universal HRMS: **CLIENT EXTENSION** unless a later business decision makes mobile location a configured punch source. It is not core attendance. The architecture already treats an unnamed device protocol and mobile location as optional. GPS-in-radius is not a universal rule.

### `nree.mobile.v2`

No location package, no location permission, and no geofence code. Classification for this tree: **NONE**.

## 12. Leave Assessment

`nree.mobile` has no native leave module in `lib/`. Leave inside the WebView would be the legacy ESS.

`nree.mobile.v2` has balance, apply, history, calendar, and a status timeline. Types are an enum: Annual, Sick, Casual. Balances in the mock are fixed integers. Status values are pending, approved, and rejected. Applying leave computes a day count in the mock by skipping Saturday and Sunday. There is no attachment field on `LeaveRequest`. There is no cancellation method in the entity that was read.

Those rules are local and hardcoded. They are not API-supplied and they are not a company policy. They must not become Universal leave pay or a leave-day rule. BD-D06 stays open.

## 13. Approval Assessment

No native approval feature was found in either tree.

v2 leave status is a display enum on a mock request. It does not name an approver, a sequence, or a role. v1 can show whatever the legacy WebView ESS shows. That is not a configurable ordered workflow in the Flutter code.

The Universal model needs server-owned steps: role, reporting manager, organization approver, or named fallback. Nree does not implement that. BD-D17 stays open.

Overtime approval was not found.

## 14. Notification Assessment

| Item | `nree.mobile` | `nree.mobile.v2` |
|---|---|---|
| Firebase Messaging | Not in `pubspec.yaml` | Not present |
| Crashlytics | Present | Not present |
| Local notifications | Not in `pubspec.yaml` | Not present |
| In-app list | Endpoint constant `default/notifications` | `MockNotificationRepository` |
| Categories | Not read as a native model | leave, attendance, payroll, system, announcement |
| Push token | Not found | Not found |
| Deep link | `dashboard-diary` to `ikigai-diary.com` | Not found |

No notification was sent. The v2 list is demo data. Payroll appears as a notification category only.

## 15. Offline and Local Storage

| Store | Where | What it holds |
|---|---|---|
| `SharedPreferences` | v1 `AppManager` | Remember-me flag, company, username, password, access token |
| `get_storage` | Declared in v1 `pubspec.yaml` | Not traced as the session store in the files read |
| `flutter_secure_storage` | v2 | Mock access token, remember-me company and employee id. The architecture note says passwords are not stored |
| SQLite / Hive | Neither `pubspec.yaml` | Absent |
| Offline punch queue | Not found | Absent |

v1 remember-me can replay a saved password. v2 can restore a mock session from secure storage. Neither app was shown to complete attendance, leave, or payroll while offline. An expired token path with refresh was not found.

Offline capture must not become the authority for attendance or payroll. Nothing in these trees is that authority today, and nothing in them is a safe offline queue either.

## 16. Security Assessment

Findings, with values omitted:

- Potential secret/credential found — location redacted.
- Potential secret/credential found — location redacted.
- v1 remember-me stores the password in `SharedPreferences`.
- v1 puts the access token on the WebView URL query string.
- v1 Dio and HTTP logging can print bodies in debug.
- v1 allows cleartext traffic in the Android manifest.
- v1 login and punch hosts are hardcoded production URLs.
- v2 stores a mock bearer token and a hardcoded demo password check. The architecture note says real passwords are not persisted. The demo check is still local authentication.
- v2 biometric control does not read a device credential.
- Certificate pinning was not found.
- Screenshot protection was not found.
- Device binding was not found.

The mobile apps are not the authorization authority required by the Universal HRMS. v1 delegates much of ESS to a WebView. v2 authorizes nobody.

## 17. Legacy Coupling

| Item | Tree | Class |
|---|---|---|
| `https://proedge-asso.com/hrms/api/` and `/hrms/apinew/` | Both, v2 unused while mocks are on | REMOVE as the Universal base URL |
| `db_alias` as the company key | v1 | REMOVE |
| `emp_id_no`, `emp_first_name`, module `mname` / `ess` | v1 login model | ADAPT the identity fields only after the new API exists. REMOVE the legacy names |
| `apilanding` WebView ESS | v1 | REMOVE for the Universal client |
| `attendancestatus`, `savegpsattendance`, `savelocationdata`, office radius | v1 | ADAPT the capture idea. REDESIGN the contract |
| `location_permission == "1"` | v1 | ADAPT as a company setting, not a legacy flag |
| Yii2 repository plan in v2 config comment | v2 | REMOVE. The next API is not Yii2 |
| Demo companies and demo user | v2 | REMOVE |
| Local late-after-09:05 and weekend-skipped leave days | v2 | REMOVE |
| Brand fonts, colors, and design tokens | v2 | REUSE |
| Feature folders, repository interfaces, GoRouter, Riverpod | v2 | REUSE WITH ADAPTATION |
| GetX controllers and WebView navigation | v1 | REMOVE |
| Deep link to `ikigai-diary.com` | v1 | REMOVE |
| Crashlytics wiring | v1 | UNKNOWN until a product decision on crash reporting |

## 18. Universal HRMS Compatibility

| Universal concept | Nree support | Compatibility | Required change |
|---|---|---|---|
| `company_id` | v1 uses `db_alias` and a typed company name. v2 uses mock string ids | NONE | New company key from the server |
| Employee identity | v1 `emp_id_no`. v2 free-text employee id | PARTIAL | Server employee id. Code stays an attribute |
| Employee code | v1 shows the legacy code | PARTIAL | Do not use it as the primary key |
| Punch identity | Not present | NONE | Server-owned punch identity |
| Punch event | v1 posts location rows | PARTIAL | New immutable punch contract. Phone does not rewrite time |
| `work_date` | Not present | NONE | Server assigns it. Phone does not |
| Shift | v2 labels 09:00 as shift start | NONE | Server shift. Delete the demo clock |
| Attendance result | v1 button from legacy status. v2 local status | PARTIAL | Display server tokens only |
| Attendance correction | Not present | NONE | New flow when the API exists |
| Approval workflow | Not present | NONE | Server steps. No local approver |
| Leave | v2 mock types and balances | PARTIAL | UI can be adapted. Rules cannot |
| Calculated OT | Not present | NONE | Server figure |
| Payable OT | Not present | NONE | Server figure |
| Paid OT | Not present | NONE | Payroll snapshot. Not a mobile calculation |
| Payroll snapshot | Notification category only | NONE | No payroll client |
| Audit / history | v1 location history from legacy. v2 mock history | PARTIAL | Server history |
| Server-side authorization | v1 depends on legacy login and then a WebView. v2 is local | PARTIAL | Every action checked by the new API |
| Configurable policy | v1 company location flag. v2 hardcoded rules | NONE | No policy values in the app |

## 19. Reuse Assessment

| Area | Class | Why |
|---|---|---|
| A. UI components | REUSE WITH ADAPTATION | v2 design system and widgets. v1 widgets are tied to GetX and the WebView flow |
| B. Navigation | REUSE WITH ADAPTATION | v2 GoRouter shell is a usable shape. v1 routes into a WebView |
| C. State management | REUSE WITH ADAPTATION | Riverpod in v2. GetX in v1 should not be carried forward |
| D. API client | REBUILD | v2 Dio shell is small and still aimed at `apinew`. v1 client logs bodies and has no refresh |
| E. Authentication UI | REUSE WITH ADAPTATION | Company, employee, and password fields. Replace mock and legacy login |
| F. Secure storage | REUSE WITH ADAPTATION | v2 storage service. Do not copy v1 password preferences |
| G. Location services | REUSE WITH ADAPTATION | v1 GPS and office-radius flow is the only real location client. Contract and `db_alias` must change |
| H. Notification framework | REBUILD | No push client. v2 list is mock data |
| I. Document / file handling | UNKNOWN | v1 declares `file_picker`. No leave-attachment flow was found |
| J. Attendance UI | REUSE WITH ADAPTATION | v2 tabs are a layout. Status, shift, and late logic are not reusable |
| K. Leave UI | REUSE WITH ADAPTATION | v2 screens. Enums, balances, and weekend math are not reusable |
| L. Approval UI | REBUILD | No native approval UI |
| M. Testing | REBUILD | Each tree has a single `widget_test.dart` |
| N. Build configuration | REUSE WITH ADAPTATION | Android and iOS runners exist. v1 signing and ids are legacy. v2 release build uses debug signing |

## 20. Redesign Requirements

Before either tree can talk to the Universal HRMS:

1. Replace `db_alias` and the WebView ESS with API screens authorized by the server.
2. Stop storing passwords and stop putting access tokens on URLs.
3. Remove hardcoded hosts, the demo password check, and the Yii2 repository plan.
4. Delete local late, shift, and leave-day calculations. The phone submits facts. The server calculates.
5. Add `company_id`, employee id, and punch identity as server fields. Do not invent them in the app.
6. Treat GPS radius as a configured punch source, off until that company enables it.
7. Add correction and approval only as clients of the server workflow.
8. Add no payroll amounts and no OT formulas.
9. Keep D01–D18 out of the client.

## 21. Future Mobile Architecture

Flutter is the mobile stack the existing source actually uses. The Step 19 document recommended Laravel and React and left them unapproved. It did not select a mobile framework. This assessment adds Flutter as the mobile candidate. It does not approve it.

```
Universal HRMS
      |
 Laravel API
      |
+-----+------------------+
|                        |
React Web           Flutter mobile
                         |
                  Android and iOS
                         |
              New client, informed by Nree
```

If that shape is later approved:

- Business rules stay on the server.
- The phone captures and submits.
- The server checks authorization.
- The server calculates attendance and OT.
- The server owns approval steps.
- The server owns payroll.
- The phone does not become the business-rule authority.

`nree.mobile` is not dropped into that diagram as the client. `nree.mobile.v2` is not dropped in either, because its repositories are mocks and its plan still names Yii2.

## 22. Nree Future Role Recommendation

**Option B.** Nree is a reusable reference. The Universal HRMS mobile client should be a new Flutter application.

| Option | Evidence |
|---|---|
| A. Refactor Nree into the client | v1 would have to lose the WebView, `db_alias`, password storage, and legacy endpoints. v2 would have to lose mock rules and the Yii2 plan. That is a new client with salvaged parts, which is Option B |
| B. Reference, then a new client | v2’s module split, router, design system, and secure-storage habit are worth copying. v1’s GPS capture is the only production mobile behavior and is legacy-coupled. Neither tree is the target client |
| C. Do not reuse | Too strong. The v2 structure and the v1 location flow are real assets. The legacy contracts and demo rules are not |

This is not a preference. v1 is an operating legacy hybrid. v2 is an unfinished mock whose own document forbids Yii2 calls for now and plans them later. Neither fact makes the tree the Universal client.

## 23. Decision Register

| Decision | Evidence | Recommendation | Approval required | Blocking? |
|---|---|---|---|---|
| Flutter as the mobile technology | Both local apps are Flutter | Use Flutter for Android and iOS when a mobile client is authorized | Yes | No for Step 20 schema |
| Nree reuse | v1 legacy hybrid. v2 mock prototype | Option B. Reference only | Yes | No for Step 20 |
| Android support | Both trees have Android runners | Keep Android in the future client | Yes, with the mobile approval | No for Step 20 |
| iOS support | Both trees have iOS runners and deployment targets | Keep iOS | Yes, with the mobile approval | No for Step 20 |
| API strategy | v1 calls legacy `api` and `apinew`. v2 does not call them yet | New Laravel API. Do not point Nree at it in this phase | Yes | Yes before any mobile connection |
| Authentication | v1 legacy form login and token-in-URL. v2 mock session | Server session. Secure storage. No password cache | Yes | Yes before any mobile connection |
| Location | v1 GPS and radius. v2 none | Client extension, configured per company, validated on the server | Yes | No until punch scope is approved |
| Offline | No queue in either app | Do not add an offline authority | Yes | No |
| Notifications | No FCM client | Add push only when the server has events to send | Yes | No |
| Legacy API replacement | Required by the coupling table | Remove `apinew` and WebView from the target client | Yes | Yes before connection |
| Mobile-specific extensions | GPS, WebView ESS, diary deep link | GPS may become an extension. WebView ESS and the diary link do not | Yes | No for schema |

## 24. Open Questions

| Question | Why it is open |
|---|---|
| Which tree do operators ship today, `7.0.10` or the v2 mock? | Version numbers and the v2 architecture note say v1 is the legacy app and v2 is Sprint 1. A store listing was not opened |
| Numeric Android min SDK | Both Gradle files delegate to the Flutter SDK |
| Whether plugin manifests add location permissions at build time | The v1 app manifest does not list them |
| Whether the legacy `attendancestatus` response is geo data for GPS | A comment in the GPS controller says it appeared so. This assessment did not call the API |
| Crash reporting product | v1 has Crashlytics. No decision to keep it |
| File attachments | `file_picker` is declared in v1. No leave attachment flow was found |

## 25. Implementation Readiness Impact

Step 19 remains **NOT READY FOR STEP 20** until the blocking technology rows in that document are approved.

This Nree assessment does not remove that block and does not add a mobile block to schema creation. It adds a later block: do not connect either Nree tree to `new_proedge_hrms` or to a new API until the API exists and the coupling list in section 17 is removed.

D01–D18 are unchanged. The v2 09:05 late rule and the weekend leave-day count are demo code, not decisions.

## 26. Final Conclusion

Nree already provides a legacy hybrid client and a separate native mock. The hybrid client punches through the legacy HRMS and then shows that HRMS in a WebView. The mock shows attendance and leave with local demo rules and does not call a backend. Flutter can be the future Android and iOS technology. Nree, as it stands, should be mined for structure and for the GPS capture reference, and should not be adopted as the Universal HRMS client.

### Safety statement

| Item | Result |
|---|---|
| Nree source modified | NO |
| Nree dependencies modified | NO |
| Nree configuration modified | NO |
| Production accessed/modified | NO |
| Legacy HRMS modified | NO |
| Universal HRMS database created | NO |
| New API created | NO |
| Nree connected to new HRMS | NO |
| Data migrated | NO |
| D01–D18 changed | NO |

### Verification

| Check | Result |
|---|---|
| Both project roots identified from disk | Yes |
| Features taken from source, not from the name | Yes |
| Secrets omitted | Yes |
| Production APIs not called | Yes |
| Attendance and leave rules not adopted | Yes |
| Mobile client not treated as the business authority | Yes |
| Option selected from the two trees’ evidence | Option B |
