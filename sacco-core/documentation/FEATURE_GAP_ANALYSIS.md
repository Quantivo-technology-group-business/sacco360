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
| **Platform engineering** | ~40% | 2 feature tests for ~2,300 files, no CI, secrets committed, one theme half-built. |

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

### A4. `late_payment_penalty` is applied to the wrong thing
`GroupService::resolveBiddingCycle()` (line 204):
```php
$cycle->penalties_deducted = (float) ($cycle->group->late_payment_penalty ?? 0);
```
This deducts the group's flat penalty **once per bidding cycle, from the pot, regardless of who was late or whether anyone was late.** There is no per-member fine record anywhere in the schema. A group with zero defaulters is fined; a group with five defaulters is fined once.

Also: the penalty is only applied to **bidding** groups. `fixed` and `random` rotation models never apply any penalty at all.

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

### A8. Chama is invisible on the Dashlite theme
All 6 portal chama views and all 4 admin group views exist **only** under `themes/adminlte/`. Under `theme_view()`, a dashlite install falls back to `sacco::group.index` → `Modules/Sacco/Resources/views/group/index.blade.php`, which does not exist → `ViewNotFoundException`. See C9 — the theme situation is worse than this.

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

### B2. No fines / penalties subsystem
Required for any real chama: late contribution, missed meeting, late arrival, absent-without-apology, phone-off-during-meeting, failure to take up a turn. Needs a fine type catalogue, per-member accrual, waiver, and collection against the next payout. **Nothing exists** — see A4.

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

### B17. Test coverage
The repository contains **4 test files**: `tests/ExampleTest.php` (1 test), `tests/Feature/SettingControllerTest.php` (1 test), `tests/Feature/SaccoGroupRotationServiceTest.php` (1 test), and `Modules/User/Tests/Unit/AuthorizationServiceTest.php` (4 tests). Every module `Tests/Feature` and `Tests/Unit` directory outside User holds only a `.gitkeep`.

The single rotation test has **one test method**: `test_generate_schedule_uses_join_date_order_for_fixed_rotation`.

Untested: bidding, bid resolution, penalties, disbursement, contribution posting, member add/remove, skip/swap, the portal contribution flow, loans, savings, shares, accounting, every payment gateway webhook, compliance, the API — and every defect in Part A. Note the rotation test hand-builds its own SQLite schema rather than running migrations, which is precisely why the enum mismatch in A3 was never caught.

---

## PART C — SACCO: remaining gaps (P1/P2)

The SACCO core is in good shape. These are the real holes.

### C1. Transaction SMS alerts are never sent (P1)
`TransactionNotificationService::notify()` is called from at least seven places — `MpesaC2bService`, `MpesaB2cService`, `PesalinkService`, `TransferService`, `TellerDeskController`, `Portal\SavingsController`. It inserts into `communication_campaign_logs`:
```php
'communication_campaign_id' => null,
'campaign_type' => 'sms',
'status' => 'pending',
```
The only consumer is `campaigns:process` (`ProcessScheduledCampaigns`), which selects `CommunicationCampaign::where('trigger_type','schedule')`. **It never queries the log table for orphans.** No other code selects `status='pending'` from that table.

Result: members receive no SMS for deposits, withdrawals, transfers, M-Pesa, PesaLink, or teller transactions — the messages accumulate in the database forever. This is a high-visibility failure for a digital banking product.

### C2. No credit-life / loan insurance (P1)
Zero occurrences of `insurance` or `credit_life` anywhere in `Modules/`. Kenyan SACCOs nearly universally carry credit life cover on the loan book, with a per-loan premium, an insurer, a claim-on-death workflow, and settlement of the outstanding balance. Today death is handled only through the welfare fund (`SaccoWelfareClaim`), which is a separate member-contributed pot with its own qualifying months and waiting period — it does not extinguish loan liability.

### C3. No loan restructuring, refinancing, or take-over (P2)
`LoanController::reschedule_loan()` changes dates and term. There is **no** capitalisation of arrears, no restructure product path (1 file in the repo even mentions "restructur"), no refinancing, no loan take-over from another institution, and no conversion between products. `allow_topup` exists on the product, but there is no structured top-up workflow that nets the old balance.

### C4. No collateral recovery workflow (P2)
`LoanCollateral` and `LoanCollateralType` capture and value security, and `write_off_loan()` / `undo_write_off()` exist. But after write-off there is **no** repossession, valuation-for-sale, auction, recovery-receipt, or apply-recovery-to-written-off-loan path. `collateral_recovery` returns zero hits. Recovery income therefore cannot be recorded against a written-off loan.

### C5. No estate / probate workflow for deceased members (P2)
`deceased` is a valid `member_status_detail` and `WingService::isWithdrawable()` permits BOSA withdrawal on death. But there is no capture of letters of administration or a death certificate as a *gating* requirement, no beneficiary apportionment engine (`SaccoBeneficiary` stores beneficiaries but nothing splits a balance across them by percentage), and no estate account. Payout to a deceased member's estate is currently an unstructured manual withdrawal.

### C6. Known copy-paste bugs from the June audit are all still open (P1)
Re-verified against current `main`:

| # | Bug | Status |
|---|-----|--------|
| 1 | `FlutterwaveController::webhook()` reads `STRIPE_SIGNATURE`, does `new Stripe()` (not imported), calls `\Stripe\Webhook::constructEvent()` | **Still present** — lines 45, 46, 51 |
| 2 | `Modules/Mpesa/Routes/web.php` registers `get_loan_intent` and `get_access_token`; `MpesaController` defines only `capture_payment()` and `webhook()` | **Still present** — both routes 500 |
| 3 | `Savings/.../withdrawal/index.blade.php` is byte-identical to `deposit/index.blade.php` | **Still present** — `diff -q` confirms identical |
| 4 | `savings_linked_charge/index.blade.php` and `share_linked_charge/index.blade.php` are copies of the loan version | **Still present** — 28 `loan::*` references in each |
| 5 | Orphaned Savings report views in the User module (`report/balance`, `report/transaction`, `report/balance_pdf`) | **Still present** — all 3 byte-identical across both modules; `User\ReportController` only implements `index()` and `performance()` |

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

### C10. `ulm.sql` predates the Sacco module (P2)
The 446 KB / 114-table dump contains **no `sacco_*` tables**. `deploy.sh` restores the dump and then runs migrations, so a fresh install depends on ~40 Sacco migrations applying cleanly on top of a legacy schema. There is no CI to prove this. Combined with the `hasColumn`/`hasTable` guards scattered through those migrations, first-run failure is likely and invisible.

---

## PART D — Platform engineering gaps (P2/P3)

| Item | Finding |
|------|---------|
| **Secrets committed** | `sacco-core/code/.env` is **tracked in git** and contains a live `APP_KEY=base64:M4NMiZg+8ZtNKeo72FMt7Bh1hGP4KPPqFOQN4LDfRTM=`. Rotate the key and purge from history. |
| **Hardcoded production IP** | `APP_URL=http://149.102.129.28:8080` in `.env`, and the same IP as the `docker-compose.yml` default for `APP_URL`, `DB_HOST`, `DB_PORT`. |
| **Default credentials** | `docker-compose.yml` defaults `DB_USERNAME`/`DB_PASSWORD` to `ulm`/`ulm`; `.env` also carries `OLD_DB_*` with `admin`/`password`. |
| **No CI** | No `.github/workflows`, no `phpunit`/`phpstan`/`pint` pipeline. The two existing tests never run automatically. |
| **Test coverage** | 4 test files (7 test methods) for ~2,300 PHP files. No tests for loans, savings, shares, accounting, payments, compliance, or the API. Only `Modules/User/Tests/` contains a real module test; every other module's `Tests/` holds only `.gitkeep`. |
| **Queue driver mismatch** | `.env.example` defaults `QUEUE_CONNECTION=sync`; `docker-compose.yml` sets `database` and runs a worker. A non-Docker install silently runs everything inline, including STK pushes. |
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

### P0 — Stop the bleeding (small, surgical, ~1–2 weeks)
1. **Implement `MpesaStkService::pushGroupContribution()`** and a matching C2B/STK callback branch that flips the contribution to `posted` and credits the member. (A1)
2. **Add the pending-contribution approval queue**: `GroupController@contributions` becomes a DataTable with approve / reject / reverse actions, guarded by a new `sacco.groups.approve` permission. (A2)
3. **Fix the enum**: migration to `enum('upcoming','active','completed','skipped','paid')` — or change `disburseCycle()` to write `completed` and track disbursement via `disbursed_at`. Then the re-disbursement guard works. (A3)
4. **Add `permission:` middleware** to `updateSettings`, `swapTurns`, `skipTurn`, `submitBid`, `resolveBidCycle`, `disburseCycle`, `contributions`. Remove the phantom `storeMember` / `updateMemberRole` entries or implement them. (A5)
5. **Add `sacco_group_id` to `sacco_meetings`** and filter `ChamaController::meetings()`/`showMeeting()` by the member's groups. Default existing rows to a SACCO-wide meeting type that members cannot see. (A6)
6. **Rotate `APP_KEY`, untrack `.env`, purge it from history**, and remove the hardcoded `149.102.129.28` from `docker-compose.yml` defaults. (D)
7. **Fix the four open copy-paste bugs** — Flutterwave webhook, M-Pesa missing methods, savings withdrawal view, savings/share linked-charge views. (C6)
8. **Fix the group import**: route `DataImportService` enrollment through `GroupService::addMember()` (or at minimum set `joined_date`, honour `max_members`, and call `rebuildUpcomingSchedule()` for `merry_go_round` groups). Backfill `joined_date` for rows already imported as `'0000-00-00'`. (A7)

### P1 — Make chama real (~4–6 weeks)
9. **Per-member fine ledger**: `sacco_group_fines` (type, member, period, amount, reason, status, waived_by). Accrue on late/missed contribution; deduct at payout; expose in admin and portal. Replaces the broken `late_payment_penalty` logic. (A4, B2)
10. **GL integration**: seed group chart-of-accounts (Group Funds liability, Merry-Go-Round Suspense, Fines Income, Bidding Discount Income) and post double entries on contribution received, payout disbursed, and fine collected — mirroring `SavingsChannelService::createJournalEntry()`. (B4)
11. **Wire `group_savings_id`**: open a real Savings account per group, and route contributions and payouts through `SavingsChannelService` so balances and statements are correct for free. (B3)
12. **Group lifecycle**: edit route + form for all group fields, suspend/dissolve with a member-balance settlement step, member role change. (B5)
13. **Chama cron**: `sacco:post-group-contributions` (monthly), `sacco:advance-rotation-cycles` (daily — activate due cycles, close completed ones, open bidding), `sacco:accrue-group-fines` (daily), `sacco:group-turn-reminders` (weekly). Register in `SaccoServiceProvider::boot()`. (B7)
14. **Notifications**: contribution received/due, turn upcoming, bid opened/won/lost, pot disbursed, meeting notice. Route through a fixed `send_sms()` call — do **not** use the orphan-log pattern that broke C1. (B8)
15. **Fix C1**: either make `TransactionNotificationService` call `send_sms()` directly (or dispatch a queued job), or add a `notifications:dispatch` command that drains orphan `pending` logs. This unlocks SMS for the whole SACCO, not just chamas.
16. **Portal officer roles**: gate treasurer (confirm cash), secretary (minutes, attendance), chairperson (approve member, sign off cycle) on `membership.role`. (B10)
17. **Member self-service**: join request, place own bid, view fines, download group statement, record attendance/apology. (B9)
18. **Group reports + exports**: contribution register, defaulters, rotation history, group P&L, member group statement — as a `ReportsLinks` entry plus an Export class and PDF views. (B6)
19. **Member360 / teller / search integration**: add a group dimension to `Member360Service::getSummary()` and `getBulkSummaries()`, surface it on the teller desk and client-360 page, index groups in `GlobalSearchService`, include group activity in statements. (B15)
20. **Consolidate API auth** to one controller and one route set; deprecate `logind`/`registers`/`logoutd`. (C7)
21. **Credit-life insurance module**: insurer, product-level premium rate, per-loan premium accrual, death claim → loan settlement. (C2)

### P2 — Complete the platform (~4–8 weeks)
22. **Group lending**: populate `loans.group_id` against `sacco_groups`, add a group loan product type with joint liability, and let group savings act as collateral in `LoanAppraisalService`. Delete or repurpose the dead `savings.group_id` / `shares.group_id`. (B1)
23. **Table banking and investment club behaviour**: differentiate by `type` — share purchase ledger that actually updates `shares_held`, group dividends/interest, group investment linked to the `Investment` module. (B11)
24. **Group welfare fund**: add `sacco_group_id` to welfare products/contributions/claims. (B12)
25. **Meeting completeness**: minutes document upload, resolution movers/seconders and votes, absence → fine linkage. (B13)
26. **Chama API + USSD**: group endpoints under `api/v1/sacco/groups`, and USSD menu items 7 (My Chama) and 8 (Contribute to Chama) with a `group.menu.ts` handler. (B14)
27. **Data import for groups**: members, contribution history, current rotation position. (B16)
28. **Loan restructuring**: arrears capitalisation, product conversion, top-up netting; **collateral recovery**: repossession → sale → apply recovery; **estate workflow**: document gating, beneficiary apportionment, estate account. (C3, C4, C5)
29. **Resolve the theme question**: delete `themes/dashlite` (389 files) or build the root dashlite theme plus the 157 missing views. (C9, A8)
30. **CI**: GitHub Actions running `composer install`, `php artisan migrate` against MySQL *and* the dump-then-migrate path, `phpunit`, static analysis, and `npm run build` for the USSD service. Rewrite the rotation test to run real migrations so schema drift is caught. (C10, D)

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
| Platform/engineering gaps | **10** |
| Still-open bugs from the June 2026 audit | **5 confirmed** |
| Existing tests | **4 files, 7 test methods** — only 1 covers chama |

**Bottom line:** the SACCO half of this product could be sold today after P0 items 1–8 and the C1 SMS fix. The Chama half is a UI sketch over a partial data model — it needs P1 in full (fines, GL, group accounts, lifecycle, cron, notifications, officer roles, reports, self-service, and USSD/API channels) before it can manage a real chama, and P2 items 22–23 before it can do what chamas actually exist for: **lending to each other**.
