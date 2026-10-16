# Sacco360 — Feature Gap Analysis

**Scope:** Full read-only review of `sacco-core/code/Modules/` (30 modules, ~2,300 PHP files) and `ussd-service/src/`
**Date:** 2026-10-14
**Question:** What is missing to *fully* manage a SACCO and a Chama?
**Companion doc:** `DUPLICATE_FUNCTIONALITY_AUDIT.md` (2026-06-07) — duplication review; this document covers **feature completeness and broken paths**

---

## Verdict

| Domain | Completeness | State |
|--------|-------------|-------|
| **SACCO (BOSA/FOSA, loans, compliance, payments)** | ~85–90% | Genuinely production-shaped. Deep, well-integrated. |
| **Chama / group banking** | ~25–30% | A thin prototype bolted onto the Sacco module. Several code paths are **broken, not just missing**. |
| **Platform engineering** | ~40% | The review baseline had only 4 test files / 7 methods and no CI. This branch expands the suite to 8 files / 65 methods and adds a PHP 7.4 + MySQL migration/PHPUnit GitHub Actions gate; overall coverage remains thin, and the leaked key remains in git history. |

The SACCO engine is strong: loan appraisal (multiplier + ⅔ rule + guarantor coverage) is enforced at application *and* approval, guarantor shares lock on disbursement and unlock on closure, SASRA prudential ratios and CAELS classification run on schedules, and 10 `sacco:*` cron commands are correctly registered in `SaccoServiceProvider`.

The Chama side is where the product is not yet usable. It is **isolated** — `SaccoGroup` is referenced by only 3 modules (Sacco, Portal, Client) and by **zero** of Loan, Savings, Share, Accounting, Report, Communication, or the USSD service.

---

## PART A — Chama: broken today (P0)

These are defects, not gaps. Each was verified in source.

### A1. M-Pesa chama contributions always fail
`Modules/Portal/Http/Controllers/ChamaController.php:185` calls:
```php
(new \Modules\Sacco\Services\MpesaStkService())->pushGroupContribution(
    $client->id, $group->id, $contribution->id, $amount, $phone
);
```
**`pushGroupContribution()` does not exist.** `MpesaStkService` defines exactly two methods: `pushLoanRepayment()` and `setting()`.

The call is wrapped in `catch (\Throwable)`, so it fails silently into a warning flash and leaves a `status='pending'` contribution that nothing will ever confirm. **The entire "pay chama contribution by M-Pesa" flow is dead.**

### A2. Pending contributions can never be approved
`ChamaController::storeContribution()` writes `status => 'pending'` for cash, bank, other, and M-Pesa. Searching the whole codebase, **nothing ever transitions a group contribution from `pending` to `posted`**:
- `GroupController` has no approve/reject action.
- `Modules/Sacco/Resources/views/themes/adminlte/group/contributions.blade.php` is **read-only** — a table with zero buttons.
- `GroupService::postContributions()` *creates new* rows at the group's flat `monthly_contribution`; it never touches existing pending rows.

Net effect: any member who self-reports a contribution is stuck in limbo forever, and their contribution never counts toward rotation eligibility or totals.

### A3. `disburseCycle()` writes an invalid enum value
```php
// Modules/Sacco/Services/GroupService.php:223
$cycle->status = 'paid';
```
The column is defined as `enum('upcoming','active','completed','skipped')` at
`2026_06_08_000001_add_missing_sacco_features.php:177`, and **no later migration alters it**.
`config/database.php` sets `'strict' => false`, so MySQL does not error — it silently coerces to `''`.

Consequences:
1. The re-disbursement guard `if ($cycle->status === 'paid')` (line 218) **never fires** → the same pot can be disbursed unlimited times.
2. `ChamaController::myRotation()` filters past cycles with `whereIn('status', ['completed','paid'])` → a disbursed cycle never appears in the member's payout history.
3. The badge in `group/show.blade.php:174` renders empty.

### A4. `late_payment_penalty` is applied to the wrong thing — ✅ FIXED, see G10
`GroupService::resolveBiddingCycle()` (line 204):
```php
$cycle->penalties_deducted = (float) ($cycle->group->late_payment_penalty ?? 0);
```
This deducts the group's flat penalty **once per bidding cycle, from the pot, regardless of who was late or whether anyone was late.** There is no per-member fine record anywhere in the schema. A group with zero defaulters is fined; a group with five defaulters is fined once.

Also: the penalty is only applied to **bidding** groups. `fixed` and `random` rotation models never apply any penalty at all.

> **Worse than recorded here.** `resolveBiddingCycle()` was the only code in the entire Sacco module that ever wrote `status = 'completed'`. Groups on the `fixed` or `random` rotation models therefore had **no way to close a turn at all** — not merely "no penalty applied". Their cycles sat at `upcoming`/`active` forever with `net_payout_amount` at 0, and `disburseCycle()` would then pay out `pot - discount - 0` with no fines ever considered. Fixed in G10.

### A5. Authorization holes in `GroupController`
```php
// Modules/Sacco/Http/Controllers/GroupController.php:23-25
$this->middleware('permission:sacco.groups.index')->only(['index', 'show', 'get_groups']);
$this->middleware('permission:sacco.groups.create')->only(['create','store','addMember','storeMember','postContributions','generateSchedule']);
$this->middleware('permission:sacco.groups.manage')->only(['removeMember','updateMemberRole']);
```
Declared methods: `index, get_groups, create, store, show, addMember, removeMember, postContributions, generateSchedule, updateSettings, swapTurns, skipTurn, submitBid, resolveBidCycle, disburseCycle, contributions`.

**Seven methods carry no `permission:` middleware** — only `auth` + `2fa`:

| Method | Risk |
|--------|------|
| `disburseCycle` | **Moves money.** Any authenticated staff account can disburse a pot. |
| `resolveBidCycle` | Declares a winner and closes a bidding round. |
| `submitBid` | Places a bid on behalf of an arbitrary `client_id`. |
| `updateSettings` | Changes rotation model and penalty amount. |
| `swapTurns` / `skipTurn` | Reorders the payout queue. |
| `contributions` | Reads the full contribution register — **data exposure**. |

The middleware also names two methods that **do not exist** (`storeMember`, `updateMemberRole`), so role changes are both unguarded *and* unimplemented.

### A6. Portal leaks all SACCO meetings to every member
```php
// Modules/Portal/Http/Controllers/ChamaController.php:354
$meetings = SaccoMeeting::withCount(['agendaItems','resolutions','attendances'])
    ->orderByDesc('scheduled_date')->paginate(20);
```
No group filter, no membership check, no `meeting_type` filter. `sacco_meetings` has **no `sacco_group_id` column at all**, so filtering is not even possible today.

Every member who logs into the portal sees **board meetings, credit-committee meetings, supervisory-committee meetings, and AGM minutes** — including the free-text `minutes` field and all resolutions. This is a governance and confidentiality breach.

### A7. Bulk member import corrupts group membership
`DataImportService::importMembers()` does support enrolling members into a chama via the `bmu_name` column (line 139), and the import UI advertises it (`import/index.blade.php:134`). But it writes rows with a raw `DB::table('sacco_group_members')->insert()`:

```php
// Modules/Sacco/Services/DataImportService.php:188
$enrollInsert[] = [
    'sacco_group_id' => $en['group_id'],
    'client_id'      => $cid,
    'status'         => 'active',
    'created_at'     => $now,
    'updated_at'     => $now,
];
```
**`joined_date` is not set.** The column is `$table->date('joined_date')` — NOT NULL with no default (`2026_06_08_000001_add_missing_sacco_features.php:142`). With `'strict' => false`, MySQL silently writes `'0000-00-00'`.

That matters because `joined_date` is the **exact sort key for the default rotation model**:
```php
// GroupService::orderMembers()
return $ordered->sortBy('joined_date')->values();
```
Every bulk-imported member ties on the same garbage date, so the merry-go-round payout order for an imported group is **arbitrary and unstable** — and `Carbon::parse('0000-00-00')` will throw if anything ever formats it.

The import also bypasses `GroupService::addMember()`, so it additionally skips:
- the `max_members` capacity check
- `rebuildUpcomingSchedule()` — an imported `merry_go_round` group ends up with **members but zero cycles**
- the duplicate-membership exception path (it falls back to a per-row `exists` check instead)

### A8. Chama is invisible on the Dashlite theme — ⬇️ RECLASSIFIED: not a P0, see G11
All 6 portal chama views and all 4 admin group views exist **only** under `themes/adminlte/`. Under `theme_view()`, a dashlite install falls back to `sacco::group.index` → `Modules/Sacco/Resources/views/group/index.blade.php`, which does not exist → `ViewNotFoundException`.

> **This was over-stated, and fixing it as scoped would achieve nothing.** The original framing implies that adding 10 dashlite views would make chama work on that theme. It would not, because **dashlite cannot be activated and has no layouts**:
>
> - `ThemeController::index()` builds its list from `base_path('themes')` directories that contain a `theme.json`. The root `themes/` directory contains **only `adminlte`** — there is no `themes/dashlite/` at all, so dashlite never appears in the switcher and cannot be activated.
> - The `core.active_theme` setting is seeded with `'displayed' => '0'`, so it is not editable through the settings UI either. The only way to reach dashlite is a manual database edit.
> - Even then it would not boot. `CoreServiceProvider` runs `loadViewsFrom(base_path("themes/{$theme}/views"), 'core')`, and `themes/dashlite/views` does not exist. Every chama view `@extends('core::layouts.master')`, and that layout exists **only** at `themes/adminlte/views/layouts/master.blade.php` — there is no unthemed `Modules/Core/Resources/views/layouts/` and no dashlite `master.blade.php` anywhere in the repository. So a forced dashlite install fails on the *layout* of every admin page, not on chama.
>
> The real gap is a whole-theme port, and it is concentrated in this module: **Sacco has 146 adminlte views against 23 dashlite** (the worst ratio of any module; the app overall is 631 vs 475). Writing the 10 chama views would move Sacco from 23 to 33 dashlite views and still leave the theme unactivatable, layout-less, and ~113 Sacco views short.
>
> Moved to C9 and to P3 of the roadmap. Recorded here rather than silently dropped, because it was originally listed as P0 item 8.

---

## PART B — Chama: missing features (P1)

### B1. Group lending — the core chama use case — does not exist
No table banking, no joint-liability lending, no group-guaranteed loans, no emergency/top-up loans against group savings.

The legacy schema *looks* like it supports this:
```
Modules/Loan/Database/Migrations/...create_loans_table.php:24     $table->bigInteger('group_id')->unsigned()->nullable();
Modules/Savings/Database/Migrations/...create_savings_table.php:27 $table->bigInteger('group_id')->unsigned()->nullable();
Modules/Share/Database/Migrations/...create_shares_table.php:28    $table->bigInteger('group_id')->unsigned()->nullable();
```
But there is **no `groups` table, no `Group` model, and no form field that ever populates them.** The only trace is a stray Vue data property at `Modules/Loan/Resources/views/themes/adminlte/loan/create.blade.php:442`:
```js
group_id: parseInt("{{old('group_id')}}"),   // no matching <select>, no groups to select
```
These are dead columns. `SaccoGroup` is never referenced by the Loan, Savings, or Share modules.

### B2. No fines / penalties subsystem — ⚠️ partially delivered by G10
Required for any real chama: late contribution, missed meeting, late arrival, absent-without-apology, phone-off-during-meeting, failure to take up a turn. Needs a fine type catalogue, per-member accrual, waiver, and collection against the next payout.

**Since G10 the accrual, waiver and per-cycle deduction half exists** — `sacco_group_penalties` records one row per member per cycle for `late_contribution` and `missed_contribution`, with an officer-attributed waiver and an admin register. **Still absent:** a configurable fine-type catalogue (the `penalty_type` enum currently has only the two contribution values), meeting-attendance fines, collection against a member's next payout rather than deduction from the cycle pot, and any member-facing view of their own fines in the portal. See roadmap item 9.

### B3. No group bank/savings account wiring
`sacco_groups.group_savings_id` is defined (`2026_06_08_000001:128`, commented *"Linked savings account for group funds"*) and appears in `SaccoGroup::$fillable`. **It is never read or written by any code in the repository.** Group money has no account to live in.

### B4. No accounting integration
Every other money-moving service posts double entries — `SavingsChannelService`, `TransferService`, `ShareMarketplaceService`, `AgencyBankingService` all call `createJournalEntry()`. **`GroupService` posts zero journal entries.**

- Contributions received: no `Dr Cash/Bank, Cr Group Funds`
- Merry-go-round payout: no `Dr Group Funds, Cr Member FOSA/Cash`
- Bidding discount (the group's income): never recognised
- Fines: never recognised

`SaccoChartOfAccountsTableSeeder` seeds 27 accounts and **none** are group-related — no "Chama Group Funds", no "Merry-Go-Round Suspense", no "Group Fines Income". A SACCO running chamas cannot reconcile them to the general ledger, so the trial balance and balance sheet are materially misstated.

### B5. No group lifecycle management
- **No edit route.** Once created, you cannot change a group's name, type, branch, registration number, `max_members`, `share_value`, `monthly_contribution`, `purpose`, or its chairperson/secretary/treasurer. Only `updateSettings` exists, and it covers just `rotation_model` + `late_payment_penalty`.
- **No dissolve or suspend action**, despite `status` being `enum('active','suspended','dissolved')`.
- **No member role change** (see A5).
- **No group statement** — neither on-screen ledger nor PDF/Excel.

### B6. No reports or exports
`Modules/Sacco/Reports/ReportsLinks.php` registers only three links: SASRA Regulatory Reports, Compliance Dashboard, CAELS Report. **No chama reports.**

The Sacco module has one export class (`RegulatoryExport`). There is no export for:
- Group contribution register / defaulters list
- Rotation history and cycle summary
- Group P&L (contributions in, payouts out, fines, bidding income)
- Per-member group statement
- Group membership roll

The admin contributions page uses plain `paginate(30)` rather than a DataTable, so it has no search, sort, filter, or date range either.

### B7. No automation
`SaccoServiceProvider` schedules 10 `sacco:*` commands — FD maturities, standing orders, AML, share deadlines, earmark expiry, loan classification, loan accruals, liquidity, provisions, compliance reminders. **None touch groups.** Missing:

- Monthly contribution posting across all active groups
- Cycle activation when its date arrives, and rollover to the next cycle
- Cycle completion detection → mark `completed`, open bidding for the next
- Defaulter detection + fine accrual
- Turn reminders to the upcoming beneficiary
- Dormant-group detection
- Year-end group close and carry-forward

### B8. No notifications
Nothing sends an SMS or email for: contribution received, contribution due, turn upcoming, bidding opened/closed, pot disbursed, meeting notice, minutes published, member added/exited. `Communication` module campaigns target `clients` by branch or loan status — there is no group audience.

### B9. Members cannot self-serve
The portal chama surface is 3 read pages and 1 contribution form. Members **cannot**:
- Request to join a group (an admin must add them)
- Place their own rotation bid — `submitBid` requires an admin to supply `client_id`, and there is no portal route
- See their fines or outstanding balances
- Download a group statement
- Record meeting attendance or send an apology
- Propose an agenda item
- View their group's financial position (only their own contributions)

### B10. Officers have no capabilities
`ChamaController` loads `'chairperson','secretary','treasurer'` relations (lines 58–60) and `membership.role` is available, but **the role is never used for authorization or UI gating.** A treasurer sees exactly the same read-only page as an ordinary member. Missing: treasurer confirms cash received; secretary records minutes and attendance; chairperson approves members and signs off a cycle.

There is also no `portal.permission` scoping per group — any member of group A can attempt actions against group B (the `firstOrFail()` on membership blocks reads, but there is no officer-role check anywhere).

### B11. Table banking and investment clubs are labels only
`type` accepts `chama, merry_go_round, table_banking, investment_club, other`, but `GroupService` branches on `type === 'merry_go_round'` and nowhere else. `table_banking` and `investment_club` groups get **no schedule, no lending, no investment behaviour** — they are functionally identical to a plain chama with a contribution list.

`SaccoGroupMember::shares_held` (a `decimal(10,2)`) is **never updated by any code** despite `SaccoGroup::share_value` existing. There is no group share ledger, no group dividend, and no interest distribution on group savings. The `Investment` module (plans, tiers, member investments, profit posting) is entirely separate and never links to a group.

### B12. Welfare fund is not group-aware
`SaccoWelfareProduct`, `SaccoWelfareContribution`, and `SaccoWelfareClaim` are member-level with no `sacco_group_id`. A chama cannot run its own benevolent/welfare fund, which is one of the most common chama purposes.

### B13. Meetings are not group-aware
Beyond the leak in A6, `sacco_meetings` has no `sacco_group_id`, so a chama's regular meetings cannot be separated from SACCO board/AGM meetings. `minutes` is a single free-text column — no document upload, no versioning, no approval. `SaccoMeetingResolution` records a resolution and a number but has **no votes, no movers/seconders, and no per-member position**. `MeetingService::recordAttendance()` accepts `attended` but nothing links an absence to a fine.

### B14. No chama API and no chama USSD
- `Modules/Sacco/Routes/api.php` exposes USSD actions, search, member360, appraisal, earmarks, ATM, M-Pesa C2B/B2C, PesaLink, compliance, KYC, transfer, agency. **No group endpoints.** A mobile app cannot read or contribute to a chama.
- `ussd-service/src/services/menu/handlers/main.menu.ts` hardcodes six options (balance, statement, transfer, loan, bill pay, M-Pesa repayment) and `menu.engine.ts` switches on `'1'`–`'6'`. **No group contribution, no rotation position, no bid.** For the target market — feature phones in Kenya — this is the single most important missing channel.

### B15. Chama is invisible in Member360, teller desk, and search
`Member360Service::getSummary()` returns 25 fields (savings, BOSA, FOSA, shares, loans, wallet, guarantor exposure, arrears, KYC, net position) and **no group dimension.** Therefore:
- The teller desk cannot see or collect a group contribution
- The admin client-360 page omits group membership
- `GlobalSearchService` does not search groups
- The member statement (`EStatementService`, `SavingsStatementService`) excludes group activity

### B16. No data import for groups
`DataImportService` implements `importMembers()` and `unseedBatch()` only. Migrating an existing chama (members, contribution history, rotation position) means manual entry. The import UI at `sacco/import` references chama in its copy but only imports clients.

### B17. Test coverage — partially improved in this branch
At the original review baseline, the repository had **4 test files / 7 methods**: `tests/ExampleTest.php` (1), `tests/Feature/SettingControllerTest.php` (1), `tests/Feature/SaccoGroupRotationServiceTest.php` (1), and `Modules/User/Tests/Unit/AuthorizationServiceTest.php` (4). This branch adds four regression suites and expands the current total to **8 files / 65 methods**. `phpunit.xml` now discovers both `tests/` and `Modules/`; the User authorization test previously imported a nonexistent `Tests\TestCase` and omitted the required `users.name` field, so both were corrected before adding it to discovery.

The added suites cover contribution approval/disbursement (14), meeting visibility (5), cycle penalties/completion (25), and transaction SMS (14). **45 methods cover chama workflows**; 14 cover C1; the remaining 6 are the existing example, settings, rotation and authorization tests. Every module `Tests/Feature` and `Tests/Unit` directory outside User still holds only a `.gitkeep`.

Still largely untested: bidding edge cases, member add/remove, skip/swap, the portal contribution flow, loans, savings, shares, accounting, most payment gateway callbacks, compliance, and the API. A3 and the other P0 defects now have regression coverage, but the rotation test still hand-builds SQLite tables rather than running migrations, so it does not catch schema drift by itself. The new CI workflow runs the migrations on MySQL and then PHPUnit with PHP 7.4, but it does not yet run tests against the legacy SQL dump path. PHP 7.4 is deliberate: `composer.lock` currently rejects PHP 8.2 despite the broader PHP constraint in `composer.json`.

---

## PART C — SACCO: remaining gaps (P1/P2)

The SACCO core is in good shape. These are the real holes.

### C1. Transaction SMS alerts are never sent — ✅ FIXED, see G12
`TransactionNotificationService::notify()` is called from at least seven places — `MpesaC2bService`, `MpesaB2cService`, `PesalinkService`, `TransferService`, `TellerDeskController`, `Portal\SavingsController`. It inserts into `communication_campaign_logs`:
```php
'communication_campaign_id' => null,
'campaign_type' => 'sms',
'status' => 'pending',
```
The only consumer is `campaigns:process` (`ProcessScheduledCampaigns`), which selects `CommunicationCampaign::where('trigger_type','schedule')`. **It never queries the log table for orphans.** No other code selects `status='pending'` from that table.

Result: members receive no SMS for deposits, withdrawals, transfers, M-Pesa, PesaLink, or teller transactions — the messages accumulate in the database forever. This is a high-visibility failure for a digital banking product.

> **The call-site list above was incomplete.** A full grep finds **16 call sites across 9 files**, including `UtilityPaymentService`, `Savings\SavingsController` (both deposit and withdrawal) and `Portal\SavingsController` — so the blast radius was wider than originally recorded. See G12.

### C2. No credit-life / loan insurance (P1)
Zero occurrences of `insurance` or `credit_life` anywhere in `Modules/`. Kenyan SACCOs nearly universally carry credit life cover on the loan book, with a per-loan premium, an insurer, a claim-on-death workflow, and settlement of the outstanding balance. Today death is handled only through the welfare fund (`SaccoWelfareClaim`), which is a separate member-contributed pot with its own qualifying months and waiting period — it does not extinguish loan liability.

### C3. No loan restructuring, refinancing, or take-over (P2)
`LoanController::reschedule_loan()` changes dates and term. There is **no** capitalisation of arrears, no restructure product path (1 file in the repo even mentions "restructur"), no refinancing, no loan take-over from another institution, and no conversion between products. `allow_topup` exists on the product, but there is no structured top-up workflow that nets the old balance.

### C4. No collateral recovery workflow (P2)
`LoanCollateral` and `LoanCollateralType` capture and value security, and `write_off_loan()` / `undo_write_off()` exist. But after write-off there is **no** repossession, valuation-for-sale, auction, recovery-receipt, or apply-recovery-to-written-off-loan path. `collateral_recovery` returns zero hits. Recovery income therefore cannot be recorded against a written-off loan.

### C5. No estate / probate workflow for deceased members (P2)
`deceased` is a valid `member_status_detail` and `WingService::isWithdrawable()` permits BOSA withdrawal on death. But there is no capture of letters of administration or a death certificate as a *gating* requirement, no beneficiary apportionment engine (`SaccoBeneficiary` stores beneficiaries but nothing splits a balance across them by percentage), and no estate account. Payout to a deceased member's estate is currently an unstructured manual withdrawal.

### C6. Known copy-paste bugs from the June audit — severity corrected (P1)
Re-verified against current `main`. **Three of the five were misclassified as "Critical" in the June audit: they are orphaned dead code, not reachable pages.** No controller or route references them, so no user could ever load them.

| # | Bug | Reachable? | Resolution |
|---|-----|-----------|------------|
| 1 | `FlutterwaveController::webhook()` reads `STRIPE_SIGNATURE`, does `new Stripe()` (not imported), calls `\Stripe\Webhook::constructEvent()` | **Yes** — routed at `webhooks/flutterwave` | **Fixed** — rewritten with `verif-hash` validation and API re-verification (see Part G) |
| 2 | `Modules/Mpesa/Routes/web.php` registers `get_loan_intent` and `get_access_token`; `MpesaController` defines only `capture_payment()` and `webhook()` | Routes registered, but **nothing calls them** — no blade, no JS, no PHP | **Fixed** — both routes removed (see Part G) |
| 3 | `Savings/.../withdrawal/index.blade.php` byte-identical to `deposit/index.blade.php` | **No** — `savings::withdrawal.index` and `savings::deposit.index` are referenced by no controller; only `withdrawal.create/edit` and `deposit.create/edit` are | **Fixed** — deleted both orphans (4 files incl. dashlite) |
| 4 | `savings_linked_charge/index.blade.php` and `share_linked_charge/index.blade.php` are copies of the loan version (28 `loan::*` refs each) | **No** — and neither is `loan_linked_charge.index`, the supposed "correct" original | **Fixed** — deleted all 4 orphans (incl. dashlite) |
| 5 | Orphaned Savings report views in the User module (`report/balance`, `report/transaction`, `report/balance_pdf`) | **No** — `User\ReportController` only references `report.index`, `report.performance`, `report.performance_pdf` | **Fixed** — deleted 3 orphans |

Verification method: extracted every `theme_view('module::path')` call across `Modules/` and `app/`, resolved each against both theme directories, and cross-checked `Modules/*/Routes/*.php`. The reachability question was not asked in the June audit, which is why dead templates were scored as critical defects.

### C7. Dual API authentication still unresolved (P1)
`api/v1/login|register|logout` (User module) and `api/v1/logind|registers|logoutd` (Api module) both exist. They assign role `"client"` vs `"Client"`, one creates a `Client` + `ClientUser` record and the other does not, one enables 2FA and the other does not, and they return different error shapes (400 vs 422). Any mobile app built against this will create inconsistent member records depending on which endpoint it happened to call.

### C8. `modules_statuses.json` is inconsistent (P3)
Contains `"Nice": true` with no `Modules/Nice/` directory, and both `"Share": true` and `"Shares": true`. Depending on module load order this produces warnings or a phantom module.

### C9. The Dashlite theme is unactivatable and half-built (P2)
- `themes/` at the app root contains **only `adminlte`**. There is no `themes/dashlite/theme.json`.
- `ThemeController::index()` enumerates `base_path('themes')` directories reading `theme.json` — so Dashlite **can never appear in the theme list or be activated.**
- Yet **389 module views** are written under `themes/dashlite/` and `@extends('core::layouts.master')`.
- Conversely, **157 view-paths exist only in adminlte** and will throw `ViewNotFoundException` if dashlite were ever activated: **125 in Sacco**, 8 Portal (incl. all 6 chama views), 7 Payroll, 6 Loan, 4 Client, 3 Savings, 3 User, 2 Dashboard, 2 Upgrade, 1 Core.

Decide: delete dashlite (389 dead files) or finish it (157 missing views + a root theme). The current state is dead weight plus a latent crash source.

Raw file counts measured 2026-10-14 corroborate the view-path analysis: **631 adminlte views vs 475 dashlite** app-wide, and the deficit is overwhelmingly in this module — **Sacco 146 vs 23**, Portal 80 vs 75, Loan 81 vs 76, Payroll 25 vs 18. Note also that `Modules/Core/Resources/views/layouts/` does not exist: `core::layouts.master` resolves *only* from `themes/adminlte/views/layouts/master.blade.php`, which is why a forced dashlite install fails on every page's layout rather than merely on the views it lacks.

**A8 (chama views missing on dashlite) is a subset of this, not a separate P0** — see G11 for why it was reclassified rather than implemented.

### C10. `ulm.sql` predates the Sacco module (P2)
The 446 KB / 114-table dump contains **no `sacco_*` tables**. `deploy.sh` restores the dump and then runs migrations, so a fresh install depends on ~40 Sacco migrations applying cleanly on top of a legacy schema. The GitHub Actions workflow added in this branch runs the full migration chain on a fresh MySQL 8 database, but does **not yet** exercise the dump-then-migrate deployment path. The `hasColumn`/`hasTable` guards scattered through those migrations make that dump-based path especially important to cover.

---

## PART D — Platform engineering gaps (P2/P3)

| Item | Finding |
|------|---------|
| **Secrets in git history** | `sacco-core/code/.env` was tracked and contained the committed `APP_KEY=base64:M4NMiZg+8ZtNKeo72FMt7Bh1hGP4KPPqFOQN4LDfRTM=`. This branch removes it from HEAD and ignores future `.env` files, but the key remains in history: rotate it and purge history. |
| **Hardcoded production IP** | Removed from `docker-compose.yml`, `deploy.sh` and `.env.docker.example` in this branch, but `sacco-core/run.sh` and `sacco-core/fix-permissions.sh` still reference `149.102.129.28`. |
| **Default credentials** | Compose now requires `DB_HOST`, `DB_USERNAME` and `DB_PASSWORD` instead of defaulting to `ulm`/`ulm`; `.env.example` still contains placeholder `admin`/`password` values for the current and legacy DB connections, which must never be used in production. |
| **CI (partial, added in this branch)** | `.github/workflows/php-tests.yml` installs Composer dependencies on PHP 7.4, runs the full migration chain against MySQL 8, and runs PHPUnit on pull requests. It does not yet exercise the dump-then-migrate path, static analysis/formatting, or the USSD build. |
| **Composer/PHP constraint mismatch** | `composer.json` allows PHP 8, but the committed lock pins packages whose `php` constraints are `^7` only; `composer install` fails on PHP 8.2. The new CI targets PHP 7.4 without bypassing platform checks. Refresh and test the dependency lock before claiming PHP 8 compatibility. |
| **Test coverage** | 8 test files (65 methods) for ~2,300 PHP files, including 45 chama tests; no tests yet for loans, savings, shares, accounting, payments, compliance, or the API. This branch also adds module-directory discovery so the four `Modules/User/Tests/` methods actually run; other modules' `Tests/` contain only `.gitkeep`. |
| **Queue driver mismatch** | `config/queue.php` defaults to `sync`, while `.env.example` and `docker-compose.yml` set `QUEUE_CONNECTION=database` (Compose also runs a worker). A non-Docker install can silently run everything inline, including STK pushes, if it has no worker. |
| **Rate limiting** | Only the global `throttle:60,1` on the `api` group. No tighter limit on `api/v1/login`, `api/v1/logind`, `api/v1/sacco/ussd`, or the M-Pesa/ATM/PesaLink webhook endpoints. |
| **Test schema drift** | `SaccoGroupRotationServiceTest` hand-builds SQLite tables instead of running migrations, so schema/code mismatches (A3) cannot be caught by tests. |
| **Dead scheduler entry** | `app/Console/Kernel.php:38` has `//$schedule->command('penalties:process')->daily();` commented out, and the real command is `loan_penalties:process` (scheduled correctly in `LoanServiceProvider`). Harmless, but confusing. |
| **Unscheduled commands** | `sacco:backfill-member-numbers`, `loan_schedules:fix`, and `rbac:upgrade` are registered but never scheduled — they are one-shot maintenance tools, which is fine, but this is undocumented. |

---

## PART E — What already works (do not rebuild)

Recorded so the roadmap does not duplicate existing capability.

**Members & KYC** — clients, identification types, next of kin, relationships, custom fields, professions, titles, member numbering (`MemberNumberService`), KYC requirements/documents/verification, CRB checks (`CrbIntegrationService`, `SaccoCrbCheck`), IPRS and NIDA integration, dormancy rules and logging.

**Loans** — products with linked charges and credit checks, funds, purposes, collateral and collateral types, guarantors with share locking, applications, appraisal (multiplier / ⅔ rule / guarantor coverage — enforced at application line 344 and approval line 834), approval + undo, rejection + undo, withdrawal + undo, disbursement + undo, rescheduling, write-off + undo, repayment posting/reversal, charge waiver, interest waiver, loan calculator, officer history, files, notes, top-up flags, balloon payments, grace periods, transaction processing strategies, loan classification (CAELS), NPL provisioning, accruals.

**Savings & Shares** — products, charges, linked charges, transactions, deposits/withdrawals, interest calculation and posting, fixed deposits with maturity processing, BOSA/FOSA wing separation with non-withdrawable BOSA rules and lock-in periods, share products, share transactions, share market periods, a share marketplace with listings/direct sales/transfers and approval, share deadlines.

**Accounting** — chart of accounts with cash-flow mapping, opening balances, budgets and variance, journal entries with reversal, trial balance, income statement, balance sheet, cash flow, ledger, account statements, PDF export for all of the above.

**Payments & channels** — M-Pesa C2B (validate/confirm), B2C (result/timeout), STK, float accounts and float ledger, M-Pesa reconciliation with items and audit log, PesaLink, RTGS, cheques, debit cards with PIN logs/disputes/requests/transactions, ATM, utility payments with providers, standing orders, checkoff batches and items, agency banking with agents/commissions/commission ledger/agent KYC/agent member registration/agent transactions, an agent portal properly scoped by `Auth::id()` with 403 on non-agents, teller desk with sessions and receipts, OTP for transactions, wallet.

**Compliance & governance** — prudential ratios (core capital absolute / to assets / to deposits, institutional capital, liquidity, non-earning assets, external borrowing, single-member exposure) with traffic lights, CAELS classification and reporting, SASRA report templates and export (PDF/Excel), regulatory approvals, board resolutions, auditor appointments, fit-and-proper records, license renewals, inspections with actions, compliance calendar, compliance audit log with middleware, AML alerts and monitoring, prohibited-business guard, maker-checker with pending approvals, financial periods and period-end closing.

**Member-facing** — a large portal (36 controllers): loans, savings, shares, share marketplace, wallet, statements, earmarks, transfers, member transfers, M-Pesa send/withdraw, PesaLink, cheques, cards, ATM history, checkoff, dividends, fixed deposits, exit settlement, beneficiaries, cross-sell (open BOSA), KYC, OTP, transaction PIN, deposits, utility payments. Plus a USSD gateway with Redis sessions, PIN auth, Africa's Talking SMS, balance/statement/transfer/loan/utility/STK.

**Other** — payroll, income, expense, assets with maintenance and valuations, communication campaigns with business rules and SMS gateways, activity log with context columns and an audit logger, reports hub aggregating module `ReportsLinks`, dashboard widgets, custom fields, branches, users/roles/permissions with an RBAC upgrade command, global search, member360, e-signature, data import with batch unseed, and a comprehensive demo data seeder (15 seeders).

---

## PART F — Prioritized roadmap

### P0 — Stop the bleeding ✅ Code work done; maintainer-only security actions remain — see Part G

> **Note on numbering.** This P0 task list and the Part A defect list are *not* the same eight things. Part A enumerates the eight broken chama code paths (A1–A8); the list below enumerates eight pieces of work, which cover A1, A2, A3, A5, A6 plus the platform-secret item (D) and the copy-paste bugs (C6), and it folds A7 in as item 8. **A4 and A8 were never in this list** — A4 was scoped as P1 item 9 and A8 as P2 item 29.
>
> Status of the Part A defects themselves, which is the more useful measure:
>
> | Defect | Status | Where |
> |--------|--------|-------|
> | A1 M-Pesa push path | ✅ fixed | G1 |
> | A2 no approval queue | ✅ fixed | G2 |
> | A3 out-of-enum `paid` | ✅ fixed | G3 |
> | A4 flat late penalty | ✅ fixed | G10 — delivered ahead of its P1 slot |
> | A5 unguarded actions | ✅ fixed | G4 |
> | A6 meeting leak | ✅ fixed | G5 |
> | A7 import corrupts membership | ✅ fixed | G6 |
> | A8 dashlite chama views | ⬇️ reclassified | G11 — not a P0; see C9 |
>
> **7 of 8 Part A defects fixed; A8 reclassified as dead-code cleanup.** A4 was pulled forward from P1 because it turned out to be money-integrity work, not a feature: it was also the only reason `fixed`/`random` groups could never close a turn.

1. ✅ **DONE — Implemented `MpesaStkService::pushGroupContribution()`** and a matching C2B/STK callback branch; the callback verifies the result with Daraja before posting. (A1, G1)
2. ✅ **DONE — Added the pending-contribution approval queue** with approve / reject / reverse actions and `sacco.groups.approve`. (A2, G2)
3. ✅ **DONE — Fixed the enum/status write.** The disbursement path writes the legal `completed` status and tracks payout separately, so the re-disbursement guard works. (A3, G3)
4. ✅ **DONE — Permission-gated the GroupController actions**, removed the phantom middleware entry and implemented the missing member-role action. (A5, G4)
5. ✅ **DONE — Added `sacco_group_id` to `sacco_meetings`** and scoped the Portal list and direct-show path to member-visible group meetings. (A6, G5)
6. ⚠️ **PARTIAL — `.env` is untracked and the compose/deploy/example IP defaults are removed, but maintainer actions remain:** rotate `APP_KEY`, purge it from git history, remove the residual IP in `sacco-core/run.sh` and `fix-permissions.sh`, and replace `.env.example`'s placeholder DB credentials before deployment. (D, G8)
7. ✅ **DONE — Fixed the live copy-paste bugs** (Flutterwave webhook, M-Pesa dead routes, unreachable savings/share linked-charge and report views). (C6, G7)
8. ✅ **DONE — Fixed the group import**: writes `joined_date`, honours capacity, rebuilds the schedule, and backfills existing zero dates. (A7, G6)

### P1 — Make chama real (~4–6 weeks)
9. **Per-member fine ledger** — ⚠️ **partially delivered by G10; extend, do not rebuild.** `sacco_group_penalties` now exists with accrual on late/missed contribution, waiver with officer and reason, per-cycle deduction, and an admin register. **Remaining:** widen `penalty_type` into a configurable fine-type catalogue (missed meeting, late arrival, absent-without-apology, failure to take up a turn), collect fines against a member's next payout rather than only deducting from the cycle pot, and expose a member's own fines in the portal. Also resolve the two open policy questions in G10 (penalty direction, and pot-vs-cash payout base). (A4, B2)
10. **GL integration**: seed group chart-of-accounts (Group Funds liability, Merry-Go-Round Suspense, Fines Income, Bidding Discount Income) and post double entries on contribution received, payout disbursed, and fine collected — mirroring `SavingsChannelService::createJournalEntry()`. (B4)
11. **Wire `group_savings_id`**: open a real Savings account per group, and route contributions and payouts through `SavingsChannelService` so balances and statements are correct for free. (B3)
12. **Group lifecycle**: edit route + form for all group fields, suspend/dissolve with a member-balance settlement step, member role change. (B5)
13. **Chama cron**: `sacco:post-group-contributions` (monthly), `sacco:advance-rotation-cycles` (daily — activate due cycles, close completed ones, open bidding), `sacco:accrue-group-fines` (daily), `sacco:group-turn-reminders` (weekly). Register in `SaccoServiceProvider::boot()`. (B7)
14. **Notifications**: contribution received/due, turn upcoming, bid opened/won/lost, pot disbursed, meeting notice. Route through a fixed `send_sms()` call — do **not** use the orphan-log pattern that broke C1. (B8)
15. **Fix C1** — ✅ **DONE, see G12.** `TransactionNotificationService` now calls `send_sms()` directly rather than writing an orphan log, records a truthful `sent`/`failed` status, and defers outside any open transaction. The queued-job option was rejected deliberately: `QUEUE_CONNECTION=database` is configured but no worker runs, so queueing would have reproduced the same silent failure. The drain-orphans command option was also rejected, since it would retrospectively text members about stale transactions. This unlocks SMS for the whole SACCO, not just chamas.
16. **Portal officer roles**: gate treasurer (confirm cash), secretary (minutes, attendance), chairperson (approve member, sign off cycle) on `membership.role`. (B10)
17. **Member self-service**: join request, place own bid, view fines, download group statement, record attendance/apology. (B9)
18. **Group reports + exports**: contribution register, defaulters, rotation history, group P&L, member group statement — as a `ReportsLinks` entry plus an Export class and PDF views. (B6)
19. **Member360 / teller / search integration**: add a group dimension to `Member360Service::getSummary()` and `getBulkSummaries()`, surface it on the teller desk and client-360 page, index groups in `GlobalSearchService`, include group activity in statements. (B15)
20. **Consolidate API auth** to one controller and one route set; deprecate `logind`/`registers`/`logoutd`. (C7)
21. **Credit-life insurance module**: insurer, product-level premium rate, per-loan premium accrual, death claim → loan settlement. (C2)
21a. **Verify all unauthenticated payment webhooks** (found while implementing G1): `MpesaC2bService::confirm()` posts a real FOSA deposit from an unverified request body; `mpesa/b2c/result`, `pesalink/inbound` and the unsigned gateway webhooks are the same shape. Add IP allowlisting or server-side re-query for Daraja and confirm each gateway validates its signature. Pre-existing and live, so it needs a maintenance window and per-gateway verification rather than a blanket change. (G1 callout)

### P2 — Complete the platform (~4–8 weeks)
22. **Group lending**: populate `loans.group_id` against `sacco_groups`, add a group loan product type with joint liability, and let group savings act as collateral in `LoanAppraisalService`. Delete or repurpose the dead `savings.group_id` / `shares.group_id`. (B1)
23. **Table banking and investment club behaviour**: differentiate by `type` — share purchase ledger that actually updates `shares_held`, group dividends/interest, group investment linked to the `Investment` module. (B11)
24. **Group welfare fund**: add `sacco_group_id` to welfare products/contributions/claims. (B12)
25. **Meeting completeness**: minutes document upload, resolution movers/seconders and votes, absence → fine linkage. (B13)
26. **Chama API + USSD**: group endpoints under `api/v1/sacco/groups`, and USSD menu items 7 (My Chama) and 8 (Contribute to Chama) with a `group.menu.ts` handler. (B14)
27. **Data import for groups**: members, contribution history, current rotation position. (B16)
28. **Loan restructuring**: arrears capitalisation, product conversion, top-up netting; **collateral recovery**: repossession → sale → apply recovery; **estate workflow**: document gating, beneficiary apportionment, estate account. (C3, C4, C5)
29. **Resolve the theme question** — ⬇️ **absorbs the former P0 item 8; see G11 for why it was reclassified.** Either delete the 389 dead `themes/dashlite` module views, or commit to a real port: a root `themes/dashlite/` with `theme.json` and `views/layouts/master.blade.php` (without which nothing renders, since `core::layouts.master` currently resolves only from adminlte), plus the 157 adminlte-only view paths — 123 of them in Sacco alone. Building just the 10 chama views achieves nothing on its own. (C9, A8)
30. **CI — partially delivered in this branch.** `.github/workflows/php-tests.yml` now runs `composer install` on PHP 7.4, the full migration chain against a fresh MySQL 8 database, and the complete discovered PHPUnit suite (application + module tests) on pull requests. Remaining: exercise the dump-then-migrate path, add static analysis/format checks and `npm run build` for the USSD service, rewrite the rotation test to run real migrations, and refresh the lock file before targeting PHP 8. (C10, D)

### P3 — Hygiene
31. Fix `modules_statuses.json` (`Nice`, duplicate `Share`/`Shares`); align `QUEUE_CONNECTION` defaults; add per-endpoint rate limits on auth and webhook routes; document the one-shot maintenance commands; remove the commented `penalties:process` line. (C8, D)
32. Test coverage for the money paths: disbursement, repayment, reversal, provisions, dividends, exit settlement, reconciliation, and every gateway webhook.

---

## Summary counts

| Category | Count |
|----------|-------|
| Chama defects (broken code paths) | **8** |
| Chama missing feature areas | **17** |
| SACCO missing feature areas | **10** |
| Platform/engineering gaps | **11** |
| Still-open bugs from the June 2026 audit | **0** — all 5 resolved (see corrected C6 and Part G) |
| Existing tests | **8 files, 65 test methods** — 45 cover chama |
| P0 items remediated | **7 of 8** Part A defects fixed (G1–G10); A8 reclassified as dead-code cleanup (G11) — 2 follow-ups need maintainer action |
| Highest-value P1 item (C1 transaction SMS) | ✅ **fixed** (G12) |

**Bottom line (updated after Parts G1–G12):** the P0 batch is closed — 7 of the 8 Part A defects are fixed and the eighth was a mis-triage, not a pending task — and **C1, the highest-value P1 item, is fixed too**. Transaction SMS alerts now actually send across all 16 call sites.

With P0 and C1 done, the SACCO half of this product is sellable, subject to the three caveats below. The Chama half is no longer *broken*, but it is still a UI sketch over a partial data model. It needs the rest of P1 (the remainder of the fine ledger, GL posting, group accounts, lifecycle, cron, notifications, officer roles, reports, self-service, and USSD/API channels) before it can manage a real chama, and P2 items 22–23 before it can do what chamas actually exist for: **lending to each other**.

Three things need a human decision rather than more code:

1. **Two money-handling policy questions** — see G10. Whether a fine should be deducted from the beneficiary's payout or collected from the defaulter, and whether payouts should be based on the scheduled pot or on cash actually collected. Both are recorded and surfaced but deliberately not changed.
2. **Two maintainer-only security actions** — see G8. Rotate `APP_KEY` (the committed key must be treated as compromised) and purge `.env` from git history. Both are destructive or need a maintenance window.
3. **Pre-existing unverified payment webhooks** — see roadmap item 21a. `MpesaC2bService::confirm()` posts a real FOSA deposit from an unverified request body, and `mpesa/b2c/result`, `pesalink/inbound` and the unsigned gateway webhooks are the same shape. Not touched here; needs a maintenance window and per-gateway verification.

Also note that deploying G12 starts real SMS traffic and real per-message cost. Set `SACCO_SMS_TRANSACTION_ALERTS=false` at deploy if that is not wanted immediately.

One caveat applies to local verification: **there is no PHP runtime in this environment**, so `phpunit` and Composer have not been run locally. This branch adds a GitHub Actions workflow that will run Composer install, the MySQL migration chain, and PHPUnit on the open PR; its check result must be reviewed before merge. See the verification note at the end of Part G for the mechanical checks done in this environment and the remaining CI scope.

---

## PART G — P0 remediation applied (2026-10-14)

The P0 items from Part F have been implemented. Each entry names the defect it closes and the files touched.

### G1. M-Pesa chama contributions now work (closes A1)

`MpesaStkService::pushGroupContribution()` **implemented** — it was called by the portal but did not exist.

- `Modules/Sacco/Services/MpesaStkService.php` — new method plus three extracted helpers now shared with `pushLoanRepayment()`: `credentials()` (validates all five Daraja settings and names the missing one), `accessToken()`, `normalisePhone()` (handles `254…`, `07…`, and bare `7…`/`1…` forms). Persists `CheckoutRequestID` and `MerchantRequestID` on the contribution so the callback can be matched.
- `Modules/Sacco/Http/Controllers/MpesaC2bController.php` — new `stkGroupContribution()` callback. Parses `Body.stkCallback`, re-matches on `CheckoutRequestID`, handles non-zero `ResultCode` (member cancelled/timed out) by annotating rather than posting, and always answers 200 so Daraja stops retrying. Writes to `AuditLogger`.
- `Modules/Sacco/Routes/api.php` — `POST api/v1/sacco/mpesa/stk/group-contribution`.
- Migration `2026_10_14_000003` adds `checkout_request_id` (indexed), `merchant_request_id`, `mpesa_receipt_number`.

**Callback is verified against Daraja, not trusted.** The STK callback URL is unauthenticated — Safaricom does not sign it — so the payload alone is spoofable, and this endpoint posts money. `MpesaStkService::queryStkStatus()` re-queries `/mpesa/stkpushquery/v1/query` with the `CheckoutRequestID` and the controller only calls `confirmMpesaContribution()` when Daraja reports `ResultCode 0`. It **fails safe**: an unreachable API, missing credentials, or any thrown error yields `verified = false`, so the contribution stays `pending` for an officer instead of posting unconfirmed money. The endpoint still answers 200 so a genuine-but-unverifiable callback is not retried forever.

> **Related exposure found while implementing this (new finding, not previously in this document):**
> `MpesaC2bService::confirm()` — reached from the already-live `POST api/v1/sacco/mpesa/c2b/confirm` — performs **no** signature, IP or re-verification check. It trusts `TransID`, `TransAmount` and `BillRefNumber` from the request body and posts a real deposit into the member's FOSA account via `SavingsChannelService::postDeposit()`. A forged POST with a guessed paybill reference would credit an arbitrary member. The same applies to `mpesa/b2c/result`, `pesalink/inbound`, and the Stripe/PayPal/Paynow/Flutterwave webhook routes that do not verify a signature.
>
> This is **not fixed here** — it is a pre-existing condition on live endpoints, and changing it could break payments in production if the gateway is currently sending something other than what the docs describe. It needs its own ticket: verify Daraja C2B callbacks (IP allowlist or re-query), and confirm each gateway webhook validates its signature. Added to Part F as P1 item 21a.

### G2. Pending contributions can be approved, rejected, reversed (closes A2)

- `Modules/Sacco/Services/GroupService.php` — `approveContribution()`, `rejectContribution()`, `reverseContribution()`, and `confirmMpesaContribution()` (idempotent; on amount mismatch it leaves the row `pending` with an explanatory note rather than silently posting a different figure than the member authorised).
- `Modules/Sacco/Http/Controllers/GroupController.php` — four new actions, all scoped by `where('sacco_group_id', $id)` so one group's contribution id cannot be actioned through another group's URL.
- `contributions()` rewritten from a read-only `paginate(30)` table into a server-side DataTable (`get_contributions`) with status and period filters, and per-row approve / reject / reverse controls gated on `sacco.groups.approve`.
- Migration `2026_10_14_000003` adds `approved_by_id`, `approved_at`, `rejection_reason`, and widens the status enum to include `rejected` — reusing `reversed` would have conflated "never accepted" with "accepted then unwound".
- `Modules/Sacco/Entities/SaccoGroupContribution.php` — new fillables, `approvedBy()` relation, `pending()` / `posted()` scopes.

### G3. Rotation disbursement no longer corrupts state (closes A3)

- Migration `2026_10_14_000001` widens `sacco_merry_go_round_cycles.status` to include `paid`, and repairs rows already truncated to `''` (→ `paid` when `disbursed_at` is set, otherwise `completed`). `down()` collapses `paid` back before narrowing, so the rollback cannot re-corrupt.
- The "already disbursed" guard in `disburseCycle()` therefore now fires, and disbursed cycles appear in the portal's `whereIn(['completed','paid'])` history.

### G4. Group actions are permission-gated (closes A5)

- `GroupController` middleware now covers all seven previously-unguarded actions, including `disburseCycle` (money movement):

| Action | Permission |
|--------|-----------|
| `contributions`, `get_contributions` | `sacco.groups.contributions` |
| `approveContribution`, `rejectContribution`, `reverseContribution` | `sacco.groups.approve` |
| `swapTurns`, `skipTurn`, `submitBid`, `resolveBidCycle` | `sacco.groups.bidding` |
| `disburseCycle` | `sacco.groups.disburse` |
| `updateSettings`, `updateMemberRole` | `sacco.groups.manage` |

- The phantom `storeMember` entry is removed. `updateMemberRole` is **implemented** (it was named in the middleware but did not exist), and keeps the denormalised `chairperson_client_id` / `secretary_client_id` / `treasurer_client_id` pointers on the group in step with the membership row so the portal and reports cannot disagree.
- Migration `2026_10_14_000004` seeds the four new permissions and grants them to `admin`; `Modules/Sacco/Config/permissions.php` registers them for the RBAC UI.
- `group/show.blade.php` gains an inline role selector per member, gated on `sacco.groups.manage`.

### G5. Portal meeting leak closed (closes A6)

- Migration `2026_10_14_000002` adds nullable `sacco_group_id` (indexed, FK `ON DELETE SET NULL`, MySQL-guarded) to `sacco_meetings`.
- `SaccoMeeting::scopeVisibleToMember()` plus `MEMBER_VISIBLE_TYPES = ['agm','sgm','other']`. A member sees meetings of groups they **actively** belong to, plus SACCO-wide meetings of a general type. Board, credit-committee, supervisory and management minutes are no longer reachable.
- `ChamaController::meetings()` uses the scope and returns an empty set when no member profile is linked (previously it returned everything). `showMeeting()` now enforces the same rule with a 403, closing the direct-URL path.
- `MeetingController::create()`/`store()` accept and validate `sacco_group_id`; `meeting/create.blade.php` gains the selector with an explanation of the visibility rule.

### G6. Bulk import no longer corrupts group membership (closes A7)

- `DataImportService::importMembers()` now writes `joined_date`, `role`, and `shares_held` on enrollment — `joined_date` was the NOT NULL column being silently zero-filled, and it is the sort key for `fixed` rotation.
- Adds the `max_members` capacity check that `GroupService::addMember()` performs but the bulk path bypassed; over-capacity rows are skipped and reported in the import error list rather than silently dropped.
- Rebuilds the merry-go-round schedule for every group that gained members, so imported members actually get a turn.
- Migration `2026_10_14_000005` backfills `joined_date` from `created_at` for rows already written as `'0000-00-00'`, with a `CURDATE()` fallback.

### G7. Live copy-paste bugs fixed (closes C6 items 1–5)

- `FlutterwaveController::webhook()` rewritten for Flutterwave: validates the `verif-hash` header against `flutterwave.webhook_secret_hash` using `hash_equals`, ignores non-`charge.completed` events with a 2xx so they are not redelivered forever, and re-verifies the transaction against the Flutterwave API before posting — the payload amount is never trusted. Returns proper JSON responses instead of `http_response_code()` + `exit()`.
- `Modules/Mpesa/Routes/web.php` — the two dead routes removed. `get_access_token` was deliberately **not** implemented: returning a Daraja OAuth token to a browser would expose a server-side credential.
- Deleted 11 orphaned views confirmed unreachable (see the corrected C6 table).

### G8. Secrets and config hygiene (closes the P0 portion of D)

- `sacco-core/code/.gitignore` was **completely empty** — the reason `.env` was tracked. Populated with the standard Laravel 8 set; added `sacco-core/.gitignore` for the deployment env.
- `sacco-core/.env`, `sacco-core/code/.env` and `code/.phpunit.result.cache` untracked via `git rm --cached`; files remain on disk.
- Hardcoded production IP `149.102.129.28` removed from `docker-compose.yml`, `deploy.sh` and `.env.docker.example`. `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` now use `${VAR:?…}` so compose **fails fast** instead of silently defaulting to `ulm`/`ulm`. `deploy.sh` derives its printed URLs from a new `APP_HOST`. Residual uses remain in `sacco-core/run.sh` and `sacco-core/fix-permissions.sh` and are still to be removed.
- `.env.example` still has placeholder `admin`/`password` values for both primary and legacy DB connections. They are not suitable for a deployment; Compose now requires explicit `DB_HOST`, `DB_USERNAME` and `DB_PASSWORD`, but the example file still needs a follow-up to make that posture consistent.

> **⚠️ Before pulling this branch: back up your local `.env` files.**
> The untracking commit records a *deletion* of `sacco-core/.env` and `sacco-core/code/.env`. `git rm --cached` keeps them on the machine that ran it, but any other clone that pulls or checks out this commit will have its local copies **deleted from the working tree** — including a deployment server. Copy both aside first, pull, then restore them. They are now gitignored, so they will stay untracked afterwards. (`deploy.sh` also self-heals by copying `.env.docker.example`, but that loses real credentials.)

> **Two items still require action by the maintainers and cannot be done from a branch:**
> 1. **Rotate `APP_KEY`.** The committed key `base64:M4NMiZg+8ZtNKeo72FMt7Bh1hGP4KPPqFOQN4LDfRTM=` must be treated as compromised. Rotating it invalidates existing encrypted cookies/sessions and any encrypted columns, so it needs a maintenance window — it is not a change to make silently from a PR.
> 2. **Purge `.env` from git history.** Untracking stops future leakage but the key remains in every past commit. `git filter-repo` rewrites SHAs and force-pushes `main`, which affects every clone and open branch; that needs explicit sign-off.

### G9. Tests

- `tests/Feature/SaccoGroupContributionApprovalTest.php` — 14 tests: approval, double-approval, rejection gating and reason capture, reversal gating, reversal excluding a contribution from member totals, M-Pesa confirmation, **callback idempotency on a retried delivery**, unknown checkout id, amount-mismatch handling, disbursement recording, **the double-disbursement regression**, and disbursed cycles appearing in member history.
- `tests/Feature/SaccoMeetingVisibilityTest.php` — 5 tests: own-group visibility, governance types never visible, SACCO-wide general meetings visible to all, exited members losing access, group-scoped committee meetings private to their members.
- `tests/Feature/SaccoGroupCyclePenaltyTest.php` — 25 tests covering G10: on-time payment producing no fines, late vs missing classification, **the flat-deduction regression** (three late members must cost 300, not 100), zero-rate groups, assessment idempotency, exited members, pending contributions not counting as paid, cycle completion for `fixed` and `random` models, double-completion, skipped/beneficiary-less/disbursed cycles, highest-bid selection, **the double-resolution regression that used to wipe the winner**, bidding with no bids, fines applying on the bidding path too, waivers recomputing the payout, `collected_amount` shortfalls, and the active scope.
- `tests/Feature/SaccoTransactionAlertTest.php` — 14 tests covering G12: an alert never left `pending`, undeliverable alerts recorded `failed` with the message text intact, a configured gateway actually being attempted, `send_sms()` returning false instead of fataling on a missing setting row / unknown gateway / refused connection, the mobile and config gates, missing `settings` / `sms_gateways` / `communication_campaign_logs` tables not breaking the caller, **the send being deferred until the transaction commits**, **a rolled-back transaction sending nothing**, and immediate delivery outside a transaction.
- All four declare the status columns as **real enums**, mirroring production. The pre-existing rotation test used plain `string` columns, which is exactly why the A3 out-of-enum write was never caught.

### G10. Per-member late-payment fines, and cycle completion for every rotation model (closes A4)

**The defect.** `late_payment_penalty` was read in exactly one place, where it was deducted from the pot as a single flat amount every cycle — regardless of how many members were late, whether anyone was, or who they were. A group with zero defaulters was fined the same as one with five. And because `resolveBiddingCycle()` was the *only* code in the module that ever wrote `status = 'completed'`, groups on the `fixed` or `random` models could never close a turn at all, so their penalty was never applied and their `net_payout_amount` stayed at 0.

**New table `sacco_group_penalties`** (migration `2026_10_14_000006`) — one row per member per cycle: `penalty_type` enum (`late_contribution`, `missed_contribution`), `period`, `amount`, `status` enum (`assessed`, `waived`, `collected`), `reason`, `assessed_by_id`, `waived_by_id`, `waived_at`. A **unique index on `(cycle, member)`** makes double-assessment impossible at the database level rather than by convention in the service. Entity: `SaccoGroupPenalty`, with an `active()` scope (`assessed` + `collected`; `waived` excluded).

**`GroupService::assessCyclePenalties()`** derives fines from evidence. A member is fined when, for the cycle's period (`cycle_date` formatted `Y-m`), they have either no `posted` contribution (missed) or one dated after the cycle date (late). Only `posted` money counts — a contribution still awaiting approval does not shield a member. Only `activeMembers` are assessed, so exited members are not fined. Idempotent: re-assessing adds nothing for a member already fined. Returns the cycle's non-waived total. A zero configured rate assesses nothing.

**`GroupService::completeCycle()`** is the shared completion path for all rotation models. It refuses to reopen a `completed`/`paid` cycle, refuses a `skipped` turn, and refuses a cycle with no beneficiary. It records `penalties_deducted` from the assessed total and computes `net_payout_amount = max(0, pot − bidding_discount − penalties)`. `resolveBiddingCycle()` now keeps only winner selection and delegates the arithmetic here, so `fixed`/`random`/`bidding` all behave identically.

**Two further bugs found and fixed while implementing this:**

1. **`resolveBiddingCycle()` destroyed its own result on a second call.** It selected the highest *pending* bid, marked all pending bids lost, and saved. Called again, it found no pending bids and fell into the `else` branch — setting `beneficiary_client_id = null` and `bidding_discount = 0` on an already-resolved cycle. The winner of a completed pot was silently erased. It now throws `already been resolved` if the cycle is closed, and throws `no pending bids` rather than completing a cycle with a null beneficiary.
2. **The cycle action buttons were gated on the wrong permission.** `group/show.blade.php` wrapped swap/skip/bid/resolve/disburse in `@can('sacco.groups.manage')`, while the routes enforce `sacco.groups.bidding` and `sacco.groups.disburse`. An officer with `manage` saw controls that returned 403; an officer with `disburse` but not `manage` saw none. The buttons now gate on the same permission their route enforces.

**New routes/actions:** `POST sacco/group/{id}/complete-cycle/{cycleId}` → `completeCycle` (gated `sacco.groups.disburse`, since it fixes the amount to be paid); `POST sacco/group/{id}/penalty/{penaltyId}/waive` → `waivePenalty` (gated `sacco.groups.approve`, since waiving cancels money owed). Both scope by group id so one group's record cannot be actioned through another group's URL. Waiving keeps the row as `waived` with the officer and reason recorded, and recomputes the cycle's deduction and payout so the beneficiary is not still charged for a cancelled fine.

**UI:** the cycles table gains Collected, Fines and Net Payout columns, and a new "Late Payment Fines" register lists every fine with its member, cycle, period, reason and status, plus a waiver form.

**Two decisions deliberately left open — they are business questions, not bugs:**

- **Direction of the penalty.** The column is named `penalties_deducted` and the original code subtracted it from the beneficiary's payout, so that semantic is preserved. But it is worth challenging: a fine is normally paid *by* the defaulter *to* the group, and deducting it from the beneficiary means the person who paid on time absorbs other members' lateness. Whether fines should instead be credited to the pot, held in a welfare account, or collected from the defaulter's next payout is a policy decision for the SACCO.
- **Payout base: scheduled pot vs cash collected.** `pot_amount` is theoretical (`monthly_contribution × member count`, written when the schedule is built). The new `collected_amount` records what was actually posted for the period, and the UI flags a shortfall — but `net_payout_amount` still uses `pot_amount`. Basing payouts on cash actually held is more defensible, yet it changes every payout figure, so it was recorded for audit and surfaced rather than applied silently.

Roadmap item 9 (per-member fine ledger) is now **partially delivered**: the accrual, waiver, per-cycle deduction and admin register exist. Still missing are a fine-type catalogue beyond late/missed contribution, collection against the next payout, and portal visibility for members.

### G11. P0 item 8 (dashlite chama views) was reclassified, not implemented

Item 8 was approved as part of the P0 batch and is **deliberately not done**. This is a scope decision worth stating plainly, because the honest conclusion is that the item was mis-triaged rather than that it is still pending.

Writing the 10 missing views (6 portal chama + 4 admin group) would not make chama work on dashlite, for three independently sufficient reasons:

1. **Dashlite cannot be activated.** `ThemeController::index()` builds the switcher list from `base_path('themes')` directories containing a `theme.json`. The root `themes/` directory holds **only `adminlte`**; there is no `themes/dashlite/` at all. The `core.active_theme` setting is seeded `'displayed' => '0'`, so it is not editable via the settings UI either — the sole route to dashlite is a manual database edit.
2. **Dashlite has no layouts.** Every chama view `@extends('core::layouts.master')`, and that layout exists only at `themes/adminlte/views/layouts/master.blade.php`. There is no unthemed `Modules/Core/Resources/views/layouts/` and no dashlite `master.blade.php` anywhere in the repository. `CoreServiceProvider` would call `loadViewsFrom(base_path('themes/dashlite/views'), 'core')` on a path that does not exist.
3. **The deficit is ~113 views in this module alone.** Sacco has 146 adminlte views against 23 dashlite — the worst ratio of any module (app-wide 631 vs 475). Adding 10 brings Sacco to 33 and still leaves the theme unactivatable, layout-less, and overwhelmingly incomplete.

So the cost is a whole-theme port, not a 10-file fix, and the benefit of the 10-file fix in isolation is zero. Item 8 is recorded as **reclassified into C9** and moved to P3 of the roadmap, where the real decision belongs: either delete the 389 dead dashlite module views, or commit to building the root theme plus the missing views.

**P0 status is therefore 7 of 8 items fixed in code, with item 8 reclassified as out-of-scope dead-code cleanup rather than left silently open.** If a client actually wants dashlite, that is a project of its own and should be scoped from C9, not from the chama list.

### G12. Transaction SMS alerts are now actually sent (closes C1)

**The defect.** `TransactionNotificationService::notify()` wrote a row to `communication_campaign_logs` with `status = 'pending'` and `communication_campaign_id = null`, and stopped. The table's only consumer, `campaigns:process`, selects `CommunicationCampaign::where('trigger_type','schedule')` and never looks for orphan log rows; nothing else in the codebase selects `status='pending'` from it. Members received no SMS for any deposit, withdrawal, transfer, M-Pesa, PesaLink, utility or teller transaction, while the log looked like a queue merely waiting to be drained — which is exactly why the failure stayed invisible.

The blast radius was wider than C1 recorded: a full grep finds **16 call sites across 9 files**, not "at least seven" — `MpesaC2bService`, `MpesaB2cService`, `PesalinkService` (×2), `TransferService` (×2), `UtilityPaymentService`, `TellerDeskController`, `Savings\SavingsController` (×2) and `Portal\SavingsController`.

**The fix.** `notify()` now dispatches through the same `send_sms()` helper the campaign processor uses, and writes the audit row with the status that reflects what happened (`sent` or `failed`) instead of permanently `pending`.

**Why synchronous, not queued.** The obvious alternative — dispatch a job — was checked and rejected. `config/queue.php` defaults to `sync` but `.env` sets `QUEUE_CONNECTION=database`, and **no queue worker is scheduled in `app/Console/Kernel.php` or otherwise running**. Queued alerts would park in the `jobs` table indefinitely and reproduce the original bug in a new form. Draining the orphan backlog with a new command was also rejected: it would retrospectively text members about transactions from weeks or months ago.

Because the send is inline on money paths, three constraints are enforced:

- **Tight timeouts.** `send_sms()` previously set no curl timeout at all, so an unreachable gateway could block the caller indefinitely. It now defaults to 5s connect / 15s total and accepts overrides; `notify()` passes **2s / 6s**. This matters specifically for M-Pesa C2B callbacks, which Safaricom retries when they take too long.
- **No locks held.** `TransferService::execute()` calls `notify()` **twice inside an open `DB::transaction()`**, and `PesalinkService` and `MpesaB2cService` each call it once inside one. A synchronous HTTP round trip there would hold row locks on savings, share and loan accounts for as long as the gateway took to answer. `notify()` now checks `DB::transactionLevel()` and defers to `DB::afterCommit()`, so the send happens after the commit. A rolled-back transaction therefore sends nothing — correct, since the member must not be told about a money movement that did not happen. (`afterCommit()` throws if no transactions manager is bound; verified `db.transactions` is registered as a singleton by `DatabaseServiceProvider` in Laravel 8.83, and the binding is still checked at runtime rather than assumed.)
- **No escape.** `send_sms()` also had a latent fatal: it dereferenced `Setting::where(...)->first()->setting_value` with no null check, so an install without a configured gateway would fatal. That path is reachable from payment callbacks. The helper now returns `false` cleanly, and `notify()` additionally pre-resolves the gateway, checks `function_exists('send_sms')` (the Communication module may be disabled), and swallows every `Throwable` — an SMS provider being down, unconfigured or absent can never fail a transaction.

**Backlog.** Migration `2026_10_14_000007` marks the historical orphan rows `failed` rather than leaving them `pending`, so the log stops reading like an undrained queue, and logs the count so the size of the gap is recoverable. They are deliberately **not** retro-sent.

**Configuration.** Sending remains gated by `sacco.sms_transaction_alerts` (`SACCO_SMS_TRANSACTION_ALERTS`, default `true`) and by the member having a mobile number. Note the practical consequence of this fix: **deploying it starts real SMS traffic and real per-message cost** on every transaction path listed above. If that is not wanted immediately, set `SACCO_SMS_TRANSACTION_ALERTS=false` at deploy and enable it deliberately.

### Verification status

No PHP runtime is available in this environment (no `php`, no root for `apt`), so `phpunit` and Composer could not be executed locally. The new `.github/workflows/php-tests.yml` workflow runs Composer, all migrations against MySQL 8, and PHPUnit for both `tests/` and `Modules/` on PHP 7.4; the committed lock rejects PHP 8.2 despite `composer.json` allowing it, so PHP 8 compatibility needs a dependency refresh. The first CI attempt on PHP 8.2 failed at dependency resolution before migrations or tests; the corrected PHP 7.4 run must pass before merge. The workflow intentionally does not yet validate the legacy dump-then-migrate path or the USSD build. Changes were also checked here with a string- and comment-aware brace/paren balance checker across modified and added PHP files, by verifying new symbols and paths, and by re-reading each call site.

Structural checks that were run mechanically, not by eye: every `permission:` string used in `GroupController` exists in `Modules/Sacco/Config/permissions.php`; all 23 `GroupController` actions are permission-gated with no phantom entries and none left unguarded; every `GroupController@method` referenced in `Routes/web.php` exists and every public method has a route; Blade `@if/@endif`, `@can/@endcan`, `@foreach/@endforeach`, `@php/@endphp` and `<section>/<div>/<table>/<form>` tags balance in every edited view; table header and body cell counts match; and no view, route or controller references any of the deleted orphan views.

**The PR's MySQL/PHPUnit check must pass before merge.** Locally, the equivalent sequence is `composer install && php artisan migrate && vendor/bin/phpunit`. The enum-widening migrations are the ones to watch, since they issue raw `ALTER … MODIFY` and are guarded to MySQL only.

One assumption worth confirming during review: `GuzzleHttp\json_decode` is still shipped by Guzzle 7.9.2 (deprecated, but present), so the existing import in `FlutterwaveController` is not a fatal error. The new webhook calls `\json_decode` explicitly anyway, because the Guzzle shim *throws* on malformed input where the global function returns `null` — a bad payload should answer 400, not 500.
