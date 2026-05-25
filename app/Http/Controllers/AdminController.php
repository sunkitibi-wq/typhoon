<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Banking\KycVerification;
use App\Models\Banking\MonitoringAlert;
use App\Models\Banking\Transaction;
use App\Models\Banking\Account;
use App\Models\Banking\ApiClient;
use App\Models\Banking\CryptoDeposit;
use App\Models\Banking\CryptoWithdrawal;
use App\Models\Banking\FeeSchedule;
use App\Models\Banking\Loan;
use App\Models\Banking\PlatformSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\ComplianceService;
use App\Services\CryptoExchangeService;
use App\Services\KycService;
use App\Services\LoanService;
use App\Services\ReportService;
use App\Models\Banking\KycDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly KycService $kycService,
        private readonly ComplianceService $complianceService,
        private readonly CryptoExchangeService $cryptoService,
        private readonly LoanService $loanService,
    ) {}

    public function dashboard()
    {
        $data = $this->reportService->adminDashboard();

        return Inertia::render('admin/dashboard', ['data' => $data]);
    }

    public function kyc()
    {
        $submissions = KycVerification::with('user', 'documents')
            ->latest()
            ->get()
            ->map(fn($k) => [
                'id' => $k->id,
                'user_id' => $k->user_id,
                'user_name' => $k->user->name,
                'user_email' => $k->user->email,
                'status' => $k->status,
                'kyc_level' => $k->kyc_level,
                'country' => $k->country,
                'date_of_birth' => $k->date_of_birth,
                'id_type' => $k->id_type,
                'id_number' => $k->id_number,
                'address_line1' => $k->address_line1,
                'city' => $k->city,
                'postal_code' => $k->postal_code,
                'documents_count' => $k->documents->count(),
                'created_at' => $k->created_at,
                'submitted_at' => $k->created_at,
                'verified_at' => $k->verified_at,
                'verification_details' => [
                    'user' => [
                        'id' => $k->user->id,
                        'name' => $k->user->name,
                        'email' => $k->user->email,
                        'phone' => $k->user->phone,
                    ],
                    'documents' => $k->documents->map(fn($doc) => [
                        'id' => $doc->id,
                        'document_type' => $doc->document_type,
                        'status' => $doc->status,
                        'created_at' => $doc->created_at,
                    ]),
                ],
            ]);

        $stats = [
            'pending' => KycVerification::where('status', 'pending')->count(),
            'approved' => KycVerification::where('status', 'approved')->count(),
            'rejected' => KycVerification::where('status', 'rejected')->count(),
            'total' => KycVerification::count(),
        ];

        return Inertia::render('admin/kyc-verification', [
            'submissions' => $submissions,
            'stats' => $stats,
        ]);
    }

    public function monitoring()
    {
        $alerts = MonitoringAlert::with('user', 'transaction')
            ->latest()
            ->get();

        return Inertia::render('admin/monitoring', [
            'alerts' => $alerts->map(fn($a) => [
                'id' => $a->id,
                'alert_type' => $a->alert_type,
                'severity' => $a->severity,
                'status' => $a->status,
                'description' => $a->description,
                'amount' => (float) ($a->amount ?? 0),
                'created_at' => $a->created_at,
            ]),
            'open_count' => $alerts->where('status', 'open')->count(),
        ]);
    }

    public function users()
    {
        $users = User::with('role', 'kycVerification', 'accounts')->latest()->paginate(20);

        return Inertia::render('admin/users', ['users' => $users]);
    }

    public function apiClients(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'scopes' => 'nullable|string',
            ]);

            $client = ApiClient::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'client_id' => strval($user->id) . '-' . Str::random(16),
                'client_secret' => Str::random(48),
                'scopes' => $validated['scopes'] ? explode(',', $validated['scopes']) : ['read'],
                'status' => 'active',
            ]);

            return redirect()->route('admin.api-clients')
                ->with('success', 'API client created')
                ->with('client_secret', $client->client_secret);
        }

        $clients = ApiClient::where('user_id', $user->id)->latest()->get();

        return Inertia::render('admin/api-clients', [
            'clients' => $clients,
            'flash' => session('client_secret') ? ['client_secret' => session('client_secret')] : null,
        ]);
    }

    public function revokeApiClient(Request $request, ApiClient $client)
    {
        if ($client->user_id !== $request->user()->id) {
            abort(403);
        }

        $client->update(['status' => 'revoked']);

        return redirect()->route('admin.api-clients')->with('success', 'API client revoked');
    }

    public function approveKyc(Request $request, KycVerification $kyc)
    {
        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        try {
            $this->kycService->approveVerification($kyc, $request->user(), $validated['notes'] ?? null);
            return redirect()->route('admin.kyc')->with('success', 'KYC approved');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function rejectKyc(Request $request, KycVerification $kyc)
    {
        $validated = $request->validate(['rejection_reason' => 'required|string|max:500']);

        try {
            $this->kycService->rejectVerification($kyc, $request->user(), $validated['rejection_reason']);
            return redirect()->route('admin.kyc')->with('success', 'KYC rejected');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function resolveAlert(Request $request, MonitoringAlert $alert)
    {
        $validated = $request->validate(['resolution' => 'nullable|string|max:1000']);

        try {
            $this->complianceService->resolveAlert($alert, $request->user(), $validated['resolution'] ?? null);
            return redirect()->route('admin.monitoring')->with('success', 'Alert resolved');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cryptoDeposits()
    {
        $deposits = CryptoDeposit::with('user', 'cryptoCurrency', 'cryptoWallet')
            ->latest()
            ->paginate(20);

        return Inertia::render('admin/crypto-deposits', [
            'deposits' => $deposits->through(fn($d) => [
                'id' => $d->id,
                'reference' => $d->reference,
                'user' => ['id' => $d->user->id, 'name' => $d->user->name, 'email' => $d->user->email],
                'currency' => $d->cryptoCurrency->code ?? '?',
                'amount' => (float) $d->amount,
                'net_amount' => (float) $d->net_amount,
                'status' => $d->status,
                'tx_hash' => $d->tx_hash,
                'confirmations' => $d->confirmations,
                'created_at' => $d->created_at,
            ]),
        ]);
    }

    public function confirmCryptoDeposit(CryptoDeposit $deposit)
    {
        try {
            $this->cryptoService->confirmDeposit($deposit);
            return redirect()->route('admin.crypto-deposits')->with('success', 'Deposit confirmed');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cryptoWithdrawals()
    {
        $withdrawals = CryptoWithdrawal::with('user', 'cryptoCurrency', 'cryptoWallet')
            ->latest()
            ->paginate(20);

        return Inertia::render('admin/crypto-withdrawals', [
            'withdrawals' => $withdrawals->through(fn($w) => [
                'id' => $w->id,
                'reference' => $w->reference,
                'user' => ['id' => $w->user->id, 'name' => $w->user->name, 'email' => $w->user->email],
                'currency' => $w->cryptoCurrency->code ?? '?',
                'amount' => (float) $w->amount,
                'net_amount' => (float) $w->net_amount,
                'to_address' => $w->to_address,
                'status' => $w->status,
                'created_at' => $w->created_at,
            ]),
        ]);
    }

    public function approveCryptoWithdrawal(CryptoWithdrawal $withdrawal)
    {
        try {
            $this->cryptoService->approveWithdrawal($withdrawal, request()->user());
            return redirect()->route('admin.crypto-withdrawals')->with('success', 'Withdrawal approved');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function userDetail(User $user)
    {
        $user->load('role', 'kycVerification', 'accounts.cryptoWallets');

        $portfolio = $this->reportService->userPortfolio($user);

        return Inertia::render('admin/user-detail', [
            'user' => $user,
            'portfolio' => $portfolio,
        ]);
    }

    // --- Fee Schedules ---

    public function feeSchedules()
    {
        $fees = FeeSchedule::latest()->get();

        return Inertia::render('admin/fee-schedules', ['fees' => $fees]);
    }

    public function createFeeSchedule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fee_type' => 'required|string|max:50',
            'calculation_method' => 'required|in:fixed,percentage,tiered',
            'fee_value' => 'required|numeric|min:0',
            'min_fee' => 'nullable|numeric|min:0',
            'max_fee' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] ??= true;
        $validated['currency'] ??= 'EUR';

        FeeSchedule::create($validated);

        return redirect()->route('admin.fee-schedules')->with('success', 'Fee schedule created');
    }

    public function toggleFeeSchedule(FeeSchedule $fee)
    {
        $fee->update(['is_active' => !$fee->is_active]);

        return redirect()->route('admin.fee-schedules')->with('success', 'Fee schedule updated');
    }

    // --- Platform Settings ---

    public function platformSettings()
    {
        $settings = PlatformSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return Inertia::render('admin/platform-settings', ['settings' => $settings]);
    }

    public function updatePlatformSettings(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            PlatformSetting::where('key', $key)->update(['value' => $value]);
        }

        return redirect()->route('admin.platform-settings')->with('success', 'Settings updated');
    }

    // --- Loan Management ---

    public function loans()
    {
        $loans = Loan::with('user', 'account')
            ->latest()
            ->paginate(20);

        return Inertia::render('admin/loans', [
            'loans' => $loans->through(fn($l) => [
                'id' => $l->id,
                'loan_number' => $l->loan_number,
                'user' => ['id' => $l->user->id, 'name' => $l->user->name, 'email' => $l->user->email],
                'amount' => (float) $l->amount,
                'interest_rate' => (float) $l->interest_rate,
                'term_months' => $l->term_months,
                'monthly_payment' => (float) $l->monthly_payment,
                'total_payable' => (float) $l->total_payable,
                'paid_amount' => (float) $l->paid_amount,
                'status' => $l->status,
                'purpose' => $l->purpose,
                'applied_at' => $l->applied_at,
                'approved_at' => $l->approved_at,
                'disbursed_at' => $l->disbursed_at,
            ]),
        ]);
    }

    public function showLoan(Loan $loan)
    {
        $loan->load('user', 'account', 'repayments', 'approvedBy');
        $schedule = $this->loanService->getSchedule($loan);

        return Inertia::render('admin/loan-detail', [
            'loan' => $loan,
            'schedule' => $schedule,
        ]);
    }

    public function underwriteLoan(Loan $loan)
    {
        try {
            $this->loanService->underwrite($loan, request()->user());
            return redirect()->route('admin.loans.show', $loan)->with('success', 'Loan moved to underwriting');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function approveLoan(Loan $loan)
    {
        try {
            $this->loanService->approve($loan, request()->user());
            return redirect()->route('admin.loans.show', $loan)->with('success', 'Loan approved');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function rejectLoan(Request $request, Loan $loan)
    {
        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        try {
            $this->loanService->reject($loan, request()->user(), $validated['reason']);
            return redirect()->route('admin.loans.show', $loan)->with('success', 'Loan rejected');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function disburseLoan(Loan $loan)
    {
        try {
            $this->loanService->disburse($loan, request()->user());
            return redirect()->route('admin.loans.show', $loan)->with('success', 'Loan disbursed');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // --- Audit Log ---

    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->input('user_id'), fn($q, $id) => $q->where('user_id', $id))
            ->when($request->input('route'), fn($q, $r) => $q->where('route_name', 'like', "%{$r}%"))
            ->latest()
            ->paginate(50);

        return Inertia::render('admin/audit-logs', ['logs' => $logs]);
    }

    // --- User Management ---

    public function createUser(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'phone' => 'nullable|string|max:20',
            ]);

            $validated['password'] = Hash::make($validated['password']);

            User::create($validated);

            return redirect()->route('admin.users')->with('success', 'User created');
        }

        $roles = Role::all();

        return Inertia::render('admin/user-form', [
            'roles' => $roles,
            'user' => null,
        ]);
    }

    public function editUser(Request $request, User $user)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'phone' => 'nullable|string|max:20',
                'status' => 'nullable|string|in:active,suspended,banned',
            ]);

            if ($validated['password'] ?? null) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            return redirect()->route('admin.users.detail', $user->id)->with('success', 'User updated');
        }

        $roles = Role::all();

        return Inertia::render('admin/user-form', [
            'roles' => $roles,
            'user' => $user,
        ]);
    }

    public function viewKycDocument(Request $request, KycDocument $document)
    {
        if (!$request->user()->isAdmin() && $request->user()->id !== $document->kycVerification->user_id) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($document->file_path);
    }

    public function freezeAccount(Account $account)
    {
        $account->update(['status' => 'frozen']);
        return back()->with('success', 'Account frozen successfully');
    }

    public function unfreezeAccount(Account $account)
    {
        $account->update(['status' => 'active']);
        return back()->with('success', 'Account unfrozen successfully');
    }

    public function closeAccount(Account $account)
    {
        $account->update(['status' => 'closed', 'closed_at' => now()]);
        return back()->with('success', 'Account closed successfully');
    }

    public function accounts(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search');

        $accounts = Account::with('user', 'accountType')
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhere('iban', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/accounts', [
            'accounts' => $accounts,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ]
        ]);
    }

    public function approveAccount(Account $account)
    {
        $account->update(['status' => 'active']);
        return back()->with('success', 'Account approved successfully');
    }

    public function rejectAccount(Account $account)
    {
        $account->update(['status' => 'rejected']);
        return back()->with('success', 'Account rejected successfully');
    }
}
