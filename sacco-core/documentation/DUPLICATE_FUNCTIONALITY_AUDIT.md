# Duplicate Functionality Audit

**Application:** Ultimate Loan Manager v3.0  
**Audit date:** 2026-06-07  
**Scope:** Read-only documentation of duplicate and overlapping functionality  
**Codebase root:** `code/Modules/` (29 Laravel modules)

---

## Executive Summary

This audit identified **five confirmed copy-paste bugs**, **three overlapping authentication surfaces**, **extensive intentional parallel layers** (Admin / Portal / API), and **significant frontend pattern duplication** (147 byte-identical theme view pairs, 86 files with bulk-select Vue logic, 57 DataTables inits).

Duplicates fall into two categories:

| Category | Meaning | Action |
|----------|---------|--------|
| **Intentional** | Same domain exposed through different channels (admin staff, client portal, REST API) | Accept; document to prevent drift |
| **Problematic** | Copy-paste errors, missing methods, wrong templates, divergent auth flows | Fix when development resumes |

---

## 1. Critical Copy-Paste Bugs

Five issues were verified with file comparison and code inspection. Each is a functional defect, not merely maintenance overhead.

### 1.1 Flutterwave webhook uses Stripe code

**Severity:** Critical  
**Impact:** Flutterwave webhook validation will always fail; online Flutterwave loan repayments cannot be confirmed server-side.

**File:** `code/Modules/Flutterwave/Http/Controllers/FlutterwaveController.php`

The `webhook()` method (lines 42–86) is a near-exact copy of `StripeController::webhook()`:

- Reads `STRIPE_SIGNATURE` header (line 45)
- Instantiates `new Stripe()` (line 46) — class not imported; will fatal-error
- Calls `\Stripe\Webhook::constructEvent()` (line 51)
- Handles `payment_intent.succeeded` / `payment_intent.payment_failed` events

**Reference (correct Stripe implementation):** `code/Modules/Stripe/Http/Controllers/StripeController.php` lines 40–84

**Note:** `capture_payment()` in the same Flutterwave controller correctly uses Flutterwave API verification. Only the webhook path is broken.

---

### 1.2 Linked-charge views show loan data in Savings and Share modules

**Severity:** Critical  
**Impact:** Savings and Share linked-charge index pages display loan charges, loan URLs, and loan translations.

**Verified identical files (byte-for-byte):**

| File | Module |
|------|--------|
| `code/Modules/Loan/Resources/views/themes/adminlte/loan_linked_charge/index.blade.php` | Loan (correct) |
| `code/Modules/Savings/Resources/views/themes/adminlte/savings_linked_charge/index.blade.php` | Savings (wrong) |
| `code/Modules/Share/Resources/views/themes/adminlte/share_linked_charge/index.blade.php` | Share (wrong) |

**Evidence in Savings copy:**
- Title uses `trans_choice('loan::general.loan',1)` and `loan::general.charge`
- "Add charge" links to `url('loan/charge/create')`
- Table columns reference `loan_charge_type_id` and loan-specific charge types (disbursement, specified due date, etc.)

Dashlite theme copies at `.../themes/dashlite/` mirror the same problem.

---

### 1.3 Withdrawal index page is an identical copy of deposit index

**Severity:** Critical  
**Impact:** Withdrawal listing page shows wrong title, wrong DataTables endpoint, and journal-entry columns instead of savings withdrawals.

**Verified identical files:**
- `code/Modules/Savings/Resources/views/themes/adminlte/deposit/index.blade.php`
- `code/Modules/Savings/Resources/views/themes/adminlte/withdrawal/index.blade.php`

**Evidence in both files:**
- Title references `loan::general.repayment` (not savings deposit or withdrawal)
- DataTables AJAX URL: `journal_entry/get_journal_entries` (Accounting module endpoint)
- Column set is journal-entry debit/credit layout

Dashlite copies (`.../dashlite/deposit/index.blade.php`, `.../dashlite/withdrawal/index.blade.php`) are also identical to each other.

---

### 1.4 User module hosts Savings report views

**Severity:** High  
**Impact:** User module contains Savings report templates; changes to one copy will not propagate; module ownership is unclear.

**Verified identical file pairs:**

| User module | Savings module |
|-------------|----------------|
| `code/Modules/User/Resources/views/themes/adminlte/report/balance.blade.php` | `code/Modules/Savings/Resources/views/themes/adminlte/report/balance.blade.php` |
| `code/Modules/User/Resources/views/themes/adminlte/report/transaction.blade.php` | `code/Modules/Savings/Resources/views/themes/adminlte/report/transaction.blade.php` |
| `code/Modules/User/Resources/views/themes/adminlte/report/balance_pdf.blade.php` | `code/Modules/Savings/Resources/views/themes/adminlte/report/balance_pdf.blade.php` |

The User module's `ReportController` only implements a `performance` report. These Savings report views appear orphaned in the User module.

---

### 1.5 M-Pesa routes reference missing controller methods

**Severity:** Critical  
**Impact:** `POST mpesa/get_loan_intent` and `POST mpesa/get_access_token` will return 500 (method not found).

**Routes file:** `code/Modules/Mpesa/Routes/web.php`

```php
Route::post('get_loan_intent', 'MpesaController@get_loan_intent');
Route::post('get_access_token', 'MpesaController@get_access_token');
Route::any('capture_payment', 'MpesaController@capture_payment');
```

**Controller:** `code/Modules/Mpesa/Http/Controllers/MpesaController.php`

Only defines:
- `capture_payment()` — STK push initiation (also fetches OAuth token inline)
- `webhook()` — payment confirmation callback

`get_loan_intent` and `get_access_token` are registered but never implemented. Other gateway modules (Stripe, PayPal) do implement `get_loan_intent`.

---

## 2. Authentication and API Surface Overlap

Three separate authentication entry points serve overlapping purposes with inconsistent behavior.

### 2.1 Auth controllers

| Controller | Route prefix | Endpoints | Registration behavior |
|------------|-------------|-----------|----------------------|
| Web login | `User` module | Session-based via `LoginController` | Standard Laravel `AuthenticatesUsers` |
| User API auth | `api/v1` (User module) | `login`, `register`, `logout` | Creates User + Client + ClientUser; role `"client"` (lowercase); requires `branch_id`, `gender`; sets `email_verified_at` |
| Api module auth | `api/v1` (Api module) | `logind`, `registers`, `logoutd` | Creates User only; role `"Client"` (capital C); enables 2FA secret; assigns KYC tier; fires `UserRegistration` event |

**Files:**
- `code/Modules/User/Http/Controllers/Auth/LoginController.php`
- `code/Modules/User/Http/Controllers/Api/v1/AuthController.php`
- `code/Modules/Api/Http/Controllers/v1/AuthController.php`

### 2.2 Route inconsistencies

**User module routes** (`code/Modules/User/Routes/api.php`):
```
POST api/v1/login
POST api/v1/register
POST api/v1/logout  (auth:api middleware)
```

**Api module routes** (`code/Modules/Api/Routes/api.php`):
```
POST api/v1/logind      ← typo suffix
POST api/v1/registers   ← typo suffix
POST api/v1/logoutd     ← typo suffix
```

Both modules register under the same `v1` prefix. Depending on route load order, one set may shadow the other for overlapping paths — but the typo-suffixed routes (`logind`, `registers`) are distinct URLs, meaning API consumers must know which module's endpoints to call.

### 2.3 Behavioral differences in `login()`

| Behavior | User `AuthController` | Api `AuthController` |
|----------|----------------------|---------------------|
| Username fallback | `$request->username ?: $request->email` | `$request->username` only |
| Error response format | Plain string JSON | Plain string JSON (same) |

### 2.4 Behavioral differences in `register()`

| Field / behavior | User `AuthController` | Api `AuthController` |
|-----------------|----------------------|---------------------|
| Required fields | first_name, last_name, phone, branch_id, gender, email, password | first_name, last_name, phone, email, password |
| Role assigned | `"client"` | `"Client"` |
| Client record created | Yes (`Client` + `ClientUser`) | No |
| 2FA secret | Commented out | Generated via `google2fa` |
| KYC tier | Not assigned | Assigned from settings |
| Validation error format | `{ success: false, errors: ... }` HTTP 400 | `{ error: ... }` HTTP 422 |
| Success response | `{ data, message, success }` | `{ user }` |

### 2.5 Risk assessment

- **Role name casing** (`"client"` vs `"Client"`) may cause permission failures if Spatie roles are case-sensitive.
- **Dual registration flows** — portal/mobile apps using different endpoints will create users with different data completeness.
- **No single source of truth** for API authentication; future security fixes must be applied in two places.

---

## 3. Intentional vs Problematic Duplication

### 3.1 Intentional duplication (by design)

These patterns increase maintenance cost but are architecturally valid.

#### Admin / Portal / API parallel layers

| Feature | Admin controller | Portal controller | API controller | Lines (admin / API) |
|---------|-----------------|-------------------|----------------|---------------------|
| Loans | `Loan/Http/Controllers/LoanController.php` | `Portal/Http/Controllers/LoanController.php` | `Loan/Http/Controllers/Api/v1/LoanController.php` | 2,344 / 1,578 |
| Savings | `Savings/Http/Controllers/SavingsController.php` | `Portal/Http/Controllers/SavingsController.php` | `Savings/Http/Controllers/Api/v1/SavingsController.php` | 1,504 / 1,038 |
| Shares | `Share/Http/Controllers/ShareController.php` | `Portal/Http/Controllers/ShareController.php` | — | — |
| Client | `Client/Http/Controllers/ClientController.php` | `Portal/Http/Controllers/ClientController.php` | — | — |

**Key behavioral split:** Admin `store_repayment()` posts `PaymentDetail` + `LoanTransaction` directly. Portal `store_repayment()` delegates to payment gateway classes via `processPayment()`.

#### Parallel account product families

Loan, Savings, Share, and Wallet each implement the same lifecycle:

- Account entity + Transaction entity + LinkedCharge entities (account + product level)
- Product controller + Charge controller with identical CRUD method sets
- Shared payment posting via `Core/PaymentGateways/PaymentGateway.php` → `processPayment()`

#### Payment gateway modules (structural duplication)

Five standalone modules follow the same integration pattern:

| Module | Controller | Routes |
|--------|-----------|--------|
| Mpesa | `Mpesa/Http/Controllers/MpesaController.php` | `get_loan_intent`, `capture_payment`, `webhooks/mpesa` |
| Stripe | `Stripe/Http/Controllers/StripeController.php` | `get_loan_intent`, `webhooks/stripe` |
| PayPal | `Paypal/Http/Controllers/PaypalController.php` | `get_loan_intent`, `capture_payment`, `webhooks/paypal` |
| Paynow | `Paynow/Http/Controllers/PaynowController.php` | `webhook` (two paths) |
| Flutterwave | `Flutterwave/Http/Controllers/FlutterwaveController.php` | `capture_payment`, `webhooks/flutterwave` |

Each gateway class extends `PaymentGateway` and calls `processPayment()` on success.

#### Per-module report controllers (7 classes)

| Module | Controller | Reports |
|--------|-----------|---------|
| Report (hub) | `Report/Http/Controllers/ReportController.php` | Aggregates `ReportsLinks.php` from modules |
| Loan | `Loan/Http/Controllers/ReportController.php` | collection_sheet, repayment, expected_repayment, arrears, disbursement |
| Savings | `Savings/Http/Controllers/ReportController.php` | transaction, balance, account_statement, account |
| Accounting | `Accounting/Http/Controllers/ReportController.php` | trial_balance, income_statement, balance_sheet, ledger |
| User | `User/Http/Controllers/ReportController.php` | performance |
| Client | `Client/Http/Controllers/ReportController.php` | index, store |
| Sacco | `Sacco/Http/Controllers/ReportTemplateController.php` | SASRA template CRUD |

Loan reports are also mirrored in `Loan/Http/Controllers/Api/v1/ReportController.php`.

#### Dual-theme UI (AdminLTE + Dashlite)

- **~371** view paths exist in both `themes/adminlte/` and `themes/dashlite/`
- **147 pairs** are byte-identical (verified via `diff`)
- Intentional: supports two visual themes selectable per organisation

#### SACCO C2B vs Mpesa STK (different flows)

| Integration | Path | Purpose |
|------------|------|---------|
| Mpesa module | `Mpesa/Http/Controllers/MpesaController.php` | STK push for portal loan repayments |
| Sacco C2B | `Sacco/Http/Controllers/MpesaC2bController.php` + `SaccoMpesaC2bService` | Paybill C2B validation/confirmation for SACCO operations |

These are separate payment flows, not duplicates — but naming overlap can confuse developers.

---

### 3.2 Problematic duplication (bugs and drift risk)

#### Duplicate business logic without shared services

**Interest/amortization helpers** — near-identical functions in two module helper files:

| Function | `Loan/Helpers/general_helper.php` | `Savings/Helpers/general_helper.php` |
|----------|----------------------------------|-------------------------------------|
| `determine_period_interest_rate()` | Includes `$repayment_frequency` param | Omits `$repayment_frequency` |
| `determine_amortized_payment()` | Present | Present (same logic) |
| `compare_multi_dimensional_array()` | Present | Present (same logic) |

**Loan schedule logic** repeated inline in `LoanController` at:
- Disbursement (~line 1211)
- Reschedule (~line 472)
- `process_loan_calculator()` (~line 1980)

Same logic duplicated again in `Loan/Http/Controllers/Api/v1/LoanController.php`. No shared `LoanScheduleService` exists.

**Manual vs online repayment posting:**
- `LoanController::store_repayment()` — inline `PaymentDetail` + `LoanTransaction` creation
- `PaymentGateway::processPayment()` — nearly identical with `online_transaction` flag

#### SACCO compliance exposed on web and API (same services, dual endpoints)

| Web endpoint | API endpoint | Shared service |
|-------------|-------------|----------------|
| `ComplianceController::ratios()` | `ComplianceApiController::ratios()` | `PrudentialRatioService::calculateAll()` |
| `AppraisalController::calculate()` | `SaccoApiController::appraisal()` | `LoanAppraisalService` |
| `sacco/search/ajax` | `api/v1/sacco/search` | `GlobalSearchService` |

Acceptable for multi-channel access, but changes must be applied consistently.

#### Two audit log systems

| System | Controller | Storage |
|--------|-----------|---------|
| Spatie Activity Log | `ActivityLog/Http/Controllers/ActivityLogController.php` | `activity_log` table (Spatie) |
| SACCO Compliance Audit | `Sacco/Http/Controllers/ComplianceAuditLogController.php` | `ComplianceAuditLog` entity |

Different domains (general audit vs regulatory compliance) but overlapping "audit log" UX.

#### Two approval workflows

| Model | Purpose |
|-------|---------|
| `SaccoPendingApproval` | Maker-checker for financial transactions |
| `SaccoRegulatoryApproval` | Board/regulatory governance approvals |

Similar naming; different domains.

#### Maker-checker helper duplication

In `Sacco/Helpers/general_helper.php`:
- `sacco_requires_maker_checker($amount)`
- `sacco_requires_maker_checker_for($amount, $type)`

Both delegate to `MakerCheckerService::requiresApproval()`.

#### Repo/config artifacts

| Item | Locations | Notes |
|------|-----------|-------|
| Database dump | `ulm.sql` (root), `code/ulm.sql` | Identical 446 KB files |
| Environment | `.env` (root), `code/.env` | Docker vs app config |
| Module name mismatch | `code/modules_statuses.json` | Both `"Shares": true` and `"Share": true`; `"Nice": true` but no `Modules/Nice/` folder |
| Paynow double webhook | `Paynow/Routes/web.php` | `paynow/webhook/paynow` and `webhooks/paynow` → same handler |
| Portal dashboard route | `Portal/Routes/web.php` | `portal/` and `portal/dashboard` both → `PortalController@index` |
| Wallet permissions | `Wallet/Http/Controllers/WalletController.php` | Uses `approve_savings`, `activate_savings` copied from Savings module |

---

### 3.3 Risk matrix

```
                    High bug risk
                         │
    Flutterwave webhook ●│● Linked-charge views
    Mpesa missing methods│● Deposit = Withdrawal
                         │● Dual API auth
    Interest helpers    ●│
                         │
Low maintenance ─────────┼───────── High maintenance
                         │
    Account families    ●│● Web vs API controllers
    Payment gateways    ●│● Dual-theme views (147 identical)
                         │
                    Low bug risk
```

---

## 4. Frontend Pattern Duplication

### 4.1 Dual-theme view pairs

| Metric | Count | Verification |
|--------|-------|-------------|
| AdminLTE views with Dashlite counterpart | ~371 | `find Modules -path '*/themes/adminlte/*'` |
| Byte-identical theme pairs | **147** | `diff` comparison script |
| Near-identical pairs (minor markup differences) | ~224 | Remainder |

**Examples of identical pairs:**
- `Client/.../client/index.blade.php` (adminlte ↔ dashlite)
- `Loan/.../loan/index.blade.php`
- `Savings/.../deposit/index.blade.php`
- `Income/.../income/index.blade.php`
- `Wallet/.../wallet/index.blade.php`
- `Accounting/.../journal_entry/index.blade.php`

**Cross-module identical files (bugs, not theme duplication):**

| File A | File B |
|--------|--------|
| `Loan/.../loan_linked_charge/index.blade.php` | `Savings/.../savings_linked_charge/index.blade.php` |
| `Loan/.../loan_linked_charge/index.blade.php` | `Share/.../share_linked_charge/index.blade.php` |
| `Savings/.../deposit/index.blade.php` | `Savings/.../withdrawal/index.blade.php` |
| `User/.../report/balance.blade.php` | `Savings/.../report/balance.blade.php` |
| `Loan/.../loan_repayment/index.blade.php` | `Portal/.../loan/loan_repayment/index.blade.php` |

---

### 4.2 Vue bulk-select pattern (`selectAllRecords`)

**86 Blade files** contain inline Vue logic for `selectAllRecords` bulk row selection.

Representative files:
- `Client/.../client/index.blade.php`
- `Loan/.../loan/index.blade.php`
- `Savings/.../savings/index.blade.php`
- `Share/.../share/index.blade.php`
- `User/.../user/index.blade.php`
- `Income/.../income/index.blade.php`
- `Expense/.../expense/index.blade.php`
- `Accounting/.../journal_entry/index.blade.php`
- `Communication/.../campaign/index.blade.php`
- Plus dashlite mirrors for most of the above

**Pattern:** Each index page embeds its own Vue instance with `selectAllRecords`, `selectedRecords`, and bulk-delete form submission — typically 30–50 lines of duplicated JavaScript per file.

---

### 4.3 DataTables server-side initialization

**57 Blade files** contain `$('#data-table').DataTable({ ... })` initialization blocks.

Representative files:
- `Portal/.../loan/index.blade.php`, `savings/index.blade.php`, `share/index.blade.php`
- `Loan/.../loan_repayment/index.blade.php`, `credit_check/index.blade.php`
- `Savings/.../deposit/index.blade.php`, `withdrawal/index.blade.php`
- `Client/.../client/show.blade.php` (4 DataTable instances)
- `Communication/Resources/views/campaign/index.blade.php`

**Pattern:** Same `processing: true, serverSide: true` block with language translations; only `ajax` URL and `columns` array differ per page.

---

### 4.4 Product form Vue patterns (`available_charges`)

**12 files** duplicate the `available_charges` computed property and chart-of-account `v-select` pattern:

- `Loan/.../loan_product/create.blade.php` (~1,002 lines)
- `Loan/.../loan_product/edit.blade.php`
- `Savings/.../savings_product/create.blade.php` (~765 lines)
- `Savings/.../savings_product/edit.blade.php`
- `Share/.../share_product/create.blade.php` (~610 lines)
- `Share/.../share_product/edit.blade.php`
- Plus dashlite equivalents for all six

---

### 4.5 Report filter/export UI

Same filter form (branch `select2`, `flat-pickr` date range, PDF/Excel export dropdown) repeated across:

**Loan reports:**
- `Loan/.../report/disbursement.blade.php`
- `Loan/.../report/arrears.blade.php`
- `Loan/.../report/collection_sheet.blade.php`
- `Loan/.../report/repayment.blade.php`
- `Loan/.../report/expected_repayment.blade.php`

**Savings reports:**
- `Savings/.../report/balance.blade.php`
- `Savings/.../report/transaction.blade.php`
- `Savings/.../report/account.blade.php`
- `Savings/.../report/account_statement.blade.php`

---

### 4.6 Member financial snapshot (three implementations)

The same member balance/KPI data is rendered independently in:

| Location | File | Approach |
|----------|------|----------|
| Portal dashboard | `Portal/.../index.blade.php` | Blade + Highcharts; `$member360`, `$loanStats`, `$savingsStats` |
| Admin client 360 | `Client/.../client/show.blade.php` | Blade info-boxes; savings/share/loan/wallet/arrears |
| SACCO teller desk | `Sacco/.../teller/index.blade.php` | Inline `fetch()` to `sacco/teller/member-lookup`; builds balances in JS |

No shared Blade component or partial exists for the member financial snapshot.

---

### 4.7 Dashboard widget pattern duplication

Info-box / small-box KPI tiles repeated across module widgets:

| Widget | Path |
|--------|------|
| Dashboard summary | `Dashboard/.../widgets/dashboard_summary.blade.php` |
| Client statistics | `Client/.../widgets/client_statistics.blade.php` |
| Savings statistics | `Savings/.../widgets/savings_statistics.blade.php` |
| Loan statistics | `Loan/.../widgets/loan_statistics.blade.php` |
| Loan pipeline | `Loan/.../widgets/loan_pipeline.blade.php` |
| Loan portfolio at risk | `Loan/.../widgets/loan_portfolio_at_risk.blade.php` |
| Loan arrears aging | `Loan/.../widgets/loan_arrears_aging.blade.php` |
| SACCO compliance prudential | `Sacco/.../widgets/compliance_prudential.blade.php` |

SACCO compliance ratio cards also appear in full form at `Sacco/.../compliance/index.blade.php` and `Sacco/.../regulatory/index.blade.php` — three levels of the same data (widget, compliance page, regulatory page).

---

### 4.8 Settings AJAX pattern

Identical `$.ajax` + SweetAlert version-check logic in:
- `Setting/.../themes/adminlte/setting/update.blade.php`
- `Setting/.../themes/dashlite/setting/update.blade.php`

---

## 5. Findings Summary

| Category | Count | Severity |
|----------|-------|----------|
| Confirmed copy-paste bugs | 5 | Critical |
| Overlapping auth/API flows | 3 controllers, 2 route sets | High |
| Duplicated helper/service logic | 2 helper files + inline controller logic | High |
| SACCO vs core overlaps | 6 areas | Medium |
| Frontend pattern duplication | 86 Vue bulk-select, 57 DataTables, 147 identical theme pairs | Medium (maintenance) |
| Intentional parallel layers | Admin/Portal/API + 4 account families + 5 gateways | Low risk, high maintenance |
| Repo/config artifacts | 6 items | Low |

---

## 6. Recommended Remediation Priority

When development resumes, address findings in this order:

1. **Fix confirmed bugs** — Flutterwave webhook, linked-charge views (Savings/Share), withdrawal view, M-Pesa missing methods, remove or redirect orphaned User report views
2. **Consolidate API auth** — single `AuthController`, one route set; deprecate `logind`/`registers`/`logoutd`
3. **Extract shared helpers** — move `determine_period_interest_rate`, `determine_amortized_payment` to `Core/Helpers/` or `FinancialCalculationService`
4. **Introduce Blade components** — report filters, DataTables init, Vue bulk-select, member360 snapshot partial
5. **Evaluate theme strategy** — single theme with CSS variables vs maintaining 147 identical pairs
6. **Document intentional splits** — Admin/Portal/API layers and SACCO C2B vs Mpesa STK in architecture notes

---

*This document was produced as a read-only audit. No application code was modified.*
