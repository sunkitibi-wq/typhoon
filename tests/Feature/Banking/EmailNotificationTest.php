<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\BankNotification;
use App\Models\Role;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Notifications\Admin\NewUserRegistered;
use App\Notifications\AccountOpenedNotification;
use App\Notifications\Admin\NewAccountCreated;
use App\Notifications\TransferNotification;
use App\Notifications\TransactionProcessedNotification;
use App\Notifications\Admin\NewTransactionProcessed;
use App\Services\AccountService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private AccountType $accountType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'client'], ['description' => 'Standard client']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin user']);
        Role::firstOrCreate(['name' => 'user'], ['description' => 'Default user']);

        $this->accountType = AccountType::create([
            'code' => 'personal',
            'name' => 'Personal Account',
            'currency' => 'EUR',
            'minimum_balance' => 0,
            'monthly_fee' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->role()->associate(Role::where('name', 'client')->first());
        $this->user->save();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_api_registration_sends_notifications(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Registration Client',
            'email' => 'regtest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(201);

        $newUser = User::where('email', 'regtest@example.com')->first();
        $this->assertNotNull($newUser);

        // Verify welcome notification is sent to the client
        Notification::assertSentTo(
            $newUser,
            WelcomeNotification::class
        );

        // Verify registration notification is sent to the admins
        Notification::assertSentTo(
            $this->admin,
            NewUserRegistered::class
        );
    }

    public function test_account_creation_sends_notifications(): void
    {
        Notification::fake();

        $accountService = app(AccountService::class);
        $account = $accountService->createAccount($this->user, 'personal', 'EUR', 'Savings Account');

        // Client and admin should be notified
        Notification::assertSentTo($this->user, AccountOpenedNotification::class);
        Notification::assertSentTo($this->admin, NewAccountCreated::class);
    }

    public function test_transfer_sends_notifications(): void
    {
        Notification::fake();

        $senderAccount = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000000001',
            'currency' => 'EUR',
            'balance' => 1000.00,
            'available_balance' => 1000.00,
            'ledger_balance' => 1000.00,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $receiver = User::factory()->create();
        $receiver->role()->associate(Role::where('name', 'client')->first());
        $receiver->save();

        $receiverAccount = $receiver->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000000002',
            'currency' => 'EUR',
            'balance' => 50.00,
            'available_balance' => 50.00,
            'ledger_balance' => 50.00,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $transactionService = app(TransactionService::class);
        $tx = $transactionService->transfer($senderAccount, $receiverAccount, 100.00, 'Test transfer', $this->user);

        // Sender notified (sent)
        Notification::assertSentTo($this->user, TransferNotification::class, function ($notification) {
            return $notification->direction === 'sent';
        });

        // Receiver notified (received)
        Notification::assertSentTo($receiver, TransferNotification::class, function ($notification) {
            return $notification->direction === 'received';
        });

        // Admins notified
        Notification::assertSentTo($this->admin, NewTransactionProcessed::class);
    }

    public function test_deposit_and_withdrawal_sends_notifications(): void
    {
        Notification::fake();

        $account = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000000003',
            'currency' => 'EUR',
            'balance' => 500.00,
            'available_balance' => 500.00,
            'ledger_balance' => 500.00,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $transactionService = app(TransactionService::class);

        // Test Deposit
        $txDeposit = $transactionService->deposit($account, 150.00, 'bank_transfer', 'DEP-TEST-01');

        Notification::assertSentTo($this->user, TransactionProcessedNotification::class, function ($notification) {
            return $notification->transaction->type === 'deposit';
        });
        Notification::assertSentTo($this->admin, NewTransactionProcessed::class, function ($notification) {
            return $notification->transaction->type === 'deposit';
        });

        // Test Withdrawal
        $txWithdrawal = $transactionService->withdraw($account, 50.00, 'bank_transfer');

        Notification::assertSentTo($this->user, TransactionProcessedNotification::class, function ($notification) {
            return $notification->transaction->type === 'withdrawal';
        });
        Notification::assertSentTo($this->admin, NewTransactionProcessed::class, function ($notification) {
            return $notification->transaction->type === 'withdrawal';
        });
    }

    public function test_bank_database_channel_writes_to_db(): void
    {
        // Don't fake Notification to test custom channel database side-effects
        $account = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000000004',
            'currency' => 'EUR',
            'balance' => 200.00,
            'available_balance' => 200.00,
            'ledger_balance' => 200.00,
            'status' => 'active',
            'label' => 'Checking',
        ]);

        $this->user->notify(new AccountOpenedNotification($account));

        // Check if database notification was created in the custom table
        $this->assertDatabaseHas('bank_notifications', [
            'user_id' => $this->user->id,
            'type' => 'account',
            'channel' => 'in_app',
            'title' => 'New Account Opened',
            'status' => 'unread',
        ]);

        $dbNotification = BankNotification::where('user_id', $this->user->id)->first();
        $this->assertNotNull($dbNotification);
        $this->assertStringContainsString('TY0000000004', $dbNotification->body);
    }
}
