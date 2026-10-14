# ULM USSD Service

Standalone Node.js/TypeScript USSD application for **Ultimate Loan Manager** SACCO, integrated with [Africa's Talking](https://africastalking.com).

## Features

- Balance inquiry (FOSA, BOSA, shares, loans)
- Mini statement (last 5 FOSA transactions)
- Transfers (FOSA→Loan, BOSA→FOSA) with PIN auth
- Loan application
- **Pay utility bills from SACCO FOSA account** (KPLC, Nairobi Water, DSTV, GOtv, Airtime)
- M-Pesa STK push for loan repayment
- SMS transaction alerts via Africa's Talking

## Architecture

This project runs **separately** from the ULM Laravel app:

```
ussd-service/              ← Node.js USSD + Redis
sacco-core/                ← SACCO Core Laravel App (write APIs + MySQL)
```

- **Reads:** Direct MySQL connection to SACCO database
- **Writes:** HTTP calls to `POST /api/v1/sacco/ussd/actions/*` on SACCO Core

## Prerequisites

- Node.js 20+
- Docker (optional)
- Running SACCO Core Laravel app with migrations applied
- Africa's Talking sandbox/production account
- Shared `USSD_API_KEY` in both `.env` files

## Setup

### 1. SACCO Core (one-time)

```bash
cd ../sacco-core
# Add to .env:
# USSD_API_KEY=your-secure-random-key

docker compose exec app php artisan migrate
```

### 2. USSD service

```bash
cp .env.example .env
# Fill: AT_*, DB_*, LARAVEL_BASE_URL, USSD_API_KEY, APP_KEY (from ULM code/.env)

npm install
npm run dev          # local development
# OR
docker compose up -d # production-like
```

### 3. Africa's Talking

1. Create a USSD channel in AT dashboard
2. Set callback URL: `https://your-domain/ussd/callback`
3. For local dev: `ngrok http 3001`

## Environment Variables

| Variable | Description |
|----------|-------------|
| `AT_USERNAME` | Africa's Talking username |
| `AT_API_KEY` | Africa's Talking API key |
| `AT_SMS_SHORTCODE` | SMS sender ID |
| `DB_*` | ULM MySQL credentials |
| `LARAVEL_BASE_URL` | ULM app URL (e.g. `http://host.docker.internal:8080`) |
| `USSD_API_KEY` | Must match ULM `.env` |
| `APP_KEY` | Laravel APP_KEY for PIN validation |
| `REDIS_URL` | Session store |

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Health check |
| POST/GET | `/ussd/callback` | Africa's Talking USSD webhook |

## USSD Menu

```
1. Balance Inquiry
2. Mini Statement
3. Transfers (PIN required)
4. Loan Services (PIN required)
5. Pay Bill from SACCO Account (PIN required)
6. M-Pesa Loan Repayment (PIN required)
0. Exit
```

## ULM Write API Endpoints

Called by this service (require `X-USSD-API-Key` header):

- `POST /api/v1/sacco/ussd/actions/transfer`
- `POST /api/v1/sacco/ussd/actions/loan-application`
- `POST /api/v1/sacco/ussd/actions/utility-payment`
- `POST /api/v1/sacco/ussd/actions/mpesa-stk`
- `GET /api/v1/sacco/ussd/actions/providers`
