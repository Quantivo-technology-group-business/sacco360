# Ultimate Loan Manager (ULM)

**Version:** 3.0  
**Framework:** Laravel (PHP)  
**Theme:** AdminLTE  
**Author:** John Wati, Savoir Microsystems

> A comprehensive, web-based loan and microfinance management system for lending businesses. Manage borrowers, loans, savings, repayments, accounting, staff, and more — from a single platform.

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation](#installation)
   - [Wizard Install (Recommended)](#wizard-install-recommended)
   - [Manual Install](#manual-install)
   - [Docker Install](#docker-install)
   - [Upgrading from v2](#upgrading-from-v2)
3. [First Login & Initial Setup](#first-login--initial-setup)
4. [Step-by-Step User Guide](#step-by-step-user-guide)
   - [Step 1 — Configure Your Organisation](#step-1--configure-your-organisation)
   - [Step 2 — Set Up Branches](#step-2--set-up-branches)
   - [Step 3 — Create User Roles & Staff](#step-3--create-user-roles--staff)
   - [Step 4 — Create Loan Products](#step-4--create-loan-products)
   - [Step 5 — Create Savings Products](#step-5--create-savings-products)
   - [Step 6 — Add Borrowers (Clients)](#step-6--add-borrowers-clients)
   - [Step 7 — Create & Process a Loan](#step-7--create--process-a-loan)
   - [Step 8 — Record Repayments](#step-8--record-repayments)
   - [Step 9 — Manage Savings Accounts](#step-9--manage-savings-accounts)
   - [Step 10 — Accounting & Journal Entries](#step-10--accounting--journal-entries)
   - [Step 11 — Run Reports](#step-11--run-reports)
   - [Step 12 — Communication & Notifications](#step-12--communication--notifications)
   - [Step 13 — Payroll Management](#step-13--payroll-management)
   - [Step 14 — Expenses & Other Income](#step-14--expenses--other-income)
   - [Step 15 — Custom Fields](#step-15--custom-fields)
5. [Module Reference](#module-reference)
6. [Payment Gateway Integration](#payment-gateway-integration)
7. [Cron Jobs & Automation](#cron-jobs--automation)
8. [Client Self-Service Portal](#client-self-service-portal)
9. [API](#api)
10. [Localisation / Language](#localisation--language)
11. [Environment Variables](#environment-variables)
12. [Troubleshooting](#troubleshooting)
13. [Credits](#credits)

---

## System Requirements

| Requirement | Minimum |
|---|---|
| PHP | 7.3+ (8.x recommended) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Extensions | PDO, CURL, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON |
| Web Server | Apache (mod_rewrite enabled) or Nginx |
| `.env` file | Must be writable by the web server |

> **Tip:** Set your PHP `max_execution_time` to at least `300` seconds before installing, as the database setup can take time. Ask your hosting provider if unsure.

---

## Installation

### Wizard Install (Recommended)

1. **Unzip** the downloaded package. You will see two folders: `code/` and `documentation/`.
2. **Upload** all files inside `code/` to your web server's root or a subdirectory.
3. **Point your domain** (or virtual host) document root to the `public/` folder inside the uploaded code.
4. Ensure the `.env` file is **writable** by the web server process.
5. Open your browser and navigate to `https://yourdomain.com` (or `https://yourdomain.com/install`).
6. The **Installation Wizard** will launch. Follow the on-screen steps:
   - Enter your **purchase code** to verify the licence.
   - Provide your **database credentials** (host, port, database name, username, password).
   - Set your **admin email and password**.
   - The wizard will create all database tables and seed initial data automatically.
7. Once complete, you will be redirected to the login page.

**Default login (if not changed during install):**
```
Email:    admin@webstudio.co.zw
Password: admin123
```
> ⚠️ **Change the default password immediately after first login.**

---

### Manual Install

Use this method if the wizard fails (e.g., shared hosting with restrictions).

1. Create a MySQL database and note the credentials.
2. Import the `ulm.sql` file into the database:
   ```bash
   mysql -u youruser -p yourdatabase < ulm.sql
   ```
3. Copy `.env.example` to `.env` and update it with your database credentials and app URL:
   ```env
   APP_URL=https://yourdomain.com
   DB_HOST=localhost
   DB_DATABASE=your_db
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   APP_INSTALLED=true
   ```
4. Generate an application key:
   ```bash
   php artisan key:generate
   ```
5. Set storage permissions:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```
6. Run migrations (if needed):
   ```bash
   php artisan migrate --seed
   ```

---

### Docker Install

A `docker-compose.yml` file is included for containerised deployments.

1. Copy `.env.docker.example` (in the project root) to `.env` and adjust values as needed.
2. From the project root, run:
   ```bash
   bash install.sh
   # or
   docker-compose up -d
   ```
3. Access the app at `http://localhost` (or the port configured in `docker-compose.yml`).

Other Docker helper scripts:

| Script | Purpose |
|---|---|
| `run.sh` | Start containers |
| `stop.sh` | Stop containers |
| `logs.sh` | Tail container logs |
| `fix-permissions.sh` | Fix file permission issues |
| `deploy.sh` | Deploy / rebuild containers |

---

### Upgrading from v2

Version 3.0 is **not** backwards-compatible with v2. To upgrade:

1. Install v3 as a fresh application on the same or a new server.
2. Log in as admin and go to **Settings → Upgrade**.
3. Click **Upgrade from v2** and follow the import wizard to migrate your v2 data.

---

## First Login & Initial Setup

After logging in for the first time, work through these settings before adding any borrowers or loans:

1. **Settings → Organisation** — Enter your company name, address, logo, and currency.
2. **Settings → General** — Set date format, financial year start, default interest method.
3. **Settings → Email** — Configure SMTP so the system can send emails.
4. **Settings → SMS** — Connect an SMS gateway (optional but recommended for borrower reminders).
5. **Settings → System** — Tune session lifetime, queue driver, and other system parameters.

---

## Step-by-Step User Guide

### Step 1 — Configure Your Organisation

**Path:** `Settings → Organisation`

- Upload your company **logo** (displayed on receipts and PDF reports).
- Set your **default currency** and currency symbol.
- Enter your **address** and **contact details**.
- Save. These details appear on all printed documents.

---

### Step 2 — Set Up Branches

**Path:** `Branch`

If your lending business has multiple branches or offices, create them here before adding staff or clients.

1. Click **Create Branch**.
2. Enter the branch name, code, address, and phone.
3. Assign a **Branch Manager** from your existing users (or create users first — see Step 3).
4. Save. Each borrower, loan, and saving can be associated with a branch.

---

### Step 3 — Create User Roles & Staff

**Path:** `User`

#### 3a — Create Roles

1. Go to **User → Roles**.
2. Click **Create Role** and give it a name (e.g., "Loan Officer", "Accountant", "Cashier").
3. Assign **permissions** — tick every page/action this role is allowed to access.
4. Save the role.

#### 3b — Create Staff Users

1. Go to **User → Users → Create**.
2. Fill in name, email, phone, and assign a role.
3. Set a temporary password. The user can change it after first login.
4. Assign the user to a branch (if applicable).
5. Save. The user receives a welcome email with login details (if email is configured).

> **Audit Trail:** All staff actions are logged. View them at **User → Activity Log**.

---

### Step 4 — Create Loan Products

**Path:** `Settings → Loan Products` (or via the Loan module settings)

A loan product is a template that defines the rules for a category of loans (e.g., "Personal Loan 12 months", "Business Loan").

1. Click **Create Loan Product**.
2. Fill in:
   - **Name** — product label shown to staff and borrowers.
   - **Minimum / Maximum amount** — the allowable loan range.
   - **Interest rate** — percentage, and whether it is flat, declining-balance, or compound.
   - **Loan term** — number of repayment periods (days, weeks, months, years).
   - **Repayment frequency** — weekly, bi-weekly, monthly, etc.
   - **Grace period** — number of days before late penalties start.
   - **Penalty rate** — percentage charged on overdue amounts.
   - **Charges** — fixed or percentage fees applied at disbursement or on schedule.
   - **GL Codes** — link to your Chart of Accounts for automatic journal entries.
3. Save. The product is now selectable when creating a loan.

---

### Step 5 — Create Savings Products

**Path:** `Settings → Savings Products`

1. Click **Create Savings Product**.
2. Enter:
   - **Name** (e.g., "Fixed Deposit 6M", "Regular Savings").
   - **Interest rate** and calculation method.
   - **Minimum balance** and **minimum deposit** amounts.
   - **Withdrawal restrictions** (if any).
   - **GL Codes** for automatic accounting entries.
3. Save.

---

### Step 6 — Add Borrowers (Clients)

**Path:** `Client → Create`

1. Fill in **personal information**: first name, last name, date of birth, gender, phone, email, address.
2. Upload a **profile photo** (optional but recommended).
3. Add **identification documents** — go to the client's profile → *Identification* tab → Add ID. Supported types: National ID, Passport, Driver's Licence, etc.
4. Add **Next of Kin** — go to the *Next of Kin* tab → Add. Enter name, relationship, phone, and address.
5. Upload supporting **files/documents** from the *Files* tab.
6. Add any **custom field** values relevant to your business.
7. Save. The client profile is now created with a unique client number.

> **Member 360 View:** Click *Member 360* on any client to see all their loans, savings, and transactions on a single screen.

---

### Step 7 — Create & Process a Loan

This is the core workflow. A loan moves through several stages: **Pending → Approved → Disbursed → Active → Closed**.

#### 7a — Create the Loan Application

**Path:** `Loan → Create`

1. Select the **borrower** from the dropdown (type to search).
2. Select the **loan product**.
3. Enter the **loan amount**, **term**, and **disbursement date**.
4. Set the **loan officer** responsible for this loan.
5. Add any **collateral** (Loan → Collateral tab) — describe the asset and its estimated value.
6. Add **guarantors** if required (Loan → Guarantors tab) — select existing clients or enter details manually.
7. Attach supporting **files** (application form, ID copies, etc.) from the Files tab.
8. Save. The loan status is **Pending**.

#### 7b — Approve the Loan

**Path:** Loan detail page → *Approve*

1. Open the loan record.
2. Review repayment schedule (auto-generated based on product settings).
3. Click **Approve Loan**. Enter any approval notes.
4. The loan status changes to **Approved**.

> To reverse: click **Undo Approval** (only available before disbursement).

#### 7c — Disburse the Loan

**Path:** Loan detail page → *Disburse*

1. Click **Disburse Loan**.
2. Select the **disbursement method** (cash, bank transfer, mobile money).
3. Enter the disbursement date and any reference number.
4. Confirm. The loan status changes to **Active** and the repayment schedule is locked in.
5. Journal entries are created automatically if GL codes are configured.

> **Undo Disbursement** is available if no repayments have been recorded yet.

#### 7d — Reject or Withdraw a Loan

- **Reject** — use when the committee decides not to approve. Enter a reason. Undo is available.
- **Withdraw** — use when the borrower cancels after approval. Undo is available.

#### 7e — Other Loan Actions

| Action | When to use |
|---|---|
| **Waive Interest** | Write off outstanding interest charges for a client in difficulty |
| **Write Off Loan** | Move a non-performing loan to written-off status |
| **Reschedule Loan** | Extend or restructure the repayment schedule |
| **Change Loan Officer** | Re-assign to a different staff member |

---

### Step 8 — Record Repayments

**Path:** `Loan → [open loan] → Repayments → Add Repayment`

1. Open the active loan.
2. Click **Add Repayment**.
3. Enter:
   - **Date** of payment.
   - **Amount** received.
   - **Payment method** (cash, bank, mobile money, etc.).
   - Optional reference / receipt number.
4. Save. The system allocates the payment to penalties → interest → principal (in that order, depending on settings).
5. A **receipt** can be printed or emailed to the borrower from the repayment record.

> **Bulk Repayment / Collection Sheet:** Go to `Report → Collection Sheet` to see all repayments due on a given day. Use it as a field collection tool.

---

### Step 9 — Manage Savings Accounts

A savings account follows a similar lifecycle to a loan: **Pending → Approved → Active → Closed**.

#### 9a — Open a Savings Account

**Path:** `Savings → Create`

1. Select the **client**.
2. Select the **savings product**.
3. Set the **opening date** and any initial deposit amount.
4. Assign a **savings officer**.
5. Save (status: Pending).

#### 9b — Approve & Activate

1. Open the savings record → click **Approve**.
2. Then click **Activate** to move it to Active status so transactions can be recorded.

#### 9c — Record Deposits & Withdrawals

**Path:** Savings detail → *Transactions → Add Transaction*

1. Select transaction type: **Deposit** or **Withdrawal**.
2. Enter amount, date, and payment method.
3. Save. A transaction receipt is available to print or email.

#### 9d — Close a Savings Account

1. Open the savings record → click **Close**.
2. The system calculates interest owed (if any) and shows the final balance.
3. Confirm the closure. Remaining balance should be withdrawn before closing.

---

### Step 10 — Accounting & Journal Entries

**Path:** `Accounting`

#### Chart of Accounts

1. Go to **Accounting → Chart of Accounts**.
2. Create account groups (Assets, Liabilities, Income, Expenses, Equity) and sub-accounts with GL codes.
3. Link these GL accounts to loan products and savings products so transactions post automatically.

#### Manual Journal Entries

**Path:** `Accounting → Journal Entries → Create`

1. Enter the **date** and a **description**.
2. Add debit and credit lines (accounts must balance to zero).
3. Save. Entries can be reversed if entered in error.

#### Financial Reports

| Report | Path |
|---|---|
| Trial Balance | `Accounting → Trial Balance` |
| Balance Sheet | `Accounting → Balance Sheet` |
| Income Statement | `Accounting → Income Statement` |
| Cash Flow | `Accounting → Cash Flow` |
| General Ledger | `Accounting → Ledger` |

---

### Step 11 — Run Reports

**Path:** `Report`

The Reports module provides a wide range of operational and regulatory reports. All reports can be exported to PDF or Excel.

| Report Category | Examples |
|---|---|
| **Loan Reports** | Loan Portfolio, Arrears Report, Loan Maturity, Expected vs Actual Payments |
| **Repayment Reports** | Collection Sheet, Repayment History by Loan Officer |
| **Savings Reports** | Savings Portfolio, Transaction History |
| **Client Reports** | Borrower Summary, New Clients by Period |
| **Accounting Reports** | Trial Balance, Balance Sheet, P&L |
| **Provisioning** | Loan Loss Provisioning by PAR (Portfolio at Risk) bucket |

**How to generate a report:**
1. Select the report type.
2. Set the **date range** and any filters (branch, loan officer, product, status).
3. Click **Generate**.
4. Use the export buttons to download as PDF or Excel.

---

### Step 12 — Communication & Notifications

**Path:** `Communication`

#### SMS Gateway

1. Go to **Communication → SMS Gateway → Create**.
2. Select your gateway provider and enter the API credentials.
3. Save and test the connection.

#### Email Configuration

Configure SMTP under **Settings → Email**.

#### Campaigns

Send bulk SMS or email messages to a filtered group of clients:
1. Go to **Communication → Campaigns → Create**.
2. Choose the channel (SMS or Email).
3. Define the recipient filter (all active borrowers, overdue borrowers, etc.).
4. Write your message.
5. Schedule or send immediately.

#### Automated Notifications (via Cron)

Set up cron jobs (see [Cron Jobs](#cron-jobs--automation)) to automatically:
- Send repayment reminders X days before due date.
- Send overdue notices after a payment is missed.
- Email scheduled reports to managers.

---

### Step 13 — Payroll Management

**Path:** `Payroll`

#### 13a — Create Employees

1. Go to **Payroll → Employees → Create**.
2. Enter employee details: name, position, bank account, and basic salary.
3. Add allowances and deductions as applicable.
4. Save.

#### 13b — Run Payroll

1. Go to **Payroll → Run Payroll → Create**.
2. Select the **pay period** (month and year).
3. The system calculates gross pay, deductions, and net pay for each employee.
4. Review and confirm.
5. Print or export individual payslips (PDF) from the payroll run detail page.

---

### Step 14 — Expenses & Other Income

#### Expenses

**Path:** `Expense`

1. Create **Expense Types** (e.g., Office Rent, Utilities, Travel) under `Expense → Types`.
2. Record individual expenses under `Expense → Create`: enter date, type, amount, and description.
3. Expenses are automatically posted to the linked GL account.

#### Other Income

**Path:** `Income`

Record non-loan income (e.g., application fees, membership fees) via the Income module.

---

### Step 15 — Custom Fields

**Path:** `Settings → Custom Fields`

Add your own form fields to capture business-specific information on:
- Borrower profiles
- Loan applications
- Expenses
- Other Income
- Collateral

1. Click **Create Custom Field**.
2. Select the **module** it belongs to.
3. Choose the **field type** (text, number, date, dropdown, checkbox).
4. Enter field label and any options (for dropdowns).
5. Mark as **required** if the field must be filled before saving.
6. Save. The field appears on the relevant forms immediately.

---

## Module Reference

| Module | Description |
|---|---|
| **Client** | Borrower profiles, identification, next of kin, files, Member 360 view |
| **Loan** | Loan applications, approval workflow, disbursement, repayments, collateral, guarantors, notes |
| **Savings** | Savings accounts, deposits, withdrawals, interest, statements |
| **Accounting** | Chart of accounts, journal entries, financial statements |
| **Report** | Operational, financial, and compliance reports |
| **User** | Staff management, roles & permissions, audit trail |
| **Branch** | Multi-branch setup and assignment |
| **Payroll** | Employee management, payroll runs, payslips |
| **Expense** | Expense categories and records |
| **Income** | Other income records |
| **Communication** | SMS gateway, email, bulk campaigns |
| **CustomField** | User-defined form fields |
| **Asset** | Fixed asset register |
| **Wallet** | Client wallet / e-wallet management |
| **Sacco** | SACCO-specific features (share capital, member contributions) |
| **Share** | Share management for SACCOs and co-operatives |
| **Portal** | Client self-service portal |
| **ActivityLog** | System-wide audit trail |
| **Dashboard** | KPI widgets and summary metrics |
| **Setting** | All system configuration |
| **Installer** | Installation wizard |
| **Upgrade** | Migration tool from v2 |
| **Api** | REST API endpoints |
| **Mpesa** | M-Pesa payment integration |
| **Flutterwave** | Flutterwave payment integration |
| **Stripe** | Stripe payment integration |
| **Paypal** | PayPal payment integration |
| **Paynow** | Paynow (ZW) payment integration |

---

## Payment Gateway Integration

ULM supports several payment gateways that allow borrowers to make repayments online.

### M-Pesa

1. Go to **Settings → Mpesa**.
2. Enter your **Consumer Key**, **Consumer Secret**, **Passkey**, **Shortcode**, and **Callback URL**.
3. Select the environment (Sandbox / Production).
4. Save and test.

### Stripe / PayPal / Flutterwave / Paynow

Each gateway has its own settings page under the relevant module in Settings. Enter the API keys provided by the payment provider, set the environment, and save.

Once configured, clients can pay via the **Client Portal** (see below) using their preferred method.

---

## Cron Jobs & Automation

Add this single cron entry to your server to enable all scheduled tasks:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Configured jobs include:
- **Repayment reminders** — SMS/email sent N days before due date (configurable in Settings).
- **Overdue alerts** — notifications triggered after a missed payment.
- **Penalty application** — automatic late fees posted on overdue loans.
- **Scheduled reports** — PDF reports emailed to configured recipients on a schedule.
- **Interest posting** — savings interest credited on schedule.

Set the notification timing and recipients under **Settings → Cron / Notifications**.

---

## Client Self-Service Portal

**Path:** `Portal` (accessed by borrowers at `https://yourdomain.com/portal`)

The portal lets clients:
- View their loan balance and repayment schedule.
- Download loan statements and receipts.
- Make repayments via connected payment gateways.
- View savings account balance and transaction history.

Enable the portal under **Settings → System → Enable Client Portal**. Each client needs a portal login — create one from the client's profile page (`Client → [client] → Create User`).

---

## API

The REST API allows third-party systems to integrate with ULM.

**Base URL:** `https://yourdomain.com/api/`

Authentication uses API tokens. Generate a token in **User → Profile → API Token**.

API documentation is available at `https://yourdomain.com/api/documentation` (if enabled).

---

## Localisation / Language

To add or customise a language:

1. Navigate to `resources/lang/` in the codebase.
2. Copy the `en/` folder and rename it to your language code (e.g., `fr/`, `sw/`, `ar/`).
3. Translate the string values in each `.php` file inside the new folder.
4. Under **Settings → General**, change the **System Language** to your new locale.
5. Save. The UI will switch to the selected language.

---

## Environment Variables

Key variables in the `.env` file:

| Variable | Purpose |
|---|---|
| `APP_URL` | The full URL of your installation |
| `APP_INSTALLED` | Set to `true` after installation |
| `DB_*` | Database connection credentials |
| `MAIL_*` | SMTP email settings |
| `QUEUE_CONNECTION` | Set to `database` for background jobs |
| `SESSION_LIFETIME` | Session timeout in minutes (default 1200) |
| `TELESCOPE_ENABLED` | Enable Laravel Telescope debugger (set `false` in production) |

After any change to `.env`, clear the config cache:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Troubleshooting

**Blank page / 500 error after install**
- Check `storage/logs/laravel.log` for the error message.
- Ensure `storage/` and `bootstrap/cache/` are writable: `chmod -R 775 storage bootstrap/cache`
- Run `php artisan config:clear && php artisan cache:clear`

**Installer loops or doesn't complete**
- Increase PHP `max_execution_time` to 300 seconds.
- Try the [Manual Install](#manual-install) method instead.

**Emails not sending**
- Verify SMTP credentials in **Settings → Email**.
- Ensure the queue worker is running: `php artisan queue:work`

**Scheduled tasks not running**
- Confirm the cron entry is saved: `crontab -l`
- Test manually: `php artisan schedule:run`

**Page permissions errors**
- Check that the user's role has the correct permissions assigned in **User → Roles**.

**Upgrading from v2 fails**
- Ensure both v2 and v3 database credentials are correctly set in `.env` (`DB_*` for v3, `OLD_DB_*` for v2).

---

## Credits

Built with:
- [Laravel](https://laravel.com) — PHP framework
- [AdminLTE](https://adminlte.io) — Admin dashboard theme
- [nWidart/laravel-modules](https://nwidart.com/laravel-modules) — Modular architecture

Support & contact: John Wati — [john.wati.ke@gmail.com](mailto:john.wati.ke@gmail.com)  
Organisation: [Savoir Microsystems](https://savoirmicrosystems.com)
