<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\Transaction;
use App\Models\Banking\WebhookEvent;
use App\Models\Banking\MonitoringAlert;
use App\Models\Role;
use App\Models\User;
use App\Services\TransactionRouter;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BaasAndRouterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $fromAccount;
    private Account $toAccount;
    private TransactionRouter $router;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);

        $type = AccountType::create([
            'code' => 'personal',
            'name' => 'Personal Account',
            'currency' => 'EUR',
            'minimum_balance' => 0,
            'monthly_fee' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->role()->associate(Role::where('name', 'user')->first());
        $this->user->save();

        $this->fromAccount = $this->user->accounts()->create([
            'account_type_id' => $type->id,
            'account_number' => 'TY0000001001',
            'iban' => 'DE12345678901234567890',
            'currency' => 'EUR',
            'balance' => 10000,
            'available_balance' => 10000,
            'ledger_balance' => 10000,
            'status' => 'active',
            'label' => 'From Account',
            'is_default' => true,
        ]);

        $this->toAccount = $this->user->accounts()->create([
            'account_type_id' => $type->id,
            'account_number' => 'TY0000001002',
            'iban' => 'DE09876543210987654321',
            'currency' => 'EUR',
            'balance' => 500,
            'available_balance' => 500,
            'ledger_balance' => 500,
            'status' => 'active',
            'label' => 'To Account',
            'is_default' => false,
        ]);

        $this->router = app(TransactionRouter::class);
    }

    public function test_router_routes_internal_transfer_successfully()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('banking.transfer'), [
            'from_account_id' => $this->fromAccount->id,
            'to_account_id' => $this->toAccount->id,
            'amount' => 1000,
            'description' => 'Internal Router Test',
        ]);

        $response->assertRedirect(route('banking.transactions'));

        $this->fromAccount->refresh();
        $this->toAccount->refresh();

        $this->assertEquals(9000, $this->fromAccount->balance);
        $this->assertEquals(1500, $this->toAccount->balance);
    }

    public function test_router_routes_sepa_transfer_external_successfully()
    {
        $details = [
            'beneficiary_iban' => 'FR7630006000011234567890123', // French SEPA IBAN
            'beneficiary_name' => 'Jean Dupont',
            'beneficiary_bic' => 'BNPAFRPPXXX',
            'amount' => 1500,
            'currency' => 'EUR',
            'description' => 'SEPA Router Test',
        ];

        $sepaTransfer = $this->router->route($this->user, $this->fromAccount, $details);

        $this->assertInstanceOf(\App\Models\Banking\SepaTransfer::class, $sepaTransfer);
        $transaction = $sepaTransfer->transaction;
        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals(1500, $transaction->amount);
        $this->assertNotEmpty($transaction->metadata['baas_external_id']);

        $this->fromAccount->refresh();
        // Since it's a withdraw, balance should be deducted (10000 - 1500) = 8500
        $this->assertEquals(8500, $this->fromAccount->balance);
    }

    public function test_router_routes_swift_transfer_external_successfully()
    {
        $details = [
            'beneficiary_account' => 'US1234567890', // Non-SEPA Account
            'beneficiary_name' => 'John Doe',
            'beneficiary_bic' => 'CHASEUS33XXX',
            'beneficiary_bank_name' => 'Chase Bank',
            'amount' => 2000,
            'currency' => 'USD', // Non-EUR
            'description' => 'SWIFT Router Test',
        ];

        $transaction = $this->router->route($this->user, $this->fromAccount, $details);

        // SWIFT transfers return SwiftTransfer model from SwiftService
        $this->assertInstanceOf(\App\Models\Banking\SwiftTransfer::class, $transaction);
        
        $tx = $transaction->transaction;
        $this->assertEquals('pending', $tx->status);
        $this->assertEquals(2000, $tx->amount);
        $this->assertNotEmpty($tx->metadata['baas_external_id']);

        $this->fromAccount->refresh();
        $this->assertEquals(8000, $this->fromAccount->balance);
    }

    public function test_webhook_event_job_processes_completed_payment()
    {
        $tx = Transaction::create([
            'reference' => 'TX-TESTCOMP',
            'type' => 'sepa',
            'status' => 'pending',
            'debit_account_id' => $this->fromAccount->id,
            'user_id' => $this->user->id,
            'amount' => 1000,
            'fee' => 0,
            'net_amount' => 1000,
            'currency' => 'EUR',
            'description' => 'SEPA Pending Tx',
            'metadata' => ['baas_external_id' => 'external_sepa_123']
        ]);

        $webhookEvent = WebhookEvent::create([
            'event_type' => 'transfer',
            'source' => 'solarís',
            'payload' => [
                'external_id' => 'external_sepa_123',
                'status' => 'completed',
            ],
            'status' => 'pending'
        ]);

        $job = new ProcessWebhookEvent($webhookEvent);
        $job->handle();

        $tx->refresh();
        $webhookEvent->refresh();

        $this->assertEquals('completed', $tx->status);
        $this->assertEquals('completed', $webhookEvent->status);
        $this->assertNotNull($webhookEvent->processed_at);
    }

    public function test_webhook_event_job_reverts_balance_on_failed_payment()
    {
        // Setup initial balance: we subtract 1000 representing the pending external transaction
        $this->fromAccount->balance = 9000;
        $this->fromAccount->available_balance = 9000;
        $this->fromAccount->save();

        $tx = Transaction::create([
            'reference' => 'TX-TESTFAIL',
            'type' => 'sepa',
            'status' => 'pending',
            'debit_account_id' => $this->fromAccount->id,
            'user_id' => $this->user->id,
            'amount' => 1000,
            'fee' => 10, // Let's test fee refund too
            'net_amount' => 1000,
            'currency' => 'EUR',
            'description' => 'SEPA Pending Tx to Fail',
            'metadata' => ['baas_external_id' => 'external_sepa_fail']
        ]);

        $webhookEvent = WebhookEvent::create([
            'event_type' => 'transfer',
            'source' => 'solarís',
            'payload' => [
                'external_id' => 'external_sepa_fail',
                'status' => 'failed',
                'failure_reason' => 'Insufficient remote funds',
            ],
            'status' => 'pending'
        ]);

        $job = new ProcessWebhookEvent($webhookEvent);
        $job->handle();

        $tx->refresh();
        $webhookEvent->refresh();
        $this->fromAccount->refresh();

        $this->assertEquals('failed', $tx->status);
        $this->assertEquals('Insufficient remote funds', $tx->failure_reason);
        $this->assertEquals('completed', $webhookEvent->status);
        // Balance should be refunded: 9000 + 1000 + 10 = 10010
        $this->assertEquals(10010, $this->fromAccount->balance);
    }

    public function test_webhook_event_job_creates_compliance_alert()
    {
        $tx = Transaction::create([
            'reference' => 'TX-COMPALERT',
            'type' => 'sepa',
            'status' => 'pending',
            'debit_account_id' => $this->fromAccount->id,
            'user_id' => $this->user->id,
            'amount' => 1000,
            'fee' => 0,
            'net_amount' => 1000,
            'currency' => 'EUR',
            'description' => 'SEPA Pending Tx',
        ]);

        $webhookEvent = WebhookEvent::create([
            'event_type' => 'compliance',
            'source' => 'solarís',
            'payload' => [
                'severity' => 'high',
                'description' => 'Potential AML structuring pattern detected',
                'transaction_id' => $tx->id
            ],
            'status' => 'pending'
        ]);

        $job = new ProcessWebhookEvent($webhookEvent);
        $job->handle();

        $webhookEvent->refresh();
        $this->assertEquals('completed', $webhookEvent->status);

        $this->assertDatabaseHas('monitoring_alerts', [
            'transaction_id' => $tx->id,
            'status' => 'open',
            'severity' => 'high',
            'description' => 'Potential AML structuring pattern detected',
        ]);
    }
}
