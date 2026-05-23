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
10. [Authentication & Authorization](#10-authentication--authorization)
11. [Configuration Reference](#11-configuration-reference)
12. [Email & Notifications](#12-email--notifications)
13. [Queue System & Background Jobs](#13-queue-system--background-jobs)
14. [Error Handling & Logging](#14-error-handling--logging)
15. [Testing Guide](#15-testing-guide)
16. [Development Workflow](#16-development-workflow)
17. [Security Best Practices](#17-security-best-practices)
18. [Environment Variables](#18-environment-variables)
19. [Performance Optimization](#19-performance-optimization)
20. [Troubleshooting](#20-troubleshooting)
21. [API Integrations](#21-api-integrations)

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

---

## 10. Authentication & Authorization

### 10.1 Web Authentication (Laravel Fortify)

Fortify handles all session-based web authentication flows:

**Features:**
- Registration with email verification
- Login with "remember me" option
- Password reset flow (email link)
- 2FA/TOTP with recovery codes
- Profile update with password confirmation

**Key Files:**
- `app/Actions/Fortify/` — Action classes for each flow
- `config/fortify.php` — Fortify configuration
- `routes/auth.php` — Auto-generated auth routes (handled by Fortify)

**Two-Factor Setup:**
```php
// User initiates 2FA
POST /auth/two-factor-auth { force_confirmation: false }

// Responds with QR code for Authenticator app
// User confirms by entering TOTP code

// Recovery codes can be regenerated anytime
POST /auth/two-factor-recovery-codes
```

### 10.2 API Authentication (Laravel Sanctum)

Sanctum provides token-based API authentication:

**Flow:**
```
1. User logs in via POST /login
2. Response includes { token: "..." }
3. All API requests: Authorization: Bearer {token}
4. Token stored client-side (secure cookie or localStorage)
```

**Token Features:**
- Stateless (no session required)
- Bearer token format
- Scopes for permission granularity
- Can expire or be revoked
- Multi-device support (separate tokens per device)

**Usage:**
```bash
# Create token
curl -X POST https://typhoon.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"..."}'

# Use token
curl -X GET https://typhoon.com/api/accounts \
  -H "Authorization: Bearer {token}"
```

### 10.3 Role-Based Access Control (Spatie Laravel Permission)

Permissions & roles are managed via Spatie package:

**Roles:**
- `admin` — Full platform access
- `compliance` — KYC & monitoring access
- `corporate_admin` — Business profile management
- `corporate_finance` — Bulk payments & transfers
- `corporate_operator` — Transaction initiation
- `corporate_viewer` — Read-only access

**Permissions:**
- Scoped by feature (e.g., `kyc:approve`, `transfer:create`)
- Assigned to roles
- Checked via middleware and policies

**Checking Permission:**
```php
// In controller
$user->hasPermissionTo('kyc:approve') // true/false

// In middleware
Route::post('/kyc/{id}/approve', [...])->middleware('permission:kyc:approve');

// In Blade
@can('kyc:approve')
  <button>Approve</button>
@endcan
```

**Corporate Role Assignment:**
```php
$corporateUser = CorporateUser::where('business_profile_id', $business->id)
    ->where('user_id', $user->id)
    ->first();

$corporateUser->role; // 'admin' | 'finance' | 'operator' | 'viewer'
$corporateUser->spending_limit; // Per-user daily limit in cents
```

---

## 11. Configuration Reference

### 11.1 config/auth.php

```php
return [
    'defaults' => [
        'guard' => 'web',                   // Web uses sessions
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'sanctum' => [
            'driver' => 'sanctum',          // API uses Sanctum
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],
];
```

### 11.2 config/fortify.php

```php
return [
    'guard' => 'web',
    'passwords' => 'users',

    'features' => [
        Features::registration(),               // Enable user signup
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::twoFactorAuthentication([
            'confirmPassword' => false,
        ]),
    ],

    'views' => true,                            // Use custom Fortify views
];
```

### 11.3 config/sanctum.php

```php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
    'expiration' => null,                       // Tokens never expire (until revoked)
    'token_prefix' => 'typhoon',
    'middleware' => [
        'throttle:60,1',                        // Rate limit
    ],
];
```

### 11.4 config/cashier.php (Stripe Integration - Optional)

```php
return [
    'model' => App\Models\User::class,
    'key' => env('STRIPE_SECRET'),
    'path' => 'stripe',
    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => 300,
    ],
];
```

### 11.5 config/queue.php

```php
return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 86400,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 86400,
            'block_for' => null,
        ],
    ],
];
```

---

## 12. Email & Notifications

### 12.1 Email Configuration

Set in `.env`:
```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@typhoon.com
MAIL_FROM_NAME="Typhoon Banking"
```

### 12.2 Notification Templates

**Welcome Email** — `app/Mail/WelcomeMail.php`
- Sent on registration
- Contains activation link if email verification enabled

**Transfer Confirmation** — `app/Notifications/TransferNotification.php`
- Sent to recipient after internal transfer
- Includes transaction reference

**KYC Status Update** — `app/Notifications/KycApproved.php` / `KycRejected.php`
- Notifies user on verification completion
- Rejected: includes reason

**Crypto Deposit Confirmed** — Event listener
- Sends when blockchain confirms 3+ blocks

**Loan Disbursement** — `LoanService::disburse()`
- Notifies user when loan funded
- Includes repayment schedule

### 12.3 In-App Notifications

Stored in `bank_notifications` table:
```php
BankNotification::create([
    'user_id' => $user->id,
    'type' => 'transfer_received',          // transfer_received, kyc_status, loan_.*
    'channel' => 'in_app',                  // 'in_app' | 'email' | 'sms'
    'title' => 'Transfer Received',
    'body' => 'You received €100 from Alice',
]);
```

---

## 13. Queue System & Background Jobs

### 13.1 Running the Queue Worker

```bash
# Development
php artisan queue:work --verbose

# Production (with supervisor)
php artisan queue:work --timeout=60 --tries=3
```

### 13.2 Job Types

| Job Class | Trigger | Purpose |
|---|---|---|
| `ProcessWebhookEvent` | Webhook received | Blockchain webhook processing |
| `ConfirmCryptoDeposit` | After 3 confirmations | Credit user wallet + notify |
| `ProcessRefund` | Transaction reversal | Reverse balance and ledger |
| `GenerateStatement` | End-of-month | Create PDF statement |
| `CheckOverdueLoans` | Daily scheduler | Default loans after 3 missed payments |
| `SyncExchangeRates` | Hourly scheduler | Fetch latest rates from external feed |

### 13.3 Scheduled Jobs (Cron)

Configured in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Hourly exchange rate sync
    $schedule->command('rates:sync')->hourly();

    // Daily overdue loan check
    $schedule->command('loans:check-overdue')->daily();

    // Monthly statement generation
    $schedule->command('statements:generate')->monthly();

    // Every 5 minutes: check for pending webhook retries
    $schedule->command('webhooks:retry')->everyFiveMinutes();
}
```

**Install supervisor for production:**
```ini
[program:typhoon-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/typhoon/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/typhoon-queue.log
```

---

## 14. Error Handling & Logging

### 14.1 Exception Handling

All exceptions handled in `app/Exceptions/Handler.php`:

```php
public function register()
{
    $this->reportable(function (InsufficientFundsException $e) {
        Log::warning('Insufficient funds', ['account' => $e->account_id]);
    });

    $this->renderable(function (InsufficientFundsException $e, $request) {
        if ($request->expectsJson()) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    });
}
```

### 14.2 Custom Exceptions

| Exception | When | Status |
|---|---|---|
| `InsufficientFundsException` | Balance < amount | 422 |
| `AccountLockedException` | Account frozen/closed | 403 |
| `KycRequiredException` | User kyc_level insufficient | 403 |
| `DuplicateTransactionException` | Idempotency key exists | 409 |
| `BlockchainException` | Blockchain call fails | 502 |

### 14.3 Logging Configuration

Set in `.env`:
```
LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_DAILY_DAYS=7
```

**Log Channels:**
- `stack` — Combines multiple channels (daily file + Slack)
- `single` — Writes to single `storage/logs/laravel.log`
- `slack` — Sends critical errors to Slack channel

**Log Levels:**
```
DEBUG < INFO < NOTICE < WARNING < ERROR < CRITICAL < ALERT < EMERGENCY
```

**Audit Logging Example:**
```php
// Every request logged to audit_logs table
AuditLog::create([
    'user_id' => auth()->id(),
    'route_name' => $request->route()?->getName(),
    'method' => $request->method(),
    'url' => $request->getPathInfo(),
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'request_payload' => $request->except(['password', 'token']),
    'status_code' => $response->status(),
]);
```

---

## 15. Testing Guide

### 15.1 Test Structure

```
tests/
├── Feature/
│   ├── AccountTest.php
│   ├── TransactionTest.php
│   ├── CryptoTest.php
│   ├── KycTest.php
│   └── AdminTest.php
└── Unit/
    ├── Services/
    │   ├── AccountServiceTest.php
    │   ├── TransactionServiceTest.php
    │   └── ComplianceServiceTest.php
    └── Models/
        └── TransactionTest.php
```

### 15.2 Running Tests

```bash
# Run all tests
php artisan test

# Run specific file
php artisan test tests/Feature/AccountTest.php

# Run with coverage
php artisan test --coverage

# Run with specific env
APP_ENV=testing php artisan test
```

### 15.3 Example Test

```php
class TransactionTest extends TestCase
{
    use RefreshDatabase;                    // Rollback DB after each test

    public function test_can_transfer_between_accounts()
    {
        // Setup
        $user = User::factory()->create();
        $from = Account::factory()->for($user)->create(['balance' => 100_00]);
        $to = Account::factory()->create(['balance' => 0]);

        // Act
        $response = $this->actingAs($user)->post('/banking/transfer', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 50_00,
        ]);

        // Assert
        $response->assertRedirect('/banking/transactions');
        $from->refresh();
        $to->refresh();
        $this->assertEquals(50_00, $from->balance);
        $this->assertEquals(50_00, $to->balance);
    }
}
```

### 15.4 Database Seeding for Tests

```php
// Use BankingSeeder in test setup
protected function setUp(): void
{
    parent::setUp();
    $this->seed(BankingSeeder::class);
}

// Or per-test
public function test_something()
{
    $this->seed(BankingSeeder::class);
    // ...
}
```

---

## 16. Development Workflow

### 16.1 Local Development Setup

```bash
# 1. Clone repo
git clone https://github.com/led/typhoon-banking.git
cd typhoon-banking

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Database (SQLite for local)
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=BankingSeeder

# 5. Build frontend
npm run dev        # Development (watch mode)
npm run build      # Production build

# 6. Run server
php artisan serve  # Runs on http://localhost:8000
```

### 16.2 Code Standards

**PHP Coding Style:**
- PSR-12 (Laravel standard)
- Checked with `./vendor/bin/pint`

```bash
# Fix style issues
./vendor/bin/pint resources/ app/

# Check without fixing
./vendor/bin/pint --test
```

**Frontend:**
- ESLint for JavaScript/TypeScript
```bash
npm run lint        # Check
npm run lint:fix    # Fix
```

### 16.3 Git Workflow

```bash
# Feature branch
git checkout -b feature/add-xyz-feature
git add .
git commit -m "feat: add xyz feature"
git push origin feature/add-xyz-feature
# → Create PR on GitHub

# After merge
git checkout main
git pull
git branch -d feature/add-xyz-feature
```

**Commit message format:**
```
<type>: <subject>

<body>

<footer>
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `chore`

---

## 17. Security Best Practices

### 17.1 Input Validation

Always validate user input using Form Requests:

```php
// app/Http/Requests/TransferRequest.php
class TransferRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('transfer:create');
    }

    public function rules()
    {
        return [
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }
}
```

### 17.2 Cross-Site Request Forgery (CSRF)

- All non-GET requests include CSRF token
- Token validated in `VerifyCsrfToken` middleware
- Token rotated after login for security

```blade
<!-- Blade form -->
<form method="POST">
    @csrf
    <!-- ... -->
</form>

// React form (via Inertia)
<Form method="post" action={transfer()}>
```

### 17.3 SQL Injection Prevention

Always use parameterized queries:

```php
// ✗ UNSAFE
User::whereRaw("email = '$email'")->first();

// ✓ SAFE
User::where('email', $email)->first();
User::whereIn('status', ['active', 'pending'])->get();
```

### 17.4 Rate Limiting

Applied to sensitive endpoints:

```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');        // 6 attempts per 1 minute

Route::post('/api/transfer', [...])->middleware('throttle:100,1');  // 100 req/min
```

### 17.5 Password Security

- Passwords hashed using bcrypt (Laravel default)
- Minimum 8 characters enforced in validation
- Password reset link expires after 60 minutes
- Cannot reuse last 5 passwords (corporate users)

### 17.6 Two-Factor Authentication

- TOTP (Time-based OTP) mandatory for admins
- Recovery codes generated on setup (stored encrypted)
- Regenerate codes periodically

### 17.7 API Token Security

- Tokens issued with narrow scopes
- Client secret never transmitted in URLs
- HTTPS required in production
- Token expiration/revocation supported

### 17.8 Data Encryption

Sensitive fields encrypted at rest:

```php
// Automatically encrypted
protected $encrypted = ['ssn', 'tax_id', 'bank_account_number'];

// In database, stored as: "payload:..."
// Automatically decrypted on retrieval
```

---

## 18. Environment Variables

### 18.1 Required Variables

```bash
### Application
APP_NAME="Typhoon Banking"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://typhoon.com
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxx

### Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=typhoon
DB_USERNAME=postgres
DB_PASSWORD=...

### Cache & Session
CACHE_DRIVER=redis
CACHE_DEFAULT_TTL=3600
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

### Queue
QUEUE_CONNECTION=redis

### Mail
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@typhoon.com

### Authentication
FORTIFY_FEATURES=registration,reset_passwords,email_verification,two_factor_authentication
SANCTUM_STATEFUL_DOMAINS=typhoon.com,localhost:3000

### Stripe (Optional)
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

### Blockchain
BLOCKCHAIN_NETWORK=mainnet              # mainnet or testnet
BLOCKCHAIN_RPC_URL=https://eth-mainnet.g.alchemy.com/v2/...
BLOCKCHAIN_PRIVATE_KEY=... (DO NOT COMMIT)

### Feature Flags
FEATURE_CRYPTO_ENABLED=true
FEATURE_CORPORATE_ENABLED=true
FEATURE_SEPA_ENABLED=true
FEATURE_SWIFT_ENABLED=true
```

---

## 19. Performance Optimization

### 19.1 Database Query Optimization

**N+1 Query Problem:**
```php
// ✗ SLOW: 1 query + N queries for each user's accounts
$users = User::all();
foreach ($users as $user) {
    echo $user->accounts->count();  // Query inside loop
}

// ✓ FAST: 2 queries total
$users = User::with('accounts')->get();
foreach ($users as $user) {
    echo $user->accounts->count();  // No additional queries
}
```

**Query Optimization Tips:**
```php
// Select only needed columns
User::select(['id', 'name', 'email'])->get();

// Use pagination for large result sets
User::paginate(50);

// Use indexes on frequently queried columns
Schema::create('accounts', function (Blueprint $table) {
    $table->index('user_id');       // User lookup
    $table->index('status');        // Status filtering
    $table->index(['user_id', 'status']);  // Composite index
});
```

### 19.2 Caching Strategy

```php
// Cache account balance for 5 minutes
$balance = Cache::remember("account:{$account->id}:balance", 300, function () use ($account) {
    return $account->balance;
});

// Invalidate cache on transaction
DB::transaction(function () {
    $transaction->save();
    Cache::forget("account:{$from->id}:balance");
    Cache::forget("account:{$to->id}:balance");
});
```

**What to Cache:**
- Exchange rates (refreshed hourly)
- User KYC status
- Fee schedules
- Portfolio calculations

### 19.3 Frontend Performance

**Code Splitting:**
```tsx
// Lazy load admin panel
const AdminDashboard = lazy(() => import('./pages/admin/dashboard'));

<Suspense fallback={<Spinner />}>
    <AdminDashboard />
</Suspense>
```

**Image Optimization:**
- Use next-gen formats (WebP with fallbacks)
- Lazy load below-the-fold images
- Use responsive srcset

---

## 20. Troubleshooting

### 20.1 Common Issues

| Issue | Cause | Solution |
|---|---|---|
| `Call to undefined method Transaction::user()` | Relationship not defined | Add `public function user() { return $this->belongsTo(User::class); }` |
| `Attempting to read property on null` | Model not found | Add null check or use `firstOrFail()` |
| `CSRF token mismatch` | Token expired/missing | Regenerate page, check session config |
| `Queue jobs not running` | Worker not running | Start: `php artisan queue:work` |
| `Memory limit exceeded` | Large result set | Use pagination/chunking: `User::chunk(100, ...)` |
| `Port 8000 already in use` | Another process using port | `php artisan serve --port=8001` |

### 20.2 Debugging

**Laravel Debugbar** (development only):
```bash
composer require barryvdh/laravel-debugbar --dev
```

Displays query count, timing, route info, logs.

**Tinker REPL:**
```bash
php artisan tinker

# Try queries
>>> $user = User::first();
>>> $user->accounts;
>>> $user->accounts()->sum('balance');
```

**Log Inspection:**
```bash
tail -f storage/logs/laravel.log
```

### 20.3 Database Issues

**Reset Database (development only):**
```bash
php artisan migrate:refresh --seed
```

**Check Migrations:**
```bash
php artisan migrate:status
```

**Rollback Last Migration:**
```bash
php artisan migrate:rollback
```

---

## 21. API Integrations

### 21.1 Blockchain RPC Calls

The `BlockchainService` handles Ethereum/EVM blockchain interaction:

```php
// Generate new address
$keypair = $blockchain->generateAddress();
// Returns: ['address' => '0x...', 'privateKey' => '0x...']

// Check wallet balance
$balance = $blockchain->getBalance($address);
// Returns: balance in wei (string)

// Send transaction
$txHash = $blockchain->sendTransaction(
    $fromAddress,
    $toAddress,
    $amountInWei,
    $data  // Optional smart contract data
);

// Get transaction status
$status = $blockchain->getTransactionStatus($txHash);
// Returns: { confirmations: 5, status: 'confirmed' }

// Watch address for incoming deposits
$blockchain->watchDeposits($address);
// Emits event when transaction detected
```

### 21.2 Exchange Rate Feed Integration

Exchange rates updated hourly via external feed:

```php
// Artisan command
php artisan rates:sync

// Implementation
CryptoExchangeService::syncExchangeRates();
// Fetches from configured source, updates database
```

### 21.3 Email Service (Mailtrap/SendGrid)

Emails sent asynchronously via queue:

```php
// Mail is queued automatically
Mail::queue(new WelcomeMail($user));

// Or sent immediately
Mail::send(new WelcomeMail($user));
```

### 21.4 SMS Notifications (Optional - Twilio)

```php
// In notification class
public function toSms($notifiable)
{
    return (new TwilioSms())
        ->setMessage("Your KYC verification was approved.");
}
```

---

## 22. Broadcasting & Real-Time Features

### 22.1 Laravel Reverb WebSocket Setup

Reverb provides real-time WebSocket broadcasting for live updates:

**Configuration** (`config/broadcasting.php`):
```php
return [
    'default' => env('BROADCAST_DRIVER', 'reverb'),

    'connections' => [
        'reverb' => [
            'driver' => 'reverb',
            'host' => env('REVERB_HOST', '127.0.0.1'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'app_id' => env('REVERB_APP_ID'),
            'app_key' => env('REVERB_APP_KEY'),
            'app_secret' => env('REVERB_APP_SECRET'),
        ],
    ],
];
```

**Environment Variables**:
```
BROADCAST_DRIVER=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_ID=typhoon
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

### 22.2 Broadcasting Channels

Define channels in `routes/channels.php`:

```php
// Public channel (no auth required)
Broadcast::channel('exchange-rates', function () {
    return true;
});

// Private channel (auth required)
Broadcast::channel('account.{accountId}', function (User $user, $accountId) {
    $account = Account::find($accountId);
    return $user->id === $account->user_id;
});

// Presence channel (with user info)
Broadcast::channel('notifications.{userId}', function (User $user, $userId) {
    if ((int)$userId === $user->id) {
        return ['id' => $user->id, 'name' => $user->name];
    }
});
```

### 22.3 Broadcasting Events from Models

Create ShouldBroadcast events:

```php
// app/Events/TransactionCreated.php
class TransactionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Transaction $transaction) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("account.{$this->transaction->debit_account_id}"),
            new PrivateChannel("account.{$this->transaction->credit_account_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->transaction->id,
            'reference' => $this->transaction->reference,
            'amount' => $this->transaction->amount,
            'status' => $this->transaction->status,
            'created_at' => $this->transaction->created_at,
        ];
    }
}

// Dispatch after transaction created
TransactionCreated::dispatch($transaction);
```

### 22.4 Client-Side Echo Listeners (React)

```tsx
// resources/js/hooks/useTransactionUpdates.ts
import { useEffect, useState } from 'react';
import Echo from 'laravel-echo';

export function useTransactionUpdates(accountId) {
    const [transaction, setTransaction] = useState(null);

    useEffect(() => {
        // Subscribe to private channel
        window.Echo.private(`account.${accountId}`)
            .listen('.transaction.created', (data) => {
                setTransaction(data);
                // Update UI with new transaction
            })
            .listen('.transaction.updated', (data) => {
                setTransaction(data);
            });

        return () => {
            window.Echo.leaveChannel(`account.${accountId}`);
        };
    }, [accountId]);

    return transaction;
}
```

### 22.5 Running Reverb Server

```bash
# Development
php artisan reverb:start

# Production (with supervisord)
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug
```

---

## 23. Code Examples & Patterns

### 23.1 Eloquent Model Relationships

**One-to-Many Example:**
```php
// app/Models/User.php
class User extends Model
{
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function kycVerification()
    {
        return $this->hasOne(KycVerification::class);
    }
}

// Usage
$user = User::with('accounts', 'transactions')->find($id);
$user->accounts;           // All accounts
$user->accounts()->sum('balance');  // Total balance
```

**Many-to-Many Example:**
```php
// app/Models/Role.php
class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}

// Attach role to user
$user->roles()->attach($roleId);

// Check if user has role
$user->hasRole('admin');
```

**Polymorphic Relationships:**
```php
// app/Models/AuditLog.php
class AuditLog extends Model
{
    public function auditable()
    {
        return $this->morphTo();  // Can audit User, Transaction, Account, etc.
    }
}

// Log transaction
AuditLog::create([
    'auditable_type' => Transaction::class,
    'auditable_id' => $transaction->id,
    'action' => 'created',
    'user_id' => auth()->id(),
]);

// Retrieve
$audit = AuditLog::find($id);
$audit->auditable;  // Returns the Transaction/User/etc
```

### 23.2 Authorization Policies

**Create Policy:**
```bash
php artisan make:policy TransactionPolicy --model=Transaction
```

**Policy File:**
```php
// app/Policies/TransactionPolicy.php
class TransactionPolicy
{
    public function view(User $user, Transaction $transaction)
    {
        return $user->id === $transaction->user_id ||
               $transaction->debit_account->user_id === $user->id ||
               $transaction->credit_account->user_id === $user->id;
    }

    public function reverse(User $user, Transaction $transaction)
    {
        return $user->hasPermissionTo('transaction:reverse') &&
               $transaction->status === 'completed' &&
               now()->diffInHours($transaction->created_at) < 24;
    }
}

// Register policy
protected $policies = [
    Transaction::class => TransactionPolicy::class,
];
```

**Using Policy:**
```php
// In controller
$this->authorize('view', $transaction);
$this->authorize('reverse', $transaction);

// In Blade
@can('reverse', $transaction)
    <button>Reverse Transaction</button>
@endcan

// In API
if ($user->cannot('view', $transaction)) {
    abort(403);
}
```

### 23.3 Middleware Implementation

**Create Middleware:**
```bash
php artisan make:middleware CheckKycLevel
```

**Middleware Code:**
```php
// app/Http/Middleware/CheckKycLevel.php
class CheckKycLevel
{
    public function handle(Request $request, Closure $next, $level = '1')
    {
        $user = $request->user();

        if (!$user || $user->kyc_level < (int)$level) {
            return response()->json(['message' => 'KYC verification required'], 403);
        }

        return $next($request);
    }
}

// Register in Kernel
protected $routeMiddleware = [
    'kyc' => CheckKycLevel::class,
];

// Use in routes
Route::post('/crypto/withdraw', [CryptoController::class, 'withdraw'])
    ->middleware('auth:sanctum', 'kyc:2');
```

### 23.4 Custom Jobs

**Create Job:**
```bash
php artisan make:job ConfirmCryptoDeposit
```

**Job Implementation:**
```php
// app/Jobs/ConfirmCryptoDeposit.php
class ConfirmCryptoDeposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public CryptoDeposit $deposit,
        public int $confirmations = 3
    ) {}

    public function handle(BlockchainService $blockchain)
    {
        $status = $blockchain->getTransactionStatus($this->deposit->tx_hash);

        if ($status['confirmations'] >= $this->confirmations) {
            // Credit wallet
            $wallet = CryptoWallet::where('address', $this->deposit->to_address)->first();
            $wallet->increment('balance', $this->deposit->amount);

            // Update deposit status
            $this->deposit->update(['status' => 'confirmed']);

            // Notify user
            Notification::send(
                $wallet->user,
                new CryptoDepositConfirmed($this->deposit)
            );
        } else {
            // Retry in 1 minute
            $this->release(60);
        }
    }
}

// Dispatch
ConfirmCryptoDeposit::dispatch($deposit);
```

### 23.5 Service Class Pattern

```php
// app/Services/CryptoExchangeService.php
class CryptoExchangeService
{
    public function __construct(
        private BlockchainService $blockchain,
        private FeeScheduleService $fees,
    ) {}

    public function placeOrder(User $user, array $data): CryptoOrder
    {
        return DB::transaction(function () use ($user, $data) {
            $rate = ExchangeRate::where('base_currency', $data['base_currency'])
                ->where('quote_currency', $data['quote_currency'])
                ->firstOrFail();

            $price = $data['side'] === 'buy' ? $rate->ask : $rate->bid;
            $fee = $this->fees->calculateFee('crypto_trade', $data['amount']);

            $order = CryptoOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'side' => $data['side'],
                'base_currency' => $data['base_currency'],
                'quote_currency' => $data['quote_currency'],
                'amount' => $data['amount'],
                'price' => $price,
                'fee' => $fee,
                'status' => 'pending',
            ]);

            if ($data['side'] === 'buy') {
                $this->executeMarketBuy($user, $order);
            }

            return $order;
        });
    }

    private function executeMarketBuy(User $user, CryptoOrder $order): void
    {
        // Execute buy logic
        $wallet = $user->cryptoWallets()
            ->where('crypto_currency_code', $order->base_currency)
            ->firstOrCreate();

        $totalCost = $order->amount * $order->price + $order->fee;
        $account = $user->accounts()->where('currency', $order->quote_currency)->first();

        if ($account->balance < $totalCost) {
            throw new InsufficientFundsException();
        }

        $account->decrement('balance', $totalCost);
        $wallet->increment('balance', $order->amount);
        $order->update(['status' => 'completed']);

        TransactionCreated::dispatch(/* ... */);
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . time() . '-' . random_int(1000, 9999);
    }
}
```

---

## 24. Advanced Operations & DevOps

### 24.1 Backup & Disaster Recovery

**Automated Database Backups:**
```bash
# Laravel Backup package
composer require spatie/laravel-backup

# Schedule in Kernel.php
$schedule->command('backup:run')->daily()->at('02:00');
$schedule->command('backup:monitor')->hourly();
```

**Manual Backup:**
```bash
# Full backup
php artisan backup:run

# Backup specific disk
php artisan backup:run --only-db

# Restore from backup
php artisan backup:restore
```

### 24.2 Docker Containerization

**Dockerfile:**
```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    postgresql-client \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/typhoon

COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install

COPY . .

CMD ["php-fpm"]
```

**Docker Compose:**
```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: typhoon-app
    volumes:
      - .:/var/www/typhoon
    depends_on:
      - db
      - redis

  db:
    image: postgres:15
    container_name: typhoon-db
    environment:
      POSTGRES_DB: typhoon
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    container_name: typhoon-redis

  nginx:
    image: nginx:alpine
    container_name: typhoon-nginx
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/typhoon
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

volumes:
  postgres_data:
```

**Running Docker:**
```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### 24.3 CI/CD Pipeline (GitHub Actions)

**.github/workflows/tests.yml:**
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: typhoon_test
          POSTGRES_PASSWORD: secret
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_pgsql, redis
      
      - name: Install Dependencies
        run: composer install --no-interaction
      
      - name: Create .env
        run: cp .env.example .env && php artisan key:generate
      
      - name: Run Tests
        run: php artisan test
        env:
          DB_HOST: localhost
          DB_DATABASE: typhoon_test
```

### 24.4 Monitoring & Observability

**Application Performance Monitoring (APM):**
```php
// config/monitoring.php
return [
    'apm_driver' => env('APM_DRIVER', 'null'),  // newrelic, datadog, elastic

    'newrelic' => [
        'app_name' => env('NEWRELIC_APP_NAME'),
        'license_key' => env('NEWRELIC_LICENSE_KEY'),
    ],

    'datadog' => [
        'api_key' => env('DATADOG_API_KEY'),
        'app_key' => env('DATADOG_APP_KEY'),
        'site' => 'datadoghq.com',
    ],
];
```

**Health Check Endpoint:**
```php
// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() ? 'ok' : 'fail',
        'redis' => Cache::store('redis')->get('test') === false ? 'fail' : 'ok',
        'queue' => queue_jobs_pending() < 100 ? 'ok' : 'warning',
    ];

    return response()->json($checks, 200);
});

// Kubernetes liveness probe
livenessProbe:
  httpGet:
    path: /health
    port: 8000
  initialDelaySeconds: 30
  periodSeconds: 10
```

### 24.5 Scaling Considerations

**Horizontal Scaling (Load Balancing):**
```bash
# Use sticky sessions for Inertia
Session::put('FORCE_SESSION_SAVE', true);
```

**Database Read Replicas:**
```php
// config/database.php
'connections' => [
    'pgsql' => [
        'driver' => 'pgsql',
        'write' => ['host' => 'primary.db.local'],
        'read' => [
            ['host' => 'replica1.db.local'],
            ['host' => 'replica2.db.local'],
        ],
    ],
];
```

**Cache Warm-up:**
```php
// Artisan command
php artisan cache:warmup

// Implementation
public function handle()
{
    Cache::remember('exchange_rates', 3600, fn() => ExchangeRate::all());
    Cache::remember('fee_schedules', 3600, fn() => FeeSchedule::all());
    Cache::remember('currencies', 3600, fn() => CryptoCurrency::all());
}
```

---

## 25. Frontend Architecture

### 25.1 Component Folder Structure

```
resources/js/
├── components/
│   ├── ui/                    # Radix UI + Tailwind wrapped components
│   │   ├── button.tsx
│   │   ├── card.tsx
│   │   ├── input.tsx
│   │   ├── select.tsx
│   │   ├── dialog.tsx
│   │   ├── table.tsx
│   │   └── spinner.tsx
│   ├── layout/                # Layout wrappers
│   │   ├── app-layout.tsx
│   │   ├── auth-layout.tsx
│   │   ├── app-header.tsx
│   │   └── app-sidebar.tsx
│   ├── forms/                 # Form components
│   │   ├── transfer-form.tsx
│   │   ├── kyc-form.tsx
│   │   └── account-form.tsx
│   ├── tables/                # Data tables
│   │   ├── transactions-table.tsx
│   │   └── accounts-table.tsx
│   └── shared/                # Reusable utilities
│       ├── empty-state.tsx
│       └── breadcrumbs.tsx
├── pages/
│   ├── banking/
│   │   ├── dashboard.tsx
│   │   ├── accounts.tsx
│   │   └── crypto.tsx
│   ├── admin/
│   │   └── dashboard.tsx
│   └── welcome.tsx
├── hooks/
│   ├── use-auth.ts
│   ├── use-balance.ts
│   ├── use-transaction-updates.ts
│   └── use-pagination.ts
├── lib/
│   ├── utils.ts               # Utility functions
│   ├── api-client.ts          # API wrapper
│   └── constants.ts
└── app.tsx                    # Root app component
```

### 25.2 State Management Patterns

**Custom Hook for Auth:**
```tsx
// resources/js/hooks/use-auth.ts
import { usePage } from '@inertiajs/react';

export function useAuth() {
    const { auth } = usePage().props;
    
    return {
        user: auth?.user,
        isAuthenticated: !!auth?.user,
        isAdmin: auth?.user?.roles?.includes('admin'),
        can: (permission: string) => 
            auth?.user?.permissions?.includes(permission),
    };
}

// Usage
function AdminPanel() {
    const { isAdmin, can } = useAuth();
    
    if (!isAdmin) return null;
    
    return (
        <>
            {can('kyc:approve') && <KycButton />}
        </>
    );
}
```

**Form State Management:**
```tsx
// Using Inertia's useForm
import { useForm } from '@inertiajs/react';

export function TransferForm() {
    const { data, setData, post, processing } = useForm({
        from_account_id: '',
        to_account_id: '',
        amount: '',
        description: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/banking/transfer');
    };

    return (
        <form onSubmit={submit}>
            <input
                value={data.from_account_id}
                onChange={(e) => setData('from_account_id', e.target.value)}
            />
            <button disabled={processing}>Transfer</button>
        </form>
    );
}
```

### 25.3 Error Boundary

```tsx
// resources/js/components/error-boundary.tsx
import { Component, ReactNode } from 'react';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    error?: Error;
}

export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error) {
        console.error('Error caught:', error);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="p-4 text-red-600">
                    <h2>Something went wrong</h2>
                    <p>{this.state.error?.message}</p>
                </div>
            );
        }

        return this.props.children;
    }
}
```

---

## 26. Domain-Specific Implementation Details

### 26.1 IBAN Generation per Country

**IBAN Structure:**
```php
// app/Services/IbanService.php
class IbanService
{
    private const IBAN_LENGTHS = [
        'DE' => 22, 'FR' => 27, 'ES' => 24, 'IT' => 27,
        'NL' => 18, 'BE' => 16, 'CH' => 21, 'AT' => 20,
    ];

    public function generate(string $countryCode, int $accountNumber): string
    {
        $length = self::IBAN_LENGTHS[$countryCode] ?? null;
        if (!$length) throw new InvalidCountryException();

        $checksum = $this->calculateChecksum($countryCode);
        $bban = $this->generateBban($countryCode, $accountNumber);

        return $countryCode . $checksum . $bban;
    }

    private function calculateChecksum(string $countryCode): string
    {
        $numeric = substr($countryCode, 0, 2);
        $rearranged = substr($numeric, 2) . $numeric . '00';
        
        $mod = 0;
        foreach (str_split($rearranged) as $digit) {
            $mod = ($mod * 10 + (int)$digit) % 97;
        }

        return str_pad(98 - $mod, 2, '0', STR_PAD_LEFT);
    }

    private function generateBban(string $country, int $accountNumber): string
    {
        return match($country) {
            'DE' => '80070024' . str_pad($accountNumber, 14, '0', STR_PAD_LEFT),
            'FR' => '20041010050500013M02606' . str_pad($accountNumber, 2, '0', STR_PAD_LEFT),
            // ... other countries
        };
    }
}
```

### 26.2 Fee Calculation Engine

**Tiered Fee Logic:**
```php
// app/Services/FeeService.php
class FeeService
{
    public function calculateFee(string $type, float $amount, User $user = null): float
    {
        $schedule = FeeSchedule::where('fee_type', $type)->first();

        if (!$schedule) return 0;

        return match($schedule->calculation_method) {
            'fixed' => $schedule->fee_value,
            'percentage' => ($amount * $schedule->fee_value) / 100,
            'tiered' => $this->calculateTieredFee($amount, $schedule->tiers),
            'progressive' => $this->calculateProgressiveFee($amount, $user, $schedule),
        };
    }

    private function calculateTieredFee(float $amount, array $tiers): float
    {
        foreach ($tiers as $tier) {
            if ($amount >= $tier['min'] && $amount <= $tier['max']) {
                return ($amount * $tier['rate']) / 100;
            }
        }
        return 0;
    }

    private function calculateProgressiveFee(float $amount, User $user, FeeSchedule $schedule): float
    {
        $monthlyVolume = $user->transactions()
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        if ($monthlyVolume > $schedule->volume_threshold) {
            return ($amount * $schedule->premium_rate) / 100;
        }

        return ($amount * $schedule->standard_rate) / 100;
    }
}
```

### 26.3 Compliance Rule Engine

**Rule Matching:**
```php
// app/Services/ComplianceEngine.php
class ComplianceEngine
{
    public function evaluateTransaction(Transaction $transaction): array
    {
        $alerts = [];
        $rules = MonitoringRule::where('active', true)->get();

        foreach ($rules as $rule) {
            if ($this->matchesRule($transaction, $rule)) {
                $alerts[] = MonitoringAlert::create([
                    'monitoring_rule_id' => $rule->id,
                    'transaction_id' => $transaction->id,
                    'user_id' => $transaction->user_id,
                    'severity' => $rule->severity,
                    'status' => 'open',
                ]);
            }
        }

        return $alerts;
    }

    private function matchesRule(Transaction $transaction, MonitoringRule $rule): bool
    {
        $conditions = json_decode($rule->conditions, true);

        foreach ($conditions as $field => $operator => $value) {
            $txValue = data_get($transaction, $field);

            if (!$this->evaluateCondition($txValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition($value, string $operator, $expected): bool
    {
        return match($operator) {
            '=' => $value === $expected,
            '>' => $value > $expected,
            '<' => $value < $expected,
            'in' => in_array($value, (array)$expected),
            'contains' => str_contains($value, $expected),
        };
    }
}

// Example rule structure
$rule = MonitoringRule::create([
    'name' => 'Unusual Transfer Amount',
    'conditions' => json_encode([
        'amount' => ['>' => 100000],
        'type' => ['in' => ['sepa', 'swift']],
    ]),
    'severity' => 'high',
]);
```

### 26.4 Wallet Seed Phrase Management

**Secure Storage:**
```php
// app/Services/BlockchainService.php
class BlockchainService
{
    public function generateAddress(): array
    {
        // Generate keypair using web3.php
        $account = $this->web3->personal()->newAccount('password');

        // Encrypt private key before storing
        $encrypted = Crypt::encryptString($account['privateKey']);

        // Store separately from address
        $wallet = CryptoWallet::create([
            'address' => $account['address'],
            'encrypted_private_key' => $encrypted,
            'created_at' => now(),
        ]);

        return [
            'address' => $account['address'],
            'privateKey' => $account['privateKey'],  // Return only once to user
        ];
    }

    public function sendTransaction(string $toAddress, string $amount): string
    {
        // Decrypt private key only when needed
        $account = Auth::user()->cryptoWallet;
        $privateKey = Crypt::decryptString($account->encrypted_private_key);

        // Sign and send transaction
        $txHash = $this->web3->eth()->sendTransaction([
            'from' => $account->address,
            'to' => $toAddress,
            'value' => $this->web3->utils()->toWei($amount, 'ether'),
            'gas' => 21000,
            'gasPrice' => $this->web3->eth()->gasPrice(),
        ], $privateKey);

        return $txHash;
    }
}
```

### 26.5 Loan Underwriting Workflow

**Multi-Stage Approval:**
```php
// app/Services/LoanService.php
class LoanService
{
    public function apply(User $user, array $data): Loan
    {
        $score = $this->calculateCreditScore($user);
        $amount = $data['amount'];
        $autoApprove = $score > 700 && $amount < 50000;

        $loan = Loan::create([
            'user_id' => $user->id,
            'account_id' => $data['account_id'],
            'loan_number' => 'LOAN-' . time(),
            'amount' => $amount,
            'interest_rate' => $this->getInterestRate($score),
            'term_months' => $data['term_months'],
            'status' => $autoApprove ? 'approved' : 'pending_review',
            'credit_score' => $score,
        ]);

        if ($autoApprove) {
            $this->disburse($loan);
        }

        return $loan;
    }

    private function calculateCreditScore(User $user): int
    {
        $score = 500;  // Base score

        // Payment history (30%)
        $missed = $user->loanRepayments()
            ->where('status', 'defaulted')
            ->count();
        $score -= $missed * 50;

        // Account age (10%)
        $months = $user->created_at->diffInMonths(now());
        $score += min($months, 60);

        // KYC level (20%)
        $score += ($user->kyc_level * 100);

        // Transaction volume (20%)
        $volume = $user->transactions()->sum('amount');
        $score += min($volume / 1000, 200);

        return max(0, min(850, $score));
    }

    private function getInterestRate(int $creditScore): float
    {
        return match(true) {
            $creditScore >= 750 => 3.5,
            $creditScore >= 700 => 5.0,
            $creditScore >= 650 => 7.5,
            $creditScore >= 600 => 10.0,
            default => 15.0,
        };
    }
}
```

---

## 27. Operations & Maintenance

### 27.1 Log Rotation & Cleanup

**Configuration** (`.env`):
```
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_DAILY_DAYS=30
```

**Automated Cleanup:**
```bash
# Schedule in Kernel.php
$schedule->command('logs:clear')->monthly();
$schedule->command('cache:clear')->daily();
$schedule->command('queue:flush')->daily();
```

### 27.2 Database Backup Scheduling

**Using Spatie Backup:**
```bash
# Schedule in Kernel.php
$schedule->command('backup:run')->daily()->at('02:00');
$schedule->command('backup:clean')->daily()->at('03:00');
$schedule->command('backup:monitor')->everyFiveMinutes();
```

**Monitoring Backups:**
```php
// config/backup.php
return [
    'backup' => [
        'source' => [
            'databases' => ['pgsql'],
            'files' => ['storage/app/kyc-documents'],
        ],
        'destination' => [
            'disks' => ['s3-backup', 'local-backup'],
        ],
    ],

    'cleanup' => [
        'defaultStrategy' => 'deleteOldestBackups',
        'strategies' => [
            'deleteOldestBackups' => [
                'deleteWhenUsingMoreThanGigabytes' => 100,
            ],
        ],
    ],

    'notifications' => [
        'notifications' => [
            Notification::class,
        ],
        'notificationChannels' => ['mail', 'slack'],
        'events' => [
            'backupHasFailed' => true,
            'unhealthyBackupWasFound' => true,
            'backupWasSuccessful' => false,
        ],
    ],
];
```

### 27.3 Session & Token Cleanup

```php
// Artisan command
php artisan session:cleanup
php artisan sanctum:prune-expired

// Schedule
$schedule->command('session:cleanup')->daily();
$schedule->command('sanctum:prune-expired --hours=24')->daily();
```

### 27.4 Cache Warming

```php
// app/Console/Commands/WarmCache.php
class WarmCache extends Command
{
    public function handle()
    {
        // Warm exchange rates
        Cache::remember('exchange_rates', 3600, fn() => 
            ExchangeRate::latest('last_refreshed_at')->get()
        );

        // Warm fee schedules
        Cache::remember('fee_schedules', 86400, fn() =>
            FeeSchedule::where('active', true)->get()
        );

        // Warm platform settings
        Cache::remember('platform_settings', 86400, fn() =>
            PlatformSetting::all()
        );

        $this->info('Cache warmed successfully');
    }
}

// Schedule
$schedule->command('cache:warm')->hourly();
$schedule->command('cache:warm')->dailyAt('00:00');
```

### 27.5 Performance Metrics & Alerts

**Key Metrics to Monitor:**
```
- Request latency (p50, p95, p99)
- Error rate (4xx, 5xx)
- Database query time
- Cache hit rate
- Queue backlog size
- Active transactions/min
- API response time
```

**Alert Thresholds:**
```
- Error rate > 1% → Critical
- Request latency > 2s (p95) → Warning
- Queue backlog > 1000 jobs → Warning
- Database connections > 80% → Warning
- Disk usage > 85% → Warning
```

---

## Complete Development Checklist

### Before Deploying to Production:
- [ ] All tests pass: `php artisan test`
- [ ] Code style fixed: `./vendor/bin/pint`
- [ ] Linting clean: `npm run lint`
- [ ] Environment variables set
- [ ] Database migrations run
- [ ] Cache cleared: `php artisan cache:clear`
- [ ] Assets built: `npm run build`
- [ ] Queue worker configured (supervisor)
- [ ] Scheduler cron job configured
- [ ] HTTPS certificates installed
- [ ] Backup strategy in place
- [ ] Monitoring/alerting configured
- [ ] Documentation updated

---

## Version History

| Version | Date | Notes |
|---|---|---|
| 1.0 | May 2026 | Initial MVP release |
| 1.1 | Jun 2026 | Enhanced auth, API improvements |
| 1.2 | Jul 2026 | Crypto integration complete, AI monitoring |

---

**Last Updated:** May 21, 2026  
**Maintainer:** Typhoon Banking Dev Team  
**License:** Proprietary — All Rights Reserved
