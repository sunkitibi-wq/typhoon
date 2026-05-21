<?php

namespace Tests\Feature\Banking;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KycTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);

        $this->user = User::factory()->create();
        $this->user->role()->associate(Role::where('name', 'user')->first());
        $this->user->save();

        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->role()->associate(Role::where('name', 'admin')->first());
        $this->admin->save();
    }

    public function test_kyc_page_is_rendered()
    {
        $response = $this->actingAs($this->user)->get(route('banking.kyc'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('banking/kyc'));
    }

    public function test_user_can_submit_kyc()
    {
        $response = $this->actingAs($this->user)->post(route('banking.kyc'), [
            'country' => 'DE',
            'date_of_birth' => '1990-01-15',
            'id_type' => 'passport',
            'id_number' => 'P12345678',
            'address_line1' => 'Main Street 123',
            'city' => 'Berlin',
        ]);

        $response->assertSessionHas('success', 'KYC submitted successfully');

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $this->user->id,
            'status' => 'pending',
            'country' => 'DE',
        ]);
    }

    public function test_kyc_submission_requires_country()
    {
        $response = $this->actingAs($this->user)->post(route('banking.kyc'), [
            'date_of_birth' => '1990-01-15',
        ]);

        $response->assertSessionHasErrors('country');
    }

    public function test_kyc_submission_requires_date_of_birth()
    {
        $response = $this->actingAs($this->user)->post(route('banking.kyc'), [
            'country' => 'DE',
        ]);

        $response->assertSessionHasErrors('date_of_birth');
    }

    public function test_kyc_status_shows_not_submitted_initially()
    {
        $response = $this->actingAs($this->user)->get(route('banking.kyc'));

        $response->assertInertia(fn ($page) => $page
            ->where('kyc_status.status', 'not_submitted')
            ->where('kyc_status.submitted', false)
        );
    }

    public function test_admin_can_access_kyc_queue()
    {
        $this->user->kycVerification()->create([
            'country' => 'DE',
            'date_of_birth' => '1990-01-15',
            'status' => 'pending',
            'kyc_level' => 'tier_1',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kyc'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/kyc-verification')
            ->has('pending', 1)
        );
    }

    public function test_non_admin_cannot_access_kyc_queue()
    {
        $response = $this->actingAs($this->user)->get(route('admin.kyc'));

        $response->assertForbidden();
    }
}
