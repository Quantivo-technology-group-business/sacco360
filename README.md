# Sacco360

**Sacco360** is a full-featured financial management and digital banking ecosystem tailored for Savings and Credit Cooperative Organizations (SACCOs), Microfinance Institutions (MFIs), and Chamas (Investment & Rotating Savings Groups).

The platform pairs a core management web platform and API with a USSD mobile banking gateway.

---

## 📁 Repository Structure

```
sacco360/
├── sacco-core/             # Core SACCO Management Platform & Web Portal (Laravel + MySQL)
│   ├── code/               # Laravel application backend & APIs
│   │   ├── Modules/        # Chama, Loan, Savings, Shares, Accounting, M-Pesa, etc.
│   │   ├── app/            # Controllers, Models, Middleware
│   │   └── routes/         # Web, API, and USSD integration routes
│   ├── documentation/      # System guides and documentation
│   ├── deploy.sh           # Automated Docker deployment script
│   ├── docker-compose.yml  # Containerized stack (App, Queue Worker, Scheduler)
│   ├── ulm.sql             # Base database schema & initial data
│   └── fix-permissions.sh  # Storage & bootstrap permission helper
│
└── ussd-service/           # Mobile USSD Gateway (TypeScript / Node.js)
    ├── src/                # USSD session handling, menus, state machine
    ├── Dockerfile          # Containerized USSD runtime
    ├── docker-compose.yml  # USSD service + Redis session store
    └── package.json        # Node.js dependencies
```

---

## 🌟 Key Features

### 1. Sacco Core Platform (`sacco-core/`)
- **Chama & Merry-Go-Round**: Group creation, rotational payout schedules, contribution tracking, and member portal.
- **Loan Management**: Multi-tier loan products, amortization schedules, approval workflows, penalty & interest calculation.
- **Savings & Shares Accounts**: Flexible deposits, fixed terms, share capital, dividend distributions.
- **Double-Entry Accounting**: Automated journal entries, chart of accounts, trial balance, and profit & loss reports.
- **Payment Integrations**: Built-in M-Pesa (STK Push, C2B, B2C), Flutterwave, Stripe, PayPal, and Paynow.
- **Member Portal**: Self-service dashboard for members to check balances, apply for loans, and view statements.

### 2. USSD Banking Gateway (`ussd-service/`)
- Integrated with **Africa's Talking** USSD API for accessibility on any mobile device (smartphones and feature phones).
- **Self-Service Banking**:
  - Balance Inquiry (FOSA, BOSA, Shares, Loans)
  - Mini Statements (Last 5 transactions)
  - Transfers (FOSA to Loan, BOSA to FOSA) secured by PIN
  - Instant Loan Applications
  - Utility Bill Payments (KPLC, Nairobi Water, DSTV, GOtv, Airtime)
  - M-Pesa STK Push for loan repayments

---

## 🚀 Quick Start

### Starting Sacco Core
```bash
cd sacco-core

# Option A: Dockerized Deployment (Recommended)
chmod +x deploy.sh
./deploy.sh

# Option B: Local PHP / Artisan
cd code
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Starting USSD Service
```bash
cd ussd-service

# Configure environment
cp .env.example .env
# Set Africa's Talking credentials, database credentials, and shared USSD_API_KEY

# Run development server
npm install
npm run dev

# Or run with Docker
docker compose up -d
```

---

## 🏢 Organization
Maintained by [Quantivo Technology Group Business](https://github.com/Quantivo-technology-group-business).
