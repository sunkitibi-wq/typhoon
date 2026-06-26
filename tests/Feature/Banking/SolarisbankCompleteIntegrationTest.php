<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\Card;
use App\Models\Banking\KycVerification;
use App\Models\Banking\Transaction;
use App\Models\Banking\WebhookEvent;
use App\Models\Role;
use App\Models\User;
use App\Services\KycService;
use App\Services\AccountService;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SolarisbankCompleteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AccountType $accountType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin user']);
        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);

        $this->accountType = AccountType::create([
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
    }

    public function test_target2_inbound_transfer_webhook_processing(): void
    {
        $account = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
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

        $webhookPayload = [
            'id' => 'evt_target2_9999',
            'event_type' => 'MUTATION_BOOK',
            'resource_id' => 'tx_target2_external_id',
            'resource_type' => 'sepa_credit_transfer',
            'payload' => [
                'id' => 'tx_target2_external_id',
                'booking_status' => 'successful_booking',
                'booking_type' => 'TARGET2_CREDIT_TRANSFER',
                'charge_details' => 'SHAR',
                'recipient_iban' => 'DE89370400440532014001',
                'amount' => [
                    'value' => 500.0,
                    'currency' => 'EUR',
                ],
                'sender_name' => 'Acme Corp Australia',
                'sender_iban' => 'AU1234567890',
                'description' => 'Invoice Payment',
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

        $account->refresh();
        $webhookEvent->refresh();

        // 1000 + 500 = 1500
        $this->assertEquals(1500.0, (float) $account->balance);
        $this->assertEquals('completed', $webhookEvent->status);

        $tx = Transaction::where('credit_account_id', $account->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals('target2_transfer', $tx->metadata['method'] ?? null);
        $this->assertEquals('TARGET2_CREDIT_TRANSFER', $tx->metadata['booking_type'] ?? null);
        $this->assertEquals('SHAR', $tx->metadata['charge_details'] ?? null);
        $this->assertEquals('Acme Corp Australia', $tx->metadata['sender_name'] ?? null);
        $this->assertEquals('AU1234567890', $tx->metadata['sender_iban'] ?? null);
    }

    public function test_dynamic_person_creation_upon_kyc_approval(): void
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
            'https://api.sandbox.solarisbank.de/v1/persons' => Http::response([
                'id' => 'sol_person_999',
                'first_name' => 'John',
                'last_name' => 'Doe',
            ], 200),
        ]);

        $kyc = KycVerification::create([
            'user_id' => $this->user->id,
            'kyc_level' => 'tier_1',
            'status' => 'pending',
            'country' => 'DE',
            'nationality' => 'German',
            'date_of_birth' => '1990-01-01',
            'address_line1' => 'Musterstr. 1',
            'city' => 'Berlin',
            'postal_code' => '10115',
        ]);

        $adminUser = User::factory()->create();
        $adminUser->role()->associate(Role::where('name', 'admin')->first());
        $adminUser->save();

        $kycService = app(KycService::class);
        $kycService->approveVerification($kyc, $adminUser);

        $this->user->refresh();
        $this->assertEquals('sol_person_999', $this->user->solaris_person_id);
    }

    public function test_dynamic_account_creation_from_baas(): void
    {
        config(['services.baas.driver' => 'solarisbank']);
        config(['services.baas.api_key' => 'test-key']);
        config(['services.baas.secret' => 'test-secret']);
        config(['services.baas.base_url' => 'https://api.sandbox.solarisbank.de']);

        Cache::forget('solarisbank:oauth_token');

        $this->user->update(['solaris_person_id' => 'sol_person_999']);

        Http::fake([
            'https://api.sandbox.solarisbank.de/oauth/token' => Http::response([
                'access_token' => 'mocked-jwt-token',
                'expires_in' => 3600,
            ], 200),
            'https://api.sandbox.solarisbank.de/v1/persons/sol_person_999/accounts' => Http::response([
                'id' => 'sol_acc_888',
                'iban' => 'DE99370400440532018888',
                'currency' => 'EUR',
            ], 200),
        ]);

        $accountService = app(AccountService::class);
        $account = $accountService->createAccount($this->user, 'personal', 'EUR', 'BaaS Account');

        $this->assertNotNull($account);
        $this->assertEquals('sol_acc_888', $account->solaris_account_id);
        $this->assertEquals('DE99370400440532018888', $account->iban);
        $this->assertEquals('active', $account->status);
    }

    public function test_cards_creation_and_status_toggling(): void
    {
        config(['services.baas.driver' => 'solarisbank']);
        config(['services.baas.api_key' => 'test-key']);
        config(['services.baas.secret' => 'test-secret']);
        config(['services.baas.base_url' => 'https://api.sandbox.solarisbank.de']);

        Cache::forget('solarisbank:oauth_token');

        $this->user->update(['solaris_person_id' => 'sol_person_999']);
        $account = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000004001',
            'iban' => 'DE89370400440532014001',
            'currency' => 'EUR',
            'balance' => 1000,
            'status' => 'active',
            'solaris_account_id' => 'sol_acc_888',
        ]);

        Http::fake([
            'https://api.sandbox.solarisbank.de/oauth/token' => Http::response([
                'access_token' => 'mocked-jwt-token',
                'expires_in' => 3600,
            ], 200),
            'https://api.sandbox.solarisbank.de/v1/persons/sol_person_999/accounts/sol_acc_888/cards' => Http::response([
                'id' => 'sol_card_777',
                'type' => 'virtual',
                'cardholder_name' => 'John Doe',
                'masked_pan' => '411111******1111',
                'expiration_date' => '12/28',
                'status' => 'active',
            ], 200),
            'https://api.sandbox.solarisbank.de/v1/cards/sol_card_777/block' => Http::response([
                'id' => 'sol_card_777',
                'status' => 'blocked',
            ], 200),
        ]);

        // 1. Create card via web route
        $this->actingAs($this->user);
        $response = $this->post(route('banking.cards.store'), [
            'account_id' => $account->id,
            'type' => 'virtual',
            'cardholder_name' => 'John Doe',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cards', [
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'solaris_card_id' => 'sol_card_777',
            'type' => 'virtual',
            'status' => 'active',
        ]);

        $card = Card::where('solaris_card_id', 'sol_card_777')->first();

        // 2. Toggle status to blocked
        $toggleResponse = $this->post(route('banking.cards.toggle', $card->id), [
            'status' => 'blocked',
        ]);

        $toggleResponse->assertRedirect();
        $card->refresh();
        $this->assertEquals('blocked', $card->status);
    }
}
