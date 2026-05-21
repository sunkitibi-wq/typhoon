# Typhoon Banking Platform — Full Documentation

> **Version:** 1.0 (MVP)
> **Last updated:** May 2026
> **Stack:** Laravel 13, React 19, Inertia.js 3, PostgreSQL/SQLite, Redis

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [System Architecture](#2-system-architecture)
3. [Database Schema](#3-database-schema)
4. [Feature Documentation](#4-feature-documentation)
5. [API Documentation](#5-api-documentation)
6. [Services Layer](#6-services-layer)
7. [Frontend Pages](#7-frontend-pages)
8. [Deployment Guide](#8-deployment-guide)
9. [User Guide](#9-user-guide)

---

## 1. Project Overview

### 1.1 About Typhoon Banking

**Typhoon Banking** is a full-stack digital banking platform that combines traditional banking services (accounts, SEPA/SWIFT transfers, IBAN management) with cryptocurrency exchange capabilities in a unified system.

| Property | Value |
|---|---|
| Platform name | Typhoon Banking |
| Version | 1.0 – MVP |
| Framework | Laravel 13 (PHP 8.3+) |
| Frontend | React 19 + Inertia.js + Tailwind CSS v4 |
| Database | SQLite (dev) / PostgreSQL (production) |
| Auth (web) | Laravel Fortify (session) |
| Auth (API) | Laravel Sanctum (bearer tokens) |
| Queue | Laravel Queue (Redis/database) |
| Cache | Redis + file fallback |

### 1.2 Target Users

| User Type | Access | Description |
|---|---|---|
| **Retail Client** | Web dashboard + API | Personal accounts, transfers, crypto |
| **Corporate Client** | Web dashboard + API | Business accounts, bulk payments, multi-user |
| **Admin** | `/admin/*` panel | Full platform control, user management |
| **Compliance Officer** | `/admin/monitoring` | KYC verification, transaction monitoring |

### 1.3 Key Modules

- **Account Management**: Personal, savings, business accounts with IBAN/SWIFT
- **Payments**: Internal transfers, SEPA credit transfers, SWIFT international
- **Crypto Exchange**: Multi-currency wallets, market/limit orders, exchange rates
- **KYC/AML**: Tiered identity verification, document upload, sanctions screening
- **Compliance**: Rule-based transaction monitoring, suspicious activity reporting
- **Corporate**: Business profiles, multi-user roles, bulk payments, API access
- **Fee Engine**: Configurable fee schedules (fixed, percentage, tiered)
- **Reports**: Account statements, portfolio overview, admin dashboard

---

## 2. System Architecture

### 2.1 Architecture Diagram

```
┌──────────────────────────────────────────────────────────┐
│                    Typhoon Banking Platform               │
│                                                           │
│  ┌──────────────────┐   ┌──────────────────┐             │
│  │  Client Dashboard │   │  Admin Panel      │            │
│  │  (React/Inertia)  │   │  (React/Inertia)  │            │
│  └────────┬─────────┘   └────────┬─────────┘             │
│           │                      │                        │
│  ┌────────▼──────────────────────▼──────────────────┐    │
│  │              Laravel 13 Application Core          │    │
│  │                                                    │    │
│  │  ┌──────────┐  ┌──────────┐  ┌─────────────────┐  │    │
│  │  │ Fortify  │  │ Sanctum  │  │  Spatie Roles   │  │    │
│  │  │ (Web)    │  │ (API)    │  │  & Permissions  │  │    │
│  │  └──────────┘  └──────────┘  └─────────────────┘  │    │
│  │                                                    │    │
│  │  ┌──────────────────────────────────────────────┐  │    │
│  │  │              Service Layer                    │  │    │
│  │  │  AccountService │ TransactionService          │  │    │
│  │  │  KycService │ CryptoExchangeService           │  │    │
│  │  │  ComplianceService │ ReportService            │  │    │
│  │  │  SepaService │ SwiftService                   │  │    │
│  │  │  CorporateService                             │  │    │
│  │  └──────────────────────┬───────────────────────┘  │    │
│  │                         │                           │    │
│  │  ┌──────────────────────▼───────────────────────┐  │    │
│  │  │                Data Layer                     │  │    │
│  │  │  Eloquent Models + Migrations (35+ tables)   │  │    │
│  │  └──────────────────────────────────────────────┘  │    │
│  └──────────────────────────────────────────────────┘    │
│                                                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐       │
│  │ Redis       │  │ Queue       │  │ Scheduler   │       │
│  │ (Cache/     │  │ Worker      │  │ (Cron)      │       │
│  │  Sessions)  │  │             │  │             │       │
│  └─────────────┘  └─────────────┘  └─────────────┘       │
└──────────────────────────────────────────────────────────┘
```

### 2.2 Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Reactive UI | React 19 + Inertia.js 3 |
| CSS | Tailwind CSS v4 |
| UI Components | Radix UI primitives + Lucide icons |
| Charts | Chart.js |
| Auth (web) | Laravel Fortify |
| Auth (API) | Laravel Sanctum |
| Roles | Spatie Laravel Permission |
| Queue | Laravel Queue (database/Redis) |
| Cache | Redis + file fallback |
| Database (dev) | SQLite |
| Database (prod) | PostgreSQL |

### 2.3 Directory Structure

```
typhoon/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/          # REST API controllers
│   │   │   ├── BankingController.php
│   │   │   ├── AdminController.php
│   │   │   ├── CorporateController.php
│   │   │   └── WebhookController.php
│   │   └── Middleware/
│   │       └── EnsureUserHasRole.php
│   ├── Models/
│   │   ├── Banking/          # Banking domain models
│   │   │   ├── Account.php
│   │   │   ├── Transaction.php
│   │   │   ├── KycVerification.php
│   │   │   ├── CryptoCurrency.php
│   │   │   ├── CryptoWallet.php
│   │   │   ├── CryptoOrder.php
│   │   │   ├── ExchangeRate.php
│   │   │   ├── SepaTransfer.php
│   │   │   ├── SwiftTransfer.php
│   │   │   ├── BusinessProfile.php
│   │   │   ├── StandingOrder.php
│   │   │   ├── FeeSchedule.php
│   │   │   ├── MonitoringRule.php
│   │   │   ├── MonitoringAlert.php
│   │   │   ├── Beneficiary.php
│   │   │   ├── BulkPayment.php
│   │   │   ├── ApiClient.php
│   │   │   ├── PlatformSetting.php
│   │   │   ├── BankNotification.php
│   │   │   ├── Loan.php
│   │   │   ├── LoanRepayment.php
│   │   │   └── WebhookEvent.php
│   │   ├── User.php
│   │   ├── Role.php
│   │   └── AuditLog.php
│   └── Services/
│       ├── AccountService.php
│       ├── BlockchainService.php
│       ├── TransactionService.php
│       ├── KycService.php
│       ├── CryptoExchangeService.php
│       ├── ComplianceService.php
│       ├── SepaService.php
│       ├── SwiftService.php
│       ├── CorporateService.php
│       ├── LoanService.php
│       └── ReportService.php
├── database/
│   ├── migrations/           # 12 migration files
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── BankingSeeder.php  # Demo data seeder
├── resources/
│   └── js/
│       ├── pages/
│       │   ├── banking/      # Client banking pages
│       │   ├── admin/        # Admin panel pages
│       │   └── corporate/    # Corporate portal pages
│       ├── components/       # UI components
│       └── layouts/          # App layouts
├── routes/
│   ├── web.php              # Web + banking routes
│   ├── api.php              # REST API routes
│   └── settings.php
└── config/
    ├── auth.php             # Added sanctum guard
    ├── fortify.php
    └── sanctum.php
```

---

## 3. Database Schema

The platform uses **35+ tables** across 7 domains. Below is the complete schema overview.

### 3.1 Accounts Domain

| Table | Key Columns | Purpose |
|---|---|---|
| `account_types` | code, name, currency, minimum_balance, monthly_fee, features | Account product definitions |
| `accounts` | user_id, account_type_id, account_number (unique), iban, swift_bic, currency, balance, available_balance, ledger_balance, status | Customer accounts |
| `account_beneficiaries` | user_id, name, iban, bic, bank_name | Saved transfer recipients |

### 3.2 Transactions Domain

| Table | Key Columns | Purpose |
|---|---|---|
| `transactions` | reference (unique), type, status, debit_account_id, credit_account_id, user_id, amount, fee, net_amount, currency | Central transaction ledger |
| `transaction_fees` | transaction_id, fee_type, amount | Per-transaction fee breakdown |
| `standing_orders` | user_id, debit_account_id, beneficiary, amount, frequency, next_execution_at | Recurring payments |
| `sepa_transfers` | transaction_id, creditor_iban, creditor_bic, remittance_info | SEPA credit transfers |
| `swift_transfers` | transaction_id, beneficiary_bic, beneficiary_bank, charge_bearer | International wire transfers |

### 3.3 KYC & Compliance Domain

| Table | Key Columns | Purpose |
|---|---|---|
| `kyc_verifications` | user_id (unique), kyc_level, status, id_type, id_number, country, date_of_birth | Identity verification records |
| `kyc_documents` | kyc_verification_id, document_type, file_path, status | Uploaded ID documents |
| `sanctions_screenings` | user_id, list_type, status, screening_result | Sanctions/PEP screening logs |
| `suspicious_activities` | user_id, transaction_id, risk_level, category, evidence | SAR reports |
| `monitoring_rules` | name, category, rule_type, conditions, severity | AML rule configuration |
| `monitoring_alerts` | monitoring_rule_id, user_id, transaction_id, severity, status | Generated compliance alerts |

### 3.4 Crypto Domain

| Table | Key Columns | Purpose |
|---|---|---|
| `crypto_currencies` | code (unique), name, network, decimals, withdrawal_fee | Supported cryptocurrencies |
| `crypto_wallets` | user_id, crypto_currency_id (unique pair), address, balance | Per-user crypto wallets |
| `exchange_rates` | base_currency, quote_currency (unique pair), bid, ask, mid_rate | Real-time price feeds |
| `crypto_orders` | order_number (unique), user_id, side, amount, price, status | Buy/sell order book |
| `crypto_deposits` | tx_hash (unique), amount, status, confirmations | Incoming crypto deposits |
| `crypto_withdrawals` | to_address, amount, status, approved_by | Outgoing crypto withdrawals |

### 3.5 Corporate Domain

| Table | Key Columns | Purpose |
|---|---|---|
| `business_profiles` | user_id, company_name, registration_number, tax_id, status | Business account profiles |
| `corporate_users` | business_profile_id, user_id, role, permissions, spending_limit | Multi-user business access |
| `bulk_payments` | batch_reference (unique), total_transactions, total_amount, status | Batch payment runs |
| `bulk_payment_items` | bulk_payment_id, beneficiary_name, iban, amount, status | Individual payment within batch |
| `api_clients` | client_id (unique), client_secret, scopes, allowed_ips | Programmatic API access |

### 3.6 Settings & Configuration

| Table | Key Columns | Purpose |
|---|---|---|
| `fee_schedules` | fee_type, calculation_method, fee_value, tiers, applicable_channels | Configurable fee structures |
| `platform_settings` | key (unique), value, group, type | Global platform configuration |
| `bank_notifications` | user_id, type, channel, title, body, status | Notification records |

### 3.7 Core Tables

| Table | Key Columns | Notes |
|---|---|---|
| `users` | +kyc_level, phone, nationality, date_of_birth, country_of_residence, two_factor_enabled | Extended for banking |
| `roles` | name, description | Spatie-style role management |
| `audit_logs` | user_id, route_name, method, url, ip, status_code, request_payload, meta | Full request audit trail |

### 3.8 Loans Domain

| Table | Key Columns | Purpose |
|---|---|---|
| `loans` | user_id, account_id, loan_number (unique), amount, interest_rate, term_months, monthly_payment, total_payable, paid_amount, status | Applied and disbursed loans |
| `loan_repayments` | loan_id, installment_number, due_date, scheduled_amount, paid_amount, remaining_balance, status | Scheduled and completed loan repayments |

### 3.9 Webhooks Domain

| Table | Key Columns | Purpose |
|---|---|---|
| `webhook_events` | event_type, source, payload, status, error_message, processed_at | Received webhook log and processing queue |

---

## 4. Feature Documentation

### 4.1 Account Management

- Open personal, savings, or business accounts
- Automatic IBAN generation per country
- Default account selection
- Balance tracking (current, available, ledger)
- Account freezing/closing with validation
- Multi-currency support

### 4.2 Payments & Transfers

**Internal Transfers:**
- Between own accounts or other platform users
- Real-time balance updates
- Fee calculation based on fee schedules
- Transaction reference generation

**SEPA Credit Transfers:**
- IBAN-based transfers within SEPA zone
- End-to-end ID tracking
- Purpose code support

**SWIFT International Transfers:**
- Global beneficiary bank transfers
- Intermediary bank support
- Configurable charge bearer (SHA/OUR/BEN)
- Fee calculation per transfer

### 4.3 Crypto Exchange

- Multi-currency wallet creation (BTC, ETH, USDT, SOL, XRP)
- Market and limit orders
- Real-time exchange rate quotes
- Deposit tracking with blockchain confirmations
- Withdrawal requests with admin approval
- Built-in fee structure per currency

### 4.4 KYC & Identity Verification

- Tiered verification levels (tier_1, tier_2, tier_3)
- Document upload (passport, ID card, selfie, proof of address)
- Admin review queue with approve/reject workflow
- Source of funds declaration
- Sanctions screening integration
- KYC level determines account limits

### 4.5 Compliance & Monitoring

- Configurable rule-based transaction monitoring
- Real-time alert generation on rule matches
- Severity-based alert triage (high/medium/low)
- Alert resolution workflow
- Suspicious activity reporting (SARs)
- Full audit logging of all admin actions

### 4.6 Corporate & Business

- Business profile registration (with company documents)
- Multi-user role management (admin, finance, operator, viewer)
- Spending limits per corporate user
- Bulk payment processing for payroll/suppliers
- API client management for business integrations

### 4.7 Account Statements & Reports

- Date-range filtered account statements
- Transaction history with type/status filtering
- Portfolio overview (fiat + crypto combined value)
- Admin dashboard with real-time KPIs
- Monthly income/spending analytics

---

## 5. API Documentation

Base URL: `https://typhoon.com/api`  
Auth: Bearer token (Sanctum)

### 5.1 Authentication

```
POST /api/auth/login          { email, password } → { token, user }
POST /api/auth/register       { name, email, password, password_confirmation } → { token, user }
POST /api/auth/logout         → { message }
GET  /api/auth/me             → { user { id, name, email, accounts, kyc_status } }
```

### 5.2 Accounts

```
GET    /api/accounts                    → { accounts[] }
POST   /api/accounts                    { account_type_code, currency?, label? } → { account }
GET    /api/accounts/{id}               → { account }
GET    /api/accounts/{id}/statement     ?from=&to= → { transactions, balances }
POST   /api/accounts/{id}/set-default   → { message }
GET    /api/account-types               → { account_types[] }
```

### 5.3 Transactions

```
GET    /api/transactions        ?type=&status=&from=&to= → paginated list
POST   /api/transactions/transfer  { from_account_id, to_account_id, amount, description? } → { transaction }
GET    /api/transactions/{id}   → { transaction with fees, sepa, swift }
```

### 5.4 KYC

```
GET  /api/kyc/status           → { status, kyc_level, submitted }
POST /api/kyc/submit           { country, date_of_birth, id_type?, address? } → { message }
POST /api/kyc/documents        { document_type, file } → { document }
```

### 5.5 Beneficiaries

```
GET    /api/beneficiaries      → { beneficiaries[] }
POST   /api/beneficiaries      { name, iban, bic?, bank_name? } → { beneficiary }
PUT    /api/beneficiaries/{id} → { beneficiary }
DELETE /api/beneficiaries/{id} → { message }
```

### 5.6 Crypto

```
GET    /api/crypto/currencies        → { currencies[] }
GET    /api/crypto/wallets           → { wallets[] }
POST   /api/crypto/wallets           { currency_code, label? } → { wallet }
GET    /api/crypto/rates             → { rates[] }
GET    /api/crypto/quote             ?from=&to=&amount= → { quote }
POST   /api/crypto/orders            { base_currency, quote_currency, side, amount } → { order }
GET    /api/crypto/orders            → paginated list
```

### 5.7 Portfolio

```
GET /api/portfolio → { total_portfolio_value_eur, accounts[], crypto_wallets[] }
```

### 5.8 Error Format

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

| Status | Meaning |
|---|---|
| 200 | Success |
| 201 | Created |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not found |
| 422 | Validation error |
| 429 | Rate limited |

---

## 6. Services Layer

### AccountService
- `createAccount(User, typeCode, currency, label)` — Opens new account with IBAN
- `getBalance(Account)` — Returns balance breakdown
- `closeAccount(Account)` — Validates zero balance before close
- `freezeAccount(Account) / unfreezeAccount(Account)` — Status management

### TransactionService
- `transfer(Account from, Account to, amount, description, initiator)` — Core transfer with fee calc
- `deposit(Account, amount, method, reference)` — Credit transaction
- `withdraw(Account, amount, method)` — Debit transaction with validation
- `reverseTransaction(Transaction, reason)` — Full reversal with balance correction
- Fee calculation supports fixed, percentage, and tiered methods

### KycService
- `submitVerification(User, data)` — Create/update KYC record
- `uploadDocument(KycVerification, file, type)` — Store verification documents
- `approveVerification(KycVerification, Admin)` — Approve and upgrade user level
- `rejectVerification(KycVerification, Admin, reason)` — Reject with feedback
- `getVerificationStatus(User)` — Current KYC state

### CryptoExchangeService
- `createWallet(User, CryptoCurrency, label)` — Generate crypto wallet
- `placeOrder(User, data)` — Market/limit order creation
- `executeMarketOrder(CryptoOrder)` — Instant order fulfillment
- `recordDeposit(data)` — Incoming crypto deposit
- `confirmDeposit(CryptoDeposit)` — Confirm and credit wallet
- `requestWithdrawal(User, Wallet, amount, address)` — Outgoing request
- `approveWithdrawal(Withdrawal, Admin)` — Admin approval
- `getQuote(from, to, amount)` — Price quotation

### ComplianceService
- `screenUser(User)` — Sanctions list screening
- `evaluateTransaction(Transaction)` — Rule-based transaction evaluation
- `reportSuspiciousActivity(...)` — File SAR
- `resolveAlert(Alert, resolver, resolution, notes)` — Close alert

### SepaService / SwiftService
- SEPA credit transfer and direct debit creation
- SWIFT international transfer with charge bearer options

### CorporateService
- Business profile registration
- Team member invitation with role-based permissions
- Bulk payment batch processing with per-item status tracking

### ReportService
- Account statement generation with opening/closing balances
- User portfolio overview (fiat + crypto)
- Admin dashboard analytics

### BlockchainService
- `generateAddress()` — Generates keypair addresses for crypto wallets
- `getBalance(address)` — Calls blockchain nodes to fetch current balance
- `sendTransaction(from, to, value, data)` — Submits transactions to the network
- `getTransactionStatus(txHash)` — Returns confirmations and status of on-chain tx
- `watchDeposits(address)` — Monitors wallet addresses for incoming transactions

### LoanService
- `apply(User, Account, amount, interestRate, termMonths)` — Create pending loan application
- `underwrite(Loan, Admin)` — Sets status to underwriting
- `approve(Loan, Admin) / reject(Loan, Admin, reason)` — Approval triage workflows
- `disburse(Loan, Admin)` — Triggers deposit and creates repayment schedules
- `makePayment(Loan, Account, installment)` — Executes repayment transfer
- `checkOverdue()` — Cron check of overdue payments; defaults users after 3 missed schedules

---

## 7. Frontend Pages

### Client Banking Pages

| Route | Page | Purpose |
|---|---|---|
| `/banking/dashboard` | Overview | Balance cards, accounts list, recent transactions |
| `/banking/accounts` | Accounts | Grid of all accounts with balances and status |
| `/banking/accounts/create` | New Account | Open new account |
| `/banking/accounts/{id}` | Account Detail | Full account info + statement |
| `/banking/transactions` | Transactions | Filterable transaction history |
| `/banking/transfer` | Transfer | Internal transfer form |
| `/banking/kyc` | KYC Verification | Submit identity documents |
| `/banking/crypto` | Crypto Exchange | Wallet balances + exchange rates |
| `/banking/deposit` | Deposit | Fiat deposit initiation simulation |
| `/banking/loans` | Loans | Loan application form and active listings |
| `/banking/loans/{id}` | Loan Detail | Repayment schedule tracker and instant pay modal |
| `/banking/notifications` | Notifications | In-app user notifications log |
| `/banking/sepa` | SEPA Transfer | SEPA credit transfers & direct debits forms |
| `/banking/standing-orders` | Standing Orders | Recurring transfer setup and scheduler control |
| `/banking/statements` | Account Statements | Account statements with balance breakdown |
| `/banking/swift` | SWIFT Transfer | International wire transfer setup form |

### Admin Pages

| Route | Page | Purpose |
|---|---|---|
| `/admin/dashboard` | Overview | Platform KPIs, recent activity |
| `/admin/kyc` | KYC Queue | Pending verifications with review modal |
| `/admin/monitoring` | Alerts | Compliance alert management |
| `/admin/users` | Users | User directory with role/status info |
| `/admin/crypto-deposits` | Crypto Deposits | Confirm incoming crypto deposits queue |
| `/admin/crypto-withdrawals`| Crypto Withdrawals| Approve outgoing crypto withdrawals queue |
| `/admin/fee-schedules` | Fee Schedules | Create and toggle transaction fee rules |
| `/admin/loans` | Loans | Admin underwriting panel for loan applications |
| `/admin/platform-settings` | Platform Settings | Update global app options and currencies |
| `/admin/audit-logs` | Audit Logs | Read-only security event logger view |
| `/admin/api-clients` | API Clients | Revoke and manage client developer API keys |

### Corporate Portal Pages

| Route | Page | Purpose |
|---|---|---|
| `/corporate/dashboard` | Overview | Business account summary and bulk payment stats |
| `/corporate/business-profile`| Profile Setup | Manage business documents and profile registration |
| `/corporate/team` | Team | Invite members and assign custom permissions |
| `/corporate/bulk-payments` | Bulk Payments | Upload and process batch SEPA/IBAN payment sheets |

---

## 8. Deployment Guide

### Prerequisites
- PHP 8.3+
- Composer
- Node.js 20+
- PostgreSQL 14+ (recommended) or SQLite
- Redis (optional, for cache/queue)

### Quick Start

```bash
# Clone and install
git clone https://github.com/typhoon/banking.git
cd banking
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database (SQLite for local dev)
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=BankingSeeder

# Frontend
npm run build

# Serve
php artisan serve
```

### Production Checklist
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure PostgreSQL in `.env`
- [ ] Set `CACHE_DRIVER=redis` and `SESSION_DRIVER=redis`
- [ ] Configure Sanctum stateful domains
- [ ] Set up queue worker: `php artisan queue:work`
- [ ] Set up scheduler: `* * * * * php artisan schedule:run`
- [ ] Configure Nginx with PHP-FPM
- [ ] Enable HTTPS with Let's Encrypt

---

## 9. User Guide

### First Time Setup
1. Register at `/register`
2. Complete KYC verification at `/banking/kyc`
3. Your first account is created automatically
4. Fund your account via bank transfer or crypto deposit

### Making a Transfer
1. Go to **Banking → Transfer**
2. Select source account
3. Select destination account
4. Enter amount and description
5. Confirm — transfer is instant between Typhoon accounts

### Crypto Trading
1. Create a wallet at **Crypto Exchange**
2. Check live rates
3. Place a market order to buy/sell instantly
4. Track order history

### Admin Tasks
- **KYC**: Review → Approve/Reject with reason
- **Monitoring**: Review alerts → Resolve with notes
- **Users**: View all users and their account status

### Demo Credentials
```
Admin:  admin@typhoon.com / password
Client: client@typhoon.com / password
```
