<?php

namespace App\Http\Controllers;

use App\Models\Banking\Account;
use App\Models\Banking\Transaction;
use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\CryptoWallet;
use App\Models\Banking\CryptoOrder;
use App\Models\Banking\CryptoDeposit;
use App\Models\Banking\CryptoWithdrawal;
use App\Models\Banking\ExchangeRate;
use App\Models\Banking\AccountType;
use App\Models\Banking\KycVerification;
use App\Models\Banking\Beneficiary;
use App\Models\Banking\StandingOrder;
use App\Models\Banking\SepaTransfer;
use App\Models\Banking\SwiftTransfer;
use App\Models\Banking\BankNotification;
use App\Models\Banking\Loan;
use App\Services\AccountService;
use App\Services\CryptoExchangeService;
use App\Services\KycService;
use App\Services\LoanService;
use App\Services\ReportService;
use App\Services\SepaService;
use App\Services\SwiftService;
use App\Services\TransactionRouter;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankingController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly TransactionService $transactionService,
        private readonly KycService $kycService,
        private readonly CryptoExchangeService $cryptoService,
        private readonly SepaService $sepaService,
        private readonly SwiftService $swiftService,
        private readonly ReportService $reportService,
        private readonly LoanService $loanService,
        private readonly TransactionRouter $transactionRouter,
    ) {}

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $accounts = $user->accounts()->with('accountType')->get();
        $transactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $monthStart = now()->startOfMonth();
        $monthlyIncome = Transaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $monthStart)
            ->whereIn('type', ['deposit', 'transfer'])
            ->sum('net_amount');

        $monthlySpending = Transaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $monthStart)
            ->whereIn('type', ['withdrawal', 'transfer'])
            ->sum('net_amount');

        return Inertia::render('banking/dashboard', [
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'number' => $a->account_number,
                'iban' => $a->iban,
                'type' => $a->accountType?->name,
                'currency' => $a->currency,
                'balance' => (float) $a->balance,
                'available_balance' => (float) $a->available_balance,
                'status' => $a->status,
                'label' => $a->label,
                'is_default' => $a->is_default,
            ]),
            'recent_transactions' => $transactions->map(fn($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'currency' => $t->currency,
                'description' => $t->description,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]),
            'total_balance' => (float) $accounts->sum('balance'),
            'monthly_income' => (float) $monthlyIncome,
            'monthly_spending' => (float) $monthlySpending,
        ]);
    }

    public function accounts(Request $request)
    {
        $accounts = $request->user()->accounts()->with('accountType')->get();

        return Inertia::render('banking/accounts', [
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->accountType?->name,
                'number' => $a->account_number,
                'iban' => $a->iban,
                'currency' => $a->currency,
                'balance' => (float) $a->balance,
                'available_balance' => (float) $a->available_balance,
                'status' => $a->status,
                'label' => $a->label,
                'is_default' => $a->is_default,
            ]),
        ]);
    }

    public function showAccount(Account $account, Request $request)
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $account->load('accountType');
        $transactions = Transaction::where(function ($q) use ($account) {
            $q->where('debit_account_id', $account->id)->orWhere('credit_account_id', $account->id);
        })->latest()->take(10)->get();

        return Inertia::render('banking/account', [
            'account' => [
                'id' => $account->id,
                'type' => $account->accountType?->name,
                'number' => $account->account_number,
                'iban' => $account->iban,
                'bic' => $account->swift_bic,
                'currency' => $account->currency,
                'balance' => (float) $account->balance,
                'available_balance' => (float) $account->available_balance,
                'ledger_balance' => (float) $account->ledger_balance,
                'status' => $account->status,
                'label' => $account->label,
                'is_default' => $account->is_default,
            ],
            'transactions' => $transactions->map(fn($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'fee' => (float) $t->fee,
                'currency' => $t->currency,
                'description' => $t->description,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]),
        ]);
    }

    public function freezeAccount(Account $account, Request $request)
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $account->update(['status' => 'frozen']);

        return redirect()->route('banking.accounts.show', $account)->with('success', 'Account frozen');
    }

    public function unfreezeAccount(Account $account, Request $request)
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $account->update(['status' => 'active']);

        return redirect()->route('banking.accounts.show', $account)->with('success', 'Account unfrozen');
    }

    public function closeAccount(Account $account, Request $request)
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        if ((float) $account->balance > 0) {
            return back()->withErrors(['balance' => 'Cannot close account with a positive balance. Transfer funds first.']);
        }

        $account->update(['status' => 'closed', 'closed_at' => now()]);

        return redirect()->route('banking.accounts')->with('success', 'Account closed');
    }

    public function createAccount(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'account_type_code' => 'required|string|exists:account_types,code',
                'label' => 'nullable|string|max:255',
            ]);

            try {
                $this->accountService->createAccount(
                    $request->user(),
                    $validated['account_type_code'],
                    'EUR',
                    $validated['label'] ?? null,
                );

                return redirect()->route('banking.accounts')->with('success', 'Account created successfully');
            } catch (\Exception $e) {
                return back()->withErrors(['account_type_code' => $e->getMessage()]);
            }
        }

        $types = AccountType::where('is_active', true)->get(['code', 'name', 'description', 'currency', 'minimum_balance', 'monthly_fee']);

        return Inertia::render('banking/accounts-create', [
            'account_types' => $types,
        ]);
    }

    public function transactions(Request $request)
    {
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->with(['debitAccount:id,account_number,label', 'creditAccount:id,account_number,label'])
            ->latest()
            ->paginate(20);

        return Inertia::render('banking/transactions', [
            'transactions' => $transactions,
        ]);
    }

    public function transfer(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'from_account_id' => 'required|exists:accounts,id',
                'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255',
            ]);

            $fromAccount = Account::findOrFail($validated['from_account_id']);
            $toAccount = Account::findOrFail($validated['to_account_id']);

            if ($fromAccount->user_id !== $user->id) {
                return back()->withErrors(['from_account_id' => 'You do not own this account']);
            }

            try {
                $this->transactionRouter->route($user, $fromAccount, [
                    'beneficiary_iban' => $toAccount->iban ?? $toAccount->account_number,
                    'beneficiary_name' => $toAccount->user->name ?? 'Internal Account',
                    'amount' => $validated['amount'],
                    'currency' => $fromAccount->currency,
                    'description' => $validated['description'] ?? null,
                ]);

                return redirect()->route('banking.transactions')->with('success', 'Transfer completed successfully');
            } catch (\Exception $e) {
                return back()->withErrors(['amount' => $e->getMessage()]);
            }
        }

        return Inertia::render('banking/transfer', [
            'accounts' => $user->accounts()->where('status', 'active')->get()->map(fn($a) => [
                'id' => $a->id,
                'number' => $a->account_number,
                'label' => $a->label,
                'balance' => (float) $a->balance,
                'currency' => $a->currency,
            ]),
        ]);
    }

    public function kyc(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'country' => 'required|string|size:2',
                'date_of_birth' => 'required|date|before:today',
                'id_type' => 'nullable|string',
                'id_number' => 'nullable|string',
                'address_line1' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
            ]);

            try {
                $this->kycService->submitVerification($request->user(), $validated);
                return redirect()->back()->with('success', 'KYC submitted successfully');
            } catch (\Exception $e) {
                return back()->withErrors(['country' => $e->getMessage()]);
            }
        }

        $kycStatus = $this->kycService->getVerificationStatus($request->user());

        return Inertia::render('banking/kyc', [
            'kyc_status' => $kycStatus,
        ]);
    }

    public function crypto(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $action = $request->input('action');

            return match ($action) {
                'create_wallet' => $this->cryptoCreateWallet($request),
                'place_order' => $this->cryptoPlaceOrder($request),
                'request_withdrawal' => $this->cryptoRequestWithdrawal($request),
                'record_deposit' => $this->cryptoRecordDeposit($request),
                'buy_with_fiat' => $this->cryptoBuyWithFiat($request),
                default => back()->withErrors(['action' => 'Invalid action']),
            };
        }

        $wallets = CryptoWallet::where('user_id', $user->id)
            ->with('cryptoCurrency')
            ->get();

        $rates = ExchangeRate::latest('last_refreshed_at')->take(10)->get();

        $orders = CryptoOrder::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        $deposits = CryptoDeposit::where('user_id', $user->id)
            ->with('cryptoCurrency')
            ->latest()
            ->take(10)
            ->get();

        $withdrawals = CryptoWithdrawal::where('user_id', $user->id)
            ->with('cryptoCurrency')
            ->latest()
            ->take(10)
            ->get();

        $currencies = CryptoCurrency::where('status', 'active')->get(['id', 'code', 'name', 'network', 'minimum_withdrawal', 'withdrawal_fee', 'minimum_deposit', 'deposit_fee']);

        $accounts = $user->accounts()->where('status', 'active')->get(['id', 'label', 'account_number', 'balance', 'currency']);

        return Inertia::render('banking/crypto', [
            'wallets' => $wallets->map(fn($w) => [
                'id' => $w->id,
                'currency' => $w->cryptoCurrency->code,
                'name' => $w->cryptoCurrency->name,
                'network' => $w->cryptoCurrency->network,
                'address' => $w->address,
                'balance' => (float) $w->balance,
                'locked_balance' => (float) $w->locked_balance,
                'label' => $w->label,
            ]),
            'rates' => $rates->map(fn($r) => [
                'pair' => "{$r->base_currency}/{$r->quote_currency}",
                'bid' => (float) $r->bid,
                'ask' => (float) $r->ask,
                'mid' => (float) $r->mid_rate,
                'change_24h' => (float) $r->change_24h,
            ]),
            'orders' => $orders->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'side' => $o->side,
                'base_currency' => $o->base_currency,
                'quote_currency' => $o->quote_currency,
                'amount' => (float) $o->amount,
                'filled_amount' => (float) $o->filled_amount,
                'price' => (float) ($o->price ?? 0),
                'fee' => (float) $o->fee,
                'total' => (float) ($o->total ?? 0),
                'status' => $o->status,
                'order_type' => $o->order_type,
                'created_at' => $o->created_at,
                'filled_at' => $o->filled_at,
            ]),
            'deposits' => $deposits->map(fn($d) => [
                'id' => $d->id,
                'reference' => $d->reference,
                'currency' => $d->cryptoCurrency->code,
                'amount' => (float) $d->amount,
                'net_amount' => (float) $d->net_amount,
                'status' => $d->status,
                'tx_hash' => $d->tx_hash,
                'confirmations' => $d->confirmations,
                'created_at' => $d->created_at,
            ]),
            'withdrawals' => $withdrawals->map(fn($w) => [
                'id' => $w->id,
                'reference' => $w->reference,
                'currency' => $w->cryptoCurrency->code,
                'amount' => (float) $w->amount,
                'net_amount' => (float) $w->net_amount,
                'to_address' => $w->to_address,
                'status' => $w->status,
                'created_at' => $w->created_at,
            ]),
            'currencies' => $currencies,
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'label' => $a->label,
                'number' => $a->account_number,
                'balance' => (float) $a->balance,
                'currency' => $a->currency,
            ]),
        ]);
    }

    private function cryptoCreateWallet(Request $request)
    {
        $validated = $request->validate([
            'currency_code' => 'required|string|exists:crypto_currencies,code',
            'label' => 'nullable|string|max:255',
        ]);

        $currency = CryptoCurrency::where('code', $validated['currency_code'])->firstOrFail();

        try {
            $this->cryptoService->createWallet($request->user(), $currency, $validated['label'] ?? null);
            return redirect()->route('banking.crypto')->with('success', 'Wallet created');
        } catch (\Exception $e) {
            return back()->withErrors(['currency_code' => $e->getMessage()]);
        }
    }

    private function cryptoPlaceOrder(Request $request)
    {
        $validated = $request->validate([
            'base_currency' => 'required|string|size:3',
            'quote_currency' => 'required|string|size:3',
            'side' => 'required|in:buy,sell',
            'amount' => 'required|numeric|min:0.00000001',
            'order_type' => 'nullable|in:market,limit,stop',
            'price' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->cryptoService->placeOrder($request->user(), $validated);
            return redirect()->route('banking.crypto')->with('success', 'Order placed');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    private function cryptoRequestWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:crypto_wallets,id',
            'amount' => 'required|numeric|min:0.00000001',
            'to_address' => 'required|string|max:255',
        ]);

        $wallet = CryptoWallet::findOrFail($validated['wallet_id']);

        try {
            $this->cryptoService->requestWithdrawal(
                $request->user(),
                $wallet,
                $validated['amount'],
                $validated['to_address'],
            );
            return redirect()->route('banking.crypto')->with('success', 'Withdrawal requested');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    private function cryptoRecordDeposit(Request $request)
    {
        $validated = $request->validate([
            'crypto_currency_id' => 'required|exists:crypto_currencies,id',
            'crypto_wallet_id' => 'required|exists:crypto_wallets,id',
            'amount' => 'required|numeric|min:0.00000001',
            'tx_hash' => 'nullable|string|max:255',
            'from_address' => 'nullable|string|max:255',
        ]);

        try {
            $deposit = $this->cryptoService->recordDeposit([
                'user_id' => $request->user()->id,
                'crypto_currency_id' => $validated['crypto_currency_id'],
                'crypto_wallet_id' => $validated['crypto_wallet_id'],
                'amount' => $validated['amount'],
                'tx_hash' => $validated['tx_hash'] ?? null,
                'from_address' => $validated['from_address'] ?? null,
            ]);
            $this->cryptoService->confirmDeposit($deposit);
            return redirect()->route('banking.crypto')->with('success', 'Deposit confirmed and wallet credited');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    private function cryptoBuyWithFiat(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'crypto_code' => 'required|string|exists:crypto_currencies,code',
            'amount' => 'required|numeric|min:1',
            'external_address' => 'nullable|string|max:255',
        ]);

        $account = Account::findOrFail($validated['account_id']);

        try {
            $result = $this->cryptoService->buyWithFiat(
                $request->user(),
                $account,
                $validated['crypto_code'],
                $validated['amount'],
                $validated['external_address'] ?? null,
            );

            $message = "Purchased {$result['crypto_amount']} {$result['crypto_code']} for €{$result['fiat_amount']}";

            if ($result['to_external']) {
                $message .= " and sent to external wallet";
            }

            return redirect()->route('banking.crypto')->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function beneficiaries(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'iban' => 'nullable|string|max:34',
                'bic' => 'nullable|string|max:11',
                'account_number' => 'nullable|string|max:50',
                'bank_name' => 'nullable|string|max:255',
                'bank_country' => 'nullable|string|size:2',
                'email' => 'nullable|email|max:255',
                'notes' => 'nullable|string|max:500',
            ]);

            $user->beneficiaries()->create($validated);

            return redirect()->route('banking.beneficiaries')->with('success', 'Beneficiary added');
        }

        $beneficiaries = $user->beneficiaries()->latest()->get();

        return Inertia::render('banking/beneficiaries', ['beneficiaries' => $beneficiaries]);
    }

    public function destroyBeneficiary(Request $request, Beneficiary $beneficiary)
    {
        if ($beneficiary->user_id !== $request->user()->id) {
            abort(403);
        }

        $beneficiary->delete();

        return redirect()->route('banking.beneficiaries')->with('success', 'Beneficiary removed');
    }

    public function standingOrders(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'debit_account_id' => 'required|exists:accounts,id',
                'beneficiary_id' => 'nullable|exists:account_beneficiaries,id',
                'beneficiary_iban' => 'nullable|string|max:34',
                'beneficiary_name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3',
                'frequency' => 'required|in:weekly,monthly,quarterly,yearly',
                'reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500',
                'starts_at' => 'required|date',
                'ends_at' => 'nullable|date|after:starts_at',
                'status' => 'nullable|in:active,paused',
            ]);

            $validated['user_id'] = $user->id;
            $validated['next_execution_at'] = $validated['starts_at'];
            $validated['status'] ??= 'active';

            StandingOrder::create($validated);

            return redirect()->route('banking.standing-orders')->with('success', 'Standing order created');
        }

        $orders = StandingOrder::where('user_id', $user->id)
            ->with('debitAccount')
            ->latest()
            ->get();

        $accounts = $user->accounts()->where('status', 'active')->get();

        return Inertia::render('banking/standing-orders', [
            'orders' => $orders,
            'accounts' => $accounts,
        ]);
    }

    public function toggleStandingOrder(StandingOrder $order, Request $request)
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        $order->update(['status' => $order->status === 'active' ? 'paused' : 'active']);

        return redirect()->route('banking.standing-orders')->with('success', 'Standing order updated');
    }

    public function destroyStandingOrder(StandingOrder $order, Request $request)
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        $order->delete();

        return redirect()->route('banking.standing-orders')->with('success', 'Standing order cancelled');
    }

    public function sepa(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $action = $request->input('action', 'credit_transfer');

            if ($action === 'direct_debit') {
                $validated = $request->validate([
                    'credit_account_id' => 'required|exists:accounts,id',
                    'amount' => 'required|numeric|min:0.01',
                    'debtor_name' => 'required|string|max:255',
                    'debtor_iban' => 'required|string|max:34',
                    'mandate_reference' => 'nullable|string|max:255',
                ]);

                $account = Account::findOrFail($validated['credit_account_id']);

                try {
                    $this->sepaService->createDirectDebit(
                        $account,
                        $validated['debtor_name'],
                        $validated['debtor_iban'],
                        $validated['amount'],
                        $validated['mandate_reference'] ?? null,
                    );

                    return redirect()->route('banking.sepa')->with('success', 'SEPA Direct Debit initiated');
                } catch (\Exception $e) {
                    return back()->withErrors(['amount' => $e->getMessage()]);
                }
            }

            $validated = $request->validate([
                'debit_account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:0.01',
                'creditor_name' => 'required|string|max:255',
                'creditor_iban' => 'required|string|max:34',
                'creditor_bic' => 'nullable|string|max:11',
                'remittance_info' => 'nullable|string|max:500',
            ]);

            $account = Account::findOrFail($validated['debit_account_id']);

            try {
                $this->sepaService->createCreditTransfer(
                    $account,
                    $validated['creditor_name'],
                    $validated['creditor_iban'],
                    $validated['creditor_bic'] ?? null,
                    $validated['amount'],
                    $validated['remittance_info'] ?? null,
                );

                return redirect()->route('banking.sepa')->with('success', 'SEPA transfer initiated');
            } catch (\Exception $e) {
                return back()->withErrors(['amount' => $e->getMessage()]);
            }
        }

        $accounts = $user->accounts()->where('status', 'active')->get();
        $transfers = SepaTransfer::whereHas('transaction', fn($q) => $q->where('user_id', $user->id))
            ->with('transaction')
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('banking/sepa', [
            'accounts' => $accounts,
            'transfers' => $transfers,
        ]);
    }

    public function swift(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'debit_account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:0.01',
                'beneficiary_name' => 'required|string|max:255',
                'beneficiary_account' => 'required|string|max:34',
                'beneficiary_bic' => 'required|string|max:11',
                'beneficiary_bank_name' => 'required|string|max:255',
                'beneficiary_address' => 'nullable|string|max:500',
                'beneficiary_bank_address' => 'nullable|string|max:500',
                'remittance_info' => 'nullable|string|max:500',
                'purpose_of_payment' => 'nullable|string|max:255',
            ]);

            $account = Account::findOrFail($validated['debit_account_id']);

            try {
                $this->swiftService->createInternationalTransfer(
                    $user,
                    $account,
                    $validated['beneficiary_name'],
                    $validated['beneficiary_account'],
                    $validated['beneficiary_bic'],
                    $validated['beneficiary_bank_name'],
                    $validated['amount'],
                    $validated['remittance_info'] ?? null,
                    $validated['beneficiary_address'] ?? null,
                    $validated['beneficiary_bank_address'] ?? null,
                    $validated['purpose_of_payment'] ?? null,
                );

                return redirect()->route('banking.swift')->with('success', 'SWIFT transfer initiated');
            } catch (\Exception $e) {
                return back()->withErrors(['amount' => $e->getMessage()]);
            }
        }

        $accounts = $user->accounts()->where('status', 'active')->get();
        $transfers = SwiftTransfer::whereHas('transaction', fn($q) => $q->where('user_id', $user->id))
            ->with('transaction')
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('banking/swift', [
            'accounts' => $accounts,
            'transfers' => $transfers,
        ]);
    }

    public function statements(Request $request)
    {
        $user = $request->user();

        $accountId = $request->input('account_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $accounts = $user->accounts()->get();

        if ($accountId) {
            $account = $user->accounts()->findOrFail($accountId);
            $statement = $this->reportService->accountStatement($account, $from, $to);

            return Inertia::render('banking/statements', [
                'accounts' => $accounts,
                'selected_account_id' => (int) $accountId,
                'from' => $from,
                'to' => $to,
                'statement' => $statement,
            ]);
        }

        return Inertia::render('banking/statements', [
            'accounts' => $accounts,
            'selected_account_id' => null,
            'from' => $from,
            'to' => $to,
            'statement' => null,
        ]);
    }

    public function downloadStatement(Account $account, Request $request)
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $account->load('user');
        $statement = $this->reportService->accountStatement($account, $from, $to);

        $html = view('pdfs.statement', [
            'account' => $account,
            'statement' => $statement,
        ])->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "statement-{$account->account_number}-{$from}-{$to}.pdf";

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function onboarding(Request $request)
    {
        $user = $request->user();
        $hasKyc = $user->kycVerification;
        $hasAccount = $user->accounts()->exists();
        $hasDeposit = Transaction::where('user_id', $user->id)->where('type', 'deposit')->where('status', 'completed')->exists();

        $steps = [
            ['key' => 'profile', 'label' => 'Complete Your Profile', 'completed' => (bool) $user->phone, 'href' => '/settings/profile'],
            ['key' => 'kyc', 'label' => 'Verify Your Identity (KYC)', 'completed' => $hasKyc && $hasKyc->status === 'approved', 'href' => '/banking/kyc'],
            ['key' => 'account', 'label' => 'Open a Bank Account', 'completed' => $hasAccount, 'href' => '/banking/accounts/create'],
            ['key' => 'deposit', 'label' => 'Make Your First Deposit', 'completed' => $hasDeposit, 'href' => '/banking/deposit'],
        ];

        $currentStep = 0;
        foreach ($steps as $i => $step) {
            if (!$step['completed']) { $currentStep = $i + 1; break; }
            $currentStep = $i + 2;
        }

        $steps = array_map(fn($s, $i) => array_merge($s, ['active' => ($i + 1) === $currentStep]), $steps, array_keys($steps));

        return Inertia::render('banking/onboarding', [
            'onboarding' => [
                'profile_complete' => (bool) $user->phone,
                'kyc_submitted' => (bool) $hasKyc,
                'kyc_approved' => $hasKyc && $hasKyc->status === 'approved',
                'has_account' => $hasAccount,
                'has_deposit' => $hasDeposit,
                'current_step' => $currentStep,
                'steps' => $steps,
            ],
        ]);
    }

    public function deposit(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:0.01',
                'method' => 'nullable|string|max:100',
                'reference' => 'nullable|string|max:255',
            ]);

            $account = Account::findOrFail($validated['account_id']);

            try {
                $this->transactionService->deposit(
                    $account,
                    $validated['amount'],
                    $validated['method'] ?? 'bank_transfer',
                    $validated['reference'] ?? null,
                );

                return redirect()->route('banking.deposit')->with('success', 'Deposit completed');
            } catch (\Exception $e) {
                return back()->withErrors(['amount' => $e->getMessage()]);
            }
        }

        return Inertia::render('banking/deposit', [
            'accounts' => $user->accounts()->where('status', 'active')->get()->map(fn($a) => [
                'id' => $a->id,
                'number' => $a->account_number,
                'label' => $a->label,
                'balance' => (float) $a->balance,
                'currency' => $a->currency,
            ]),
        ]);
    }

    public function loans(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:100',
                'interest_rate' => 'required|numeric|min:0.1|max:50',
                'term_months' => 'required|integer|min:1|max:120',
                'purpose' => 'nullable|string|max:500',
                'collateral_description' => 'nullable|string|max:1000',
                'collateral_value' => 'nullable|numeric|min:0',
            ]);

            $account = Account::findOrFail($validated['account_id']);

            try {
                $this->loanService->apply(
                    $user,
                    $account,
                    $validated['amount'],
                    $validated['interest_rate'],
                    $validated['term_months'],
                    $validated['purpose'] ?? null,
                    $validated['collateral_description'] ?? null,
                    $validated['collateral_value'] ?? null,
                );

                return redirect()->route('banking.loans')->with('success', 'Loan application submitted');
            } catch (\Exception $e) {
                return back()->withErrors(['amount' => $e->getMessage()]);
            }
        }

        $loans = Loan::where('user_id', $user->id)
            ->with('account')
            ->latest()
            ->paginate(20);

        return Inertia::render('banking/loans', [
            'loans' => $loans->through(fn($l) => [
                'id' => $l->id,
                'loan_number' => $l->loan_number,
                'amount' => (float) $l->amount,
                'interest_rate' => (float) $l->interest_rate,
                'term_months' => $l->term_months,
                'monthly_payment' => (float) $l->monthly_payment,
                'total_payable' => (float) $l->total_payable,
                'paid_amount' => (float) $l->paid_amount,
                'status' => $l->status,
                'purpose' => $l->purpose,
                'applied_at' => $l->applied_at,
                'account' => ['id' => $l->account->id, 'number' => $l->account->account_number, 'label' => $l->account->label],
            ]),
            'accounts' => $user->accounts()->where('status', 'active')->get()->map(fn($a) => [
                'id' => $a->id,
                'number' => $a->account_number,
                'label' => $a->label,
                'balance' => (float) $a->balance,
                'currency' => $a->currency,
            ]),
        ]);
    }

    public function showLoan(Loan $loan, Request $request)
    {
        if ($loan->user_id !== $request->user()->id) {
            abort(403);
        }

        $schedule = $this->loanService->getSchedule($loan);

        $accounts = $request->user()->accounts()
            ->where('status', 'active')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'number' => $a->account_number,
                'label' => $a->label,
                'balance' => (float) $a->balance,
                'currency' => $a->currency,
            ]);

        $loan->load('account');
        $loanData = [
            'id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'amount' => (float) $loan->amount,
            'interest_rate' => (float) $loan->interest_rate,
            'term_months' => $loan->term_months,
            'monthly_payment' => (float) $loan->monthly_payment,
            'total_payable' => (float) $loan->total_payable,
            'paid_amount' => (float) $loan->paid_amount,
            'status' => $loan->status,
            'purpose' => $loan->purpose,
            'collateral_description' => $loan->collateral_description,
            'collateral_value' => (float) ($loan->collateral_value ?? 0),
            'applied_at' => $loan->applied_at,
            'approved_at' => $loan->approved_at,
            'disbursed_at' => $loan->disbursed_at,
            'paid_at' => $loan->paid_at,
            'rejection_reason' => $loan->rejection_reason,
            'account' => ['id' => $loan->account->id, 'number' => $loan->account->account_number, 'label' => $loan->account->label],
        ];

        return Inertia::render('banking/loan-detail', [
            'loan' => $loanData,
            'schedule' => $schedule,
            'accounts' => $accounts,
        ]);
    }

    public function loanMakePayment(Loan $loan, Request $request)
    {
        if ($loan->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'installment_number' => 'nullable|integer|min:1',
        ]);

        $fromAccount = Account::findOrFail($validated['from_account_id']);

        if ($fromAccount->user_id !== $request->user()->id) {
            return back()->withErrors(['from_account_id' => 'You do not own this account']);
        }

        try {
            $this->loanService->makePayment(
                $loan,
                $fromAccount,
                $validated['installment_number'] ?? null,
            );

            return redirect()->route('banking.loans.show', $loan)->with('success', 'Payment made');
        } catch (\Exception $e) {
            return back()->withErrors(['from_account_id' => $e->getMessage()]);
        }
    }

    public function notifications(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $notificationId = $request->input('notification_id');

            if ($notificationId) {
                $notification = BankNotification::where('user_id', $user->id)
                    ->where('id', $notificationId)
                    ->firstOrFail();

                $notification->update(['read_at' => now(), 'status' => 'read']);
            } else {
                BankNotification::where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now(), 'status' => 'read']);
            }

            return redirect()->route('banking.notifications')->with('success', 'Notifications updated');
        }

        $notifications = BankNotification::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        $unreadCount = BankNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return Inertia::render('banking/notifications', [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }
}
