<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\Transaction;
use App\Models\Banking\WebhookEvent;
use App\Models\Role;
use App\Models\User;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SolarisbankIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);

        $accountType = AccountType::create([
            'code' => 'personal',
            'name' => 'Personal Account',
            'currency' => 'EUR',
            'minimum_balance' => 0,
            'monthly_fee' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'solaris_person_id' => 'person_12345',
        ]);
        $this->user->role()->associate(Role::where('name', 'user')->first());
        $this->user->save();

        $this->account = $this->user->accounts()->create([
            'account_type_id' => $accountType->id,
            'account_number' => 'TY0000004001',
            'iban' => 'DE89370400440532014001',
            'swift_bic' => 'COBADEFFXXX',
            'currency' => 'EUR',
            'balance' => 1000,
            'available_balance' => 1000,
            'ledger_balance' => 1000,
            'status' => 'active',
            'solaris_account_id' => 'acc_67890',
        ]);
    }

    public function test_solaris_credit_transfer_oauth_flow_and_api_call(): void
    {
        config(['services.baas.driver' => 'solarisbank']);
        config(['services.baas.api_key' => 'test-key']);
        config(['services.baas.secret' => 'test-secret']);
        config(['services.baas.base_url' => 'https://api.sandbox.solarisbank.de']);

        Cache::forget('solarisbank:oauth_token');

        Http::fake([
            'https://api.sandbox.solarisbank.de/oauth/token' => Http::response([
                'access_token' => 'mocked-jwt-token',
                'expires_in' => 3600,
            ], 200),
            'https://api.sandbox.solarisbank.de/v1/persons/person_12345/accounts/acc_67890/transactions/sepa_credit_transfer' => Http::response([
                'id' => 'sol_tx_99999',
                'status' => 'pending',
            ], 200),
        ]);

        $baasService = app(\App\Services\BaasService::class);
        $response = $baasService->initiateCreditTransfer([
            'external_person_id' => 'person_12345',
            'external_account_id' => 'acc_67890',
            'amount' => 100.0,
            'currency' => 'EUR',
            'creditor_iban' => 'DE89370400440532014002',
            'creditor_bic' => 'COBADEFFXXX',
            'creditor_name' => 'John Doe',
            'remittance_info' => 'Invoice #1',
        ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('sol_tx_99999', $response['id']);

        // Assert cached token
        $this->assertEquals('mocked-jwt-token', Cache::get('solarisbank:oauth_token'));
    }

    public function test_process_solaris_webhook_completed_event(): void
    {
        // Set up pending transaction
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'debit_account_id' => $this->account->id,
            'amount' => 100.0,
            'fee' => 0.15,
            'net_amount' => 100.15,
            'currency' => 'EUR',
            'type' => 'transfer',
            'status' => 'pending',
            'reference' => 'TX123456789',
            'metadata' => [
                'baas_external_id' => 'sol_tx_99999',
            ],
        ]);

        $webhookPayload = [
            'id' => 'evt_123',
            'event_type' => 'MUTATION_BOOK',
            'resource_id' => 'sol_tx_99999',
            'resource_type' => 'sepa_credit_transfer',
            'payload' => [
                'id' => 'sol_tx_99999',
                'booking_status' => 'successful_booking',
            ]
        ];

        $webhookEvent = WebhookEvent::create([
            'event_type' => 'MUTATION_BOOK',
            'source' => 'solarisbank',
            'payload' => $webhookPayload,
            'status' => 'pending',
        ]);

        $job = new ProcessWebhookEvent($webhookEvent);
        $job->handle();

        $transaction->refresh();
        $webhookEvent->refresh();

        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals('completed', $webhookEvent->status);
    }
}
