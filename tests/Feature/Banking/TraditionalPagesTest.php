<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\KycVerification;
use App\Models\Banking\MonitoringAlert;
use App\Models\Banking\Transaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraditionalPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private AccountType $accountType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin user']);

        $this->accountType = AccountType::create([
            'code' => 'checking',
            'name' => 'Checking Account',
            'currency' => 'EUR',
            'minimum_balance' => 0,
            'monthly_fee' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->role()->associate(Role::where('name', 'user')->first());
        $this->user->save();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_guest_cannot_access_traditional_routes(): void
    {
        $response1 = $this->get('/client/home');
        $response1->assertRedirect('/login');

        $response2 = $this->get('/admin/home');
        $response2->assertRedirect('/login');
    }

    public function test_traditional_client_home_displays_actual_user_accounts_and_transactions(): void
    {
        // Create accounts for user
        $account1 = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000003001',
            'iban' => 'DE89370400440532013001',
            'currency' => 'EUR',
            'balance' => 1250.50,
            'available_balance' => 1250.50,
            'ledger_balance' => 1250.50,
            'status' => 'active',
            'label' => 'Main Checking',
        ]);

        $account2 = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000003002',
            'iban' => 'DE89370400440532013002',
            'currency' => 'EUR',
            'balance' => 500.00,
            'available_balance' => 500.00,
            'ledger_balance' => 500.00,
            'status' => 'frozen',
            'label' => 'Savings Account',
        ]);

        // Create transaction for user
        $tx = Transaction::create([
            'reference' => 'TFR-CLITEST001',
            'type' => 'deposit',
            'status' => 'completed',
            'credit_account_id' => $account1->id,
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'fee' => 0,
            'net_amount' => 1000.00,
            'currency' => 'EUR',
            'description' => 'Direct deposit salary',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/client/home');

        $response->assertStatus(200);
        $response->assertSee('Main Checking');
        $response->assertSee('Savings Account');
        $response->assertSee('1,250.50');
        $response->assertSee('Frozen');
        $response->assertSee('Direct deposit salary');
        $response->assertSee('TFR-CLITEST001');
    }

    public function test_traditional_admin_home_displays_actual_platform_metrics_and_alerts(): void
    {
        // Create a KYC submission
        $applicant = User::factory()->create();
        KycVerification::create([
            'user_id' => $applicant->id,
            'kyc_level' => 'tier_1',
            'status' => 'pending',
            'id_type' => 'passport',
            'id_number' => 'A1234567',
            'country' => 'US',
            'date_of_birth' => '1990-01-01',
            'address_line1' => '123 Test St',
            'city' => 'New York',
            'postal_code' => '10001',
            'nationality' => 'American',
        ]);

        // Create a compliance alert
        $rule = \App\Models\Banking\MonitoringRule::create([
            'name' => 'High Volume Tx',
            'category' => 'AML',
            'rule_type' => 'velocity',
            'conditions' => [],
            'severity' => 'high',
            'is_active' => true,
        ]);

        MonitoringAlert::create([
            'monitoring_rule_id' => $rule->id,
            'user_id' => $applicant->id,
            'alert_type' => 'Velocity Alert',
            'severity' => 'high',
            'status' => 'open',
            'description' => 'Suspicious high volume transfer detected',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/home');

        $response->assertStatus(200);
        // Assert metrics are rendered
        $response->assertSee('Pending KYC');
        $response->assertSee('Compliance Alerts');
        // Assert list data is rendered
        $response->assertSee($applicant->name);
        $response->assertSee('Suspicious high volume transfer detected');
        $response->assertSee('High');
        $response->assertSee('Open');
    }
}
