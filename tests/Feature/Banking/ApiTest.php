<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\AccountType;
use App\Models\Banking\Account;
use App\Models\Banking\StandingOrder;
use App\Models\Banking\Loan;
use App\Models\Banking\BankNotification;
use App\Models\Banking\PosTerminal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);

        AccountType::create([
            'code' => 'personal',
            'name' => 'Personal Account',
            'currency' => 'EUR',
            'minimum_balance' => 0,
            'monthly_fee' => 0,
            'is_active' => true,
        ]);
    }

    public function test_api_login()
    {
        $user = User::factory()->create([
            'email' => 'test@api.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@api.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_api_login_fails_with_wrong_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrong',
        ]);

        $response->assertUnprocessable();
    }

    public function test_api_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_api_requires_auth_for_protected_endpoints()
    {
        $response = $this->getJson('/api/accounts');

        $response->assertUnauthorized();
    }

    public function test_api_can_get_account_types()
    {
        $response = $this->getJson('/api/account-types');

        $response->assertOk()
            ->assertJsonStructure(['account_types']);
    }

    public function test_api_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        $user->role()->associate(Role::where('name', 'user')->first());
        $user->save();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_api_can_get_crypto_currencies()
    {
        $response = $this->getJson('/api/crypto/currencies');

        $response->assertOk()
            ->assertJsonStructure(['currencies']);
    }

    public function test_api_forgot_password_sends_otp()
    {
        $user = User::factory()->create([
            'email' => 'forgot@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'forgot@test.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'forgot@test.com',
        ]);
    }

    public function test_api_reset_password_resets_successfully()
    {
        $user = User::factory()->create([
            'email' => 'reset@test.com',
            'password' => Hash::make('oldpassword'),
        ]);

        \DB::table('password_reset_tokens')->insert([
            'email' => 'reset@test.com',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset@test.com',
            'token' => '123456',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertOk();
        
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'reset@test.com',
        ]);
    }

    public function test_api_sepa_transfers_endpoints()
    {
        $user = User::factory()->create();
        $user->role()->associate(Role::where('name', 'user')->first());
        $user->save();

        $account = $user->accounts()->create([
            'account_type_id' => AccountType::where('code', 'personal')->first()->id,
            'account_number' => 'TY0000000002',
            'currency' => 'EUR',
            'balance' => 500,
            'available_balance' => 500,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        // GET transfers
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/sepa/transfers');
        $response->assertOk()->assertJsonStructure(['transfers']);

        // POST credit transfer
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sepa/transfers', [
            'debit_account_id' => $account->id,
            'amount' => 100,
            'creditor_name' => 'Alice Smith',
            'creditor_iban' => 'DE89370400440532013111',
            'bic' => 'BOFAUS3NXXX',
        ]);
        $response->assertStatus(201);

        // POST direct debit
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sepa/direct-debits', [
            'credit_account_id' => $account->id,
            'amount' => 50,
            'debtor_name' => 'Bob Miller',
            'debtor_iban' => 'DE89370400440532013222',
        ]);
        $response->assertStatus(201);
    }

    public function test_api_swift_transfers_endpoints()
    {
        $user = User::factory()->create();
        $user->role()->associate(Role::where('name', 'user')->first());
        $user->save();

        $account = $user->accounts()->create([
            'account_type_id' => AccountType::where('code', 'personal')->first()->id,
            'account_number' => 'TY0000000003',
            'currency' => 'EUR',
            'balance' => 500,
            'available_balance' => 500,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/swift/transfers');
        $response->assertOk()->assertJsonStructure(['transfers']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/swift/transfers', [
            'debit_account_id' => $account->id,
            'amount' => 100,
            'beneficiary_name' => 'Global Corp',
            'beneficiary_account' => 'US1234567890',
            'bic' => 'BOFAUS3NXXX',
            'bank_name' => 'Bank of America',
        ]);
        $response->assertStatus(201);
    }

    public function test_api_standing_orders_endpoints()
    {
        $user = User::factory()->create();
        $user->role()->associate(Role::where('name', 'user')->first());
        $user->save();

        $account = $user->accounts()->create([
            'account_type_id' => AccountType::where('code', 'personal')->first()->id,
            'account_number' => 'TY0000000004',
            'currency' => 'EUR',
            'balance' => 500,
            'available_balance' => 500,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/standing-orders', [
            'debit_account_id' => $account->id,
            'beneficiary_name' => 'Landlord',
            'beneficiary_iban' => 'DE89370400440532013333',
            'amount' => 600,
            'currency' => 'EUR',
            'frequency' => 'monthly',
            'start_date' => now()->addDay()->toDateString(),
        ]);
        $response->assertStatus(201);
        $orderId = $response->json('id');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/standing-orders');
        $response->assertOk()->assertJsonStructure(['standing_orders']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/standing-orders/{$orderId}/toggle");
        $response->assertOk();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/standing-orders/{$orderId}");
        $response->assertOk();
    }

    public function test_api_deposits_endpoints()
    {
        $user = User::factory()->create();
        $user->role()->associate(Role::where('name', 'user')->first());
        $user->save();

        $account = $user->accounts()->create([
            'account_type_id' => AccountType::where('code', 'personal')->first()->id,
            'account_number' => 'TY0000000005',
            'currency' => 'EUR',
            'balance' => 500,
            'available_balance' => 500,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/deposits');
        $response->assertOk()->assertJsonStructure(['deposits']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/deposits', [
            'account_id' => $account->id,
            'amount' => 200,
            'method' => 'bank_transfer',
        ]);
        $response->assertStatus(201);
    }

    public function test_api_loans_endpoints()
    {
        $user = User::factory()->create();
        $user->role()->associate(Role::where('name', 'user')->first());
        $user->save();

        $account = $user->accounts()->create([
            'account_type_id' => AccountType::where('code', 'personal')->first()->id,
            'account_number' => 'TY0000000006',
            'currency' => 'EUR',
            'balance' => 500,
            'available_balance' => 500,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/loans');
        $response->assertOk()->assertJsonStructure(['loans']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/loans', [
            'account_id' => $account->id,
            'amount' => 5000,
            'interest_rate' => 4.5,
            'term_months' => 24,
            'purpose' => 'Car purchase',
        ]);
        $response->assertStatus(201);
    }

    public function test_api_notifications_endpoints()
    {
        $user = User::factory()->create();
        $user->role()->associate(Role::where('name', 'user')->first());
        $user->save();

        BankNotification::create([
            'user_id' => $user->id,
            'type' => 'info',
            'channel' => 'in_app',
            'title' => 'Test Notification',
            'body' => 'This is a test notification',
            'status' => 'unread',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/notifications');
        $response->assertOk()->assertJsonStructure(['notifications']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/notifications/read-all');
        $response->assertOk();
    }

    public function test_api_pos_management_endpoints()
    {
        $user = User::factory()->create();
        $user->role()->associate(Role::where('name', 'user')->first());
        $user->save();

        $account = $user->accounts()->create([
            'account_type_id' => AccountType::where('code', 'personal')->first()->id,
            'account_number' => 'TY0000000007',
            'currency' => 'EUR',
            'balance' => 500,
            'available_balance' => 500,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/pos/terminals', [
            'account_id' => $account->id,
            'serial_number' => 'TY-API-POS-01',
            'label' => 'Register 1',
            'device_model' => 'Terminal S1',
        ]);
        $response->assertStatus(201);
        $terminalId = $response->json('terminal.id');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/pos/terminals');
        $response->assertOk()->assertJsonStructure(['terminals']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/pos/terminals/{$terminalId}/toggle");
        $response->assertOk();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/pos/terminals/{$terminalId}");
        $response->assertOk();
    }
}
