# Product Requirements Document (PRD)
## Typhoon Banking Platform – Version 1.0 (MVP)

---

### 1. Executive Summary

**Product Name:** Typhoon Banking Platform  
**Target Release:** MVP – Q4 2026  
**Technology Stack:** Laravel 13, PHP 8.3+, Mysqls, Redis, Laravel Reverb, livewire 4+ 
**Document Owner:** Product Team  

**Vision:**  
To build a secure, scalable, and compliant digital banking platform that offers traditional banking services (accounts, SEPA, SWIFT, IBAN) alongside cryptocurrency exchange capabilities, targeting businesses and individuals who need a unified fiat-crypto financial hub.

---

### 2. Target Users & Personas

| Persona | Description | Key Needs |
|---------|-------------|------------|
| **Retail Customer** | Individual managing personal finances | Fiat accounts, crypto buying/selling, simple transfers, KYC onboarding |
| **Corporate Treasurer** | Business managing payroll, supplier payments | Bulk SEPA/SWIFT, multi-user roles, audit logs, API access |
| **Crypto Trader** | Active trader | Real-time prices, order book (if CEX), crypto deposits/withdrawals |
| **Platform Admin** | Internal operations | User/account management, transaction monitoring, fee configuration, compliance reports |
| **Compliance Officer** | AML/KYC oversight | KYC verification, transaction monitoring, suspicious activity reports (SARs) |

---

### 3. Product Architecture Overview

The platform will follow a **modular monolith** approach (deployable as single Laravel app, with clear domain modules) to simplify initial development, with the ability to extract microservices later (e.g., matching engine, crypto hot wallet).

---

### 4. Functional Requirements

#### 4.1 Middleware & Core Services (from diagram)

| ID | Feature | Description | Priority |
|----|---------|-------------|----------|
| **M1** | Authentication | Passkey (biometric), MFA (TOTP/SMS), session management. Laravel 13 native Passkey + Laravel Fortify. | P0 |
| **M2** | Role & Permissions | RBAC using Spatie. Support for admin, compliance, user, auditor. | P0 |
| **M3** | Audit Logging | Immutable log of all user and system actions. Includes IP, user agent, timestamp, before/after state. | P0 |
| **M4** | Transaction Router | Intelligent routing of payments based on cost, availability, and compliance rules (e.g., domestic SEPA vs SWIFT). | P1 |
| **M5** | Webhook Receiver | Secure endpoint for receiving asynchronous callbacks from payment processors, crypto networks, SWIFT gateways. Idempotent processing. | P1 |
| **M6** | Transaction Status Updater | Background job (Laravel Queues) that polls external APIs and updates transaction statuses. Exponential backoff + dead-letter queue. | P0 |
| **M7** | API Connectors (EBANQ, Processor) | Unified HTTP client with retry, circuit breaker, and authentication handling for connecting to EBANQ API and underlying payment processors. | P0 |

#### 4.2 Integration Layer (from diagram)

| ID | Feature | Description | Priority |
|----|---------|-------------|----------|
| **I1** | SWIFT MT103 API | Support outgoing SWIFT MT103 messages via processor (Banking as a Service API). Store message references. | P1 |
| **I2** | GPU Tracking | *Clarified as “Payment Tracking”* – real-time status of fiat/crypto payments across networks, with notification to users. | P1 |
| **I3** | IBAN Transfer API | Internal IBAN-to-IBAN transfers within platform; also support external SEPA/Credit Transfer via processor. | P0 |
| **I4** | SEPA Direct Debit API | Core SEPA Direct Debit (SDD) creditor and debtor functionality: mandate management, collections, refunds. | P1 |
| **I5** | POS Gateway API | Integration with point-of-sale terminals (e.g., Stripe Terminal, Adyen) for card-present transactions. | P2 |
| **I6** | CyberSource / Visa API | Direct card-not-present (ecommerce) processing via CyberSource (Visa). | P1 |
| **I7** | Crypto Bridge API | Fiat ↔ Crypto on/off-ramp via third-party (e.g., Linkio, MoonPay, Transak). Includes deposit/withdrawal of crypto assets to user wallets. | P0 (for crypto MVP) |

#### 4.3 Core Banking Features (EBANQ-like)

| ID | Feature | Description | Priority |
|----|---------|-------------|----------|
| **B1** | User Onboarding & KYC | Multi-step registration with document upload (selfie, ID, proof of address). Integration with identity verification provider (e.g., Sumsub, Onfido). | P0 |
| **B2** | Multi-currency Accounts | EUR, USD, GBP, BTC, ETH, USDC etc. Each user gets virtual IBAN (for fiat) and blockchain addresses (for crypto). | P0 |
| **B3** | Internal Transfers | Instant, no-fee transfers between two Typhoon accounts. | P0 |
| **B4** | External SEPA/IBAN Transfers | Single and batch (bulk) payments. Compliance checks (sanctions, AML). | P0 |
| **B5** | Transaction History & Statements | Search/filter by date, amount, status. Download PDF statements (ISO 20022 optional). | P0 |
| **B6** | Fee Engine | Configurable fee rules (percentage, fixed, minimum/maximum) per transaction type and user tier. | P1 |
| **B7** | Notifications | Email, SMS, in-app (WebSocket via Reverb) for transaction confirmations, approvals, and crypto deposit confirmations. | P0 |

#### 4.4 Crypto Exchange Features

| ID | Feature | Description | Priority |
|----|---------|-------------|----------|
| **C1** | Crypto Wallet Management | Generate deposit addresses (HD wallet) for Bitcoin, Ethereum, etc. Hot wallet for liquidity; cold storage for reserves. | P0 |
| **C2** | Real-time Price Feeds | Aggregated prices from Binance, Coinbase, Kraken (using CCXT or unified price feed library). Cache with Redis. | P0 |
| **C3** | Spot Trading (Order Book) | Basic limit/market orders. Matching engine runs as separate service (or Laravel Job with atomic locks). | P1 (simplified) |
| **C4** | Fiat-to-Crypto Buy/Sell | User can buy crypto using fiat balance (via CyberSource or crypto bridge provider). Sell crypto to fiat. | P0 |
| **C5** | Crypto Withdrawals (on-chain) | User sends crypto to external address; platform signs transaction from hot wallet, broadcasts to blockchain. | P0 |
| **C6** | Crypto Deposits Tracking | Poll blockchain nodes (or use webhooks from providers) to credit user’s crypto balance after sufficient confirmations. | P0 |
| **C7** | Exchange Fee Structure | Maker/taker fees, withdrawal network fees configurable per asset. | P1 |

---

### 5. Non-Functional Requirements

| Category | Requirement |
|----------|-------------|
| **Security** | All sensitive data encrypted (AES-256) in database; private keys stored in HSM or KMS; TLS 1.3 only; force MFA for admin. |
| **Compliance** | GDPR (right to erasure, data export), PSD2 SCA for EU payments, AML/KYC records retention (5+ years). |
| **Performance** | API response time <200ms for 95% of requests (non-transactional); transaction processing <2s from initiation to confirmation. |
| **Availability** | 99.95% uptime for core banking; maintenance windows communicated 14 days in advance. |
| **Auditability** | Every financial operation must be recorded in append-only ledger (event sourcing recommended for balances). |
| **Scalability** | Horizontal scaling: stateless Laravel app, separate queue workers, read replicas for reporting. |
| **Backup & DR** | Daily encrypted backups (DB + storage). RTO <4 hours, RPO <15 minutes. |

---

### 6. User Stories (Selected Examples)

1. **As a Retail Customer**, I want to open an account via mobile device, upload my ID, and get verified within 15 minutes.  
2. **As a Crypto Trader**, I want to see real-time BTC/EUR price and place a market buy order using my fiat balance.  
3. **As an Admin**, I want to approve a suspicious SEPA transfer and block the user’s account pending investigation.  
4. **As a Developer**, I want to receive a webhook when a crypto deposit reaches 3 confirmations to update user balance.  
5. **As a Compliance Officer**, I want to run a report of all transactions above €10,000 in the last 30 days.

---

### 7. Integration & External Dependencies

| Integration | Purpose | Example Vendor |
|-------------|---------|----------------|
| BaaS Provider | SWIFT, SEPA, IBAN accounts, direct debits | Solarisbank, Railsbank, Treasury Prime |
| KYC/AML Provider | Identity verification, sanctions screening | Sumsub, Onfido, ComplyAdvantage |
| Crypto Bridge | Fiat-crypto on/off ramp | Linkio, MoonPay, Transak |
| Blockchain Nodes | Broadcast/read BTC, ETH, etc. | Infura (ETH), QuickNode, self-hosted |
| Price Feed API | Real-time crypto prices | CCXT (Binance, Kraken) |
| Payment Gateway | Card payments (ecommerce) | CyberSource, Stripe |
| Cloud Infrastructure | Hosting, KMS, CDN | AWS (EKS/EC2, KMS, CloudFront) |

---

### 8. Out of Scope for MVP

- Decentralized finance (DeFi) staking or lending
- Cryptocurrency derivatives (futures, options)
- Credit/debit card issuing (physical/virtual)
- Stock or commodity trading
- Mobile native app (responsive web only)
- Multi-language support (English only)

---

### 9. Phased Roadmap

| Phase | Duration | Key Deliverables |
|-------|----------|------------------|
| **Phase 0: Foundation** | 4 weeks | Laravel 13 setup, authentication (Passkey), RBAC, audit logging, basic user onboarding. |
| **Phase 1: Core Banking** | 8 weeks | Multi-currency accounts, internal transfers, external SEPA/IBAN via BaaS, transaction history, admin dashboard. |
| **Phase 2: Crypto Exchange MVP** | 8 weeks | Crypto wallets, price feeds, fiat/crypto buy/sell via bridge, crypto deposit/withdrawal tracking. |
| **Phase 3: Integrations** | 6 weeks | Webhook receiver, transaction router, CyberSource, SWIFT, SEPA Direct Debit, POS Gateway. |
| **Phase 4: Production Hardening** | 4 weeks | Security audit, load testing, compliance documentation, deployment pipeline, monitoring. |

---

### 10. Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|-------------|
| Regulatory non-compliance (MiCA, PSD2) | High | Critical | Engage legal counsel early; limit MVP to jurisdictions with clear frameworks. |
| Crypto withdrawal key compromise | Low | Critical | Use HSM/multi-sig; separate hot/cold wallets; mandatory admin approval for large withdrawals. |
| BaaS provider API downtime | Medium | High | Multi-provider fallback via transaction router; cache static data where possible. |
| High gas fees or blockchain congestion | Medium | Medium | Allow user-configurable gas limit; implement fee estimation; queue withdrawals. |
| Smart contract risk (if using bridge) | Medium | High | Audit third-party bridge contracts; use established providers with insurance. |

---

### 11. Success Metrics (KPIs)

| Metric | Target (6 months post-MVP) |
|--------|-----------------------------|
| User signups | 5,000+ |
| Daily active users | >500 |
| Total transaction volume (fiat) | €10M |
| Crypto exchange volume | €2M |
| Average KYC verification time | <10 minutes |
| Platform uptime | 99.95% |
| Support tickets per 1k users | <20/week |

---

### 12. Appendices

- **Glossary:** SEPA, SWIFT, IBAN, BaaS, HSM, PSD2, MiCA, KYC, AML, MT103, SDD, POS  
- **Reference:** EBANQ API documentation (if available), CCXT library docs, Laravel 13 release notes  
- **Diagram reference:** *Attached middleware/integration layer diagram (user provided)*

---

**Approvals:**

- Product Manager: `_________________`  
- Engineering Lead: `_________________`  
- Compliance Officer: `_________________`  

**Document Version:** 1.0  
**Last Updated:** 2026-05-16