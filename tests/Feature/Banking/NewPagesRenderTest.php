<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\AccountType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);

        $this->user = User::factory()->create();
    }

    public function test_sepa_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.sepa'));
        $response->assertOk();
    }

    public function test_swift_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.swift'));
        $response->assertOk();
    }

    public function test_standing_orders_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.standing-orders'));
        $response->assertOk();
    }

    public function test_beneficiaries_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.beneficiaries'));
        $response->assertOk();
    }

    public function test_statements_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.statements'));
        $response->assertOk();
    }

    public function test_notifications_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.notifications'));
        $response->assertOk();
    }
}
