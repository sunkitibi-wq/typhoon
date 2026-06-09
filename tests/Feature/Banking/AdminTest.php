<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\AccountType;
use App\Models\Banking\Transaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);

        $type = AccountType::create([
            'code' => 'personal',
            'name' => 'Personal Account',
            'currency' => 'EUR',
            'minimum_balance' => 0,
            'monthly_fee' => 0,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->admin()->create(['email' => 'admin@test.com']);

        $this->user = User::factory()->create();
        $this->user->role()->associate(Role::where('name', 'user')->first());
        $this->user->save();
        $this->user->accounts()->create([
            'account_type_id' => $type->id,
            'account_number' => 'TY0000001001',
            'currency' => 'EUR',
            'balance' => 5000,
            'available_balance' => 5000,
            'ledger_balance' => 5000,
            'status' => 'active',
            'label' => 'Main',
            'is_default' => true,
        ]);
    }

    public function test_admin_dashboard_is_accessible()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('admin/dashboard'));
    }

    public function test_non_admin_cannot_access_admin_dashboard()
    {
        $response = $this->actingAs($this->user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_dashboard_shows_kpis()
    {
        Transaction::create([
            'reference' => 'TFR-TEST0001',
            'type' => 'deposit',
            'status' => 'completed',
            'credit_account_id' => $this->user->accounts->first()->id,
            'user_id' => $this->user->id,
            'amount' => 1000,
            'fee' => 0,
            'net_amount' => 1000,
            'currency' => 'EUR',
            'description' => 'Test deposit',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->has('data.total_users')
            ->has('data.active_accounts')
            ->has('data.recent_transactions')
        );
    }

    public function test_admin_can_view_users_list()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('admin/users'));
    }

    public function test_non_admin_cannot_view_users_list()
    {
        $response = $this->actingAs($this->user)->get(route('admin.users'));

        $response->assertForbidden();
    }

    public function test_admin_can_assign_roles()
    {
        $roleAdmin = Role::where('name', 'admin')->first();

        $response = $this->actingAs($this->admin)->post(route('admin.users.edit', $this->user->id), [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role_id' => $roleAdmin->id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.users.detail', $this->user->id));
        $this->user->refresh();
        $this->assertEquals($roleAdmin->id, $this->user->role_id);
    }

    public function test_admin_can_freeze_user_account()
    {
        $account = $this->user->accounts->first();

        $response = $this->actingAs($this->admin)->post(route('admin.accounts.freeze', $account->id));

        $response->assertRedirect();
        $account->refresh();
        $this->assertEquals('frozen', $account->status);
    }

    public function test_admin_can_unfreeze_user_account()
    {
        $account = $this->user->accounts->first();
        $account->update(['status' => 'frozen']);

        $response = $this->actingAs($this->admin)->post(route('admin.accounts.unfreeze', $account->id));

        $response->assertRedirect();
        $account->refresh();
        $this->assertEquals('active', $account->status);
    }

    public function test_admin_can_close_user_account()
    {
        $account = $this->user->accounts->first();

        $response = $this->actingAs($this->admin)->post(route('admin.accounts.close', $account->id));

        $response->assertRedirect();
        $account->refresh();
        $this->assertEquals('closed', $account->status);
        $this->assertNotNull($account->closed_at);
    }

    public function test_admin_can_view_accounts_list()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.accounts'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('admin/accounts'));
    }

    public function test_admin_can_approve_account()
    {
        $account = $this->user->accounts->first();
        $account->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->post(route('admin.accounts.approve', $account->id));

        $response->assertRedirect();
        $account->refresh();
        $this->assertEquals('active', $account->status);
    }

    public function test_admin_can_reject_account()
    {
        $account = $this->user->accounts->first();
        $account->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->post(route('admin.accounts.reject', $account->id));

        $response->assertRedirect();
        $account->refresh();
        $this->assertEquals('rejected', $account->status);
    }
}
