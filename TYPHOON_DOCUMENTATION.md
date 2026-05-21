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
