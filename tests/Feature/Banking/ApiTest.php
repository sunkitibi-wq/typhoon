<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\AccountType;
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
}
