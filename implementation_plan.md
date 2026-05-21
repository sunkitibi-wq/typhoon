# Implementation Plan — Integration Layer Completion

This plan outlines the steps required to transition the Typhoon Banking Platform from a local/simulated sandbox to an integrated, production-ready system. We will address all four core items requested:
1. **BaaS Client Integration**: Connect SEPA and SWIFT operations to a simulated/configurable external provider.
2. **Webhook Event Consumer Queue Job**: Process incoming webhook queue payloads.
3. **Real Blockchain RPC Settings**: Enable toggling to live Infura/Alchemy nodes.
4. **Transaction Router (M4)**: Build automated payment routing logic based on IBAN, currency, and compliance checks.

---

## User Review Required

> [!IMPORTANT]
> **BaaS Provider Choice**
> We will implement a generic BaaS client wrapper supporting both **Solarisbank** and **Treasury Prime** JSON API payload mappings. By default, it will fall back to a mock mode when credentials are not configured in `.env`, ensuring tests run green in the sandbox.

> [!WARNING]
> **Webhook Event Safety**
> Webhook ingestion currently writes requests to the `webhook_events` database table. The queue worker will query and update database states. If a transaction has already been completed locally, the consumer will flag it as `ignored` to prevent double-crediting.

---

## Open Questions

> [!NOTE]
> **Dynamic Routing Limits**
> For the Transaction Router (M4), should we enforce hard limits on SWIFT routing? (e.g., automatically reject/force SWIFT for any transfer exceeding €50,000 to non-EEA accounts, or fallback to SEPA for EEA accounts when EUR is selected). We will implement a standard rule-set by default unless specified otherwise.

---

## Proposed Changes

### 1. Configuration & Dependency Layer

#### [MODIFY] [services.php](file:///c:/Users/led/Herd/example-app/config/services.php)
*   Add configuration endpoints, API keys, and client secrets for `baas_provider` (Solarisbank / Treasury Prime keys).

---

### 2. Services Layer

#### [NEW] [BaasService.php](file:///c:/Users/led/Herd/example-app/app/Services/BaasService.php)
*   Create a unified BaaS wrapper utilizing Laravel's `Http` facade.
*   Implement methods:
    *   `initiateCreditTransfer(array $params)`
    *   `initiateDirectDebit(array $params)`
    *   `fetchTransferStatus(string $externalId)`
*   Support a default simulated/mock handler that writes to logs if `BAAS_PROVIDER_KEY` is not set.

#### [MODIFY] [SepaService.php](file:///c:/Users/led/Herd/example-app/app/Services/SepaService.php)
*   Inject [BaasService](file:///c:/Users/led/Herd/example-app/app/Services/BaasService.php) into the constructor.
*   Modify `createCreditTransfer()` and `createDirectDebit()` to call `BaasService` for external payments, updating the local database status to `pending` (waiting for webhook/updater) rather than immediately `completed`.

#### [MODIFY] [SwiftService.php](file:///c:/Users/led/Herd/example-app/app/Services/SwiftService.php)
*   Inject [BaasService](file:///c:/Users/led/Herd/example-app/app/Services/BaasService.php) into the constructor.
*   Refactor `createInternationalTransfer()` to route through `BaasService` and return MT103 reference tokens.

#### [NEW] [TransactionRouter.php](file:///c:/Users/led/Herd/example-app/app/Services/TransactionRouter.php)
*   Expose `route(Account $debitAccount, array $paymentDetails, User $user)` to dynamically route transactions based on details:
    *   If the creditor IBAN exists locally in `accounts` table: route as **Internal Transfer**.
    *   If the creditor IBAN is foreign but within EEA (starts with valid SEPA country code) and currency is EUR: route as **SEPA Credit Transfer**.
    *   Otherwise: route as **SWIFT Wire Transfer**.
*   Automatically calculates the corresponding fee using `TransactionService->calculateFee()` based on the decided channel and applies compliance screening.

---

### 3. Background Job / Queue Worker

#### [NEW] [ProcessWebhookEvent.php](file:///c:/Users/led/Herd/example-app/app/Jobs/ProcessWebhookEvent.php)
*   Implement a standard queueable job (`ShouldQueue`) that handles parsing of `WebhookEvent` models.
*   Resolves event types:
    *   `transfer.completed` / `transfer.failed`: Updates corresponding local `SepaTransfer` or `SwiftTransfer` statuses, reconciles balances if failed.
    *   `compliance.alert`: Automatically raises a compliance alert in `monitoring_alerts` table.
*   Sets status of `WebhookEvent` to `completed` or `failed` with error logs.

---

### 4. Controller Integration

#### [MODIFY] [BankingController.php](file:///c:/Users/led/Herd/example-app/app/Http/Controllers/BankingController.php)
*   Refactor the `transfer` controller method to leverage the new [TransactionRouter](file:///c:/Users/led/Herd/example-app/app/Services/TransactionRouter.php) class, allowing a single unified transfer form in the UI to dynamically dispatch the payment under the hood.

---

## Verification Plan

### Automated Tests
*   Run the new feature test suite targeting the BaaS clients and the router:
    ```bash
    php artisan test --filter=BaasAndRouterTest
    ```
*   Ensure the existing 102/102 test suite remains completely green:
    ```bash
    php artisan test
    ```

### Manual Verification
*   We will verify transaction routing rules inside the local sqlite shell or web client by typing in internal, SEPA, and international SWIFT IBANs and ensuring the transaction gets dispatched to the correct service.
