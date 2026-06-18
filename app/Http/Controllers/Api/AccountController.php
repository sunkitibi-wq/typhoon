<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Services\AccountService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly ReportService $reportService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $accounts = $request->user()->accounts()->with('accountType')->get();

        return response()->json([
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->accountType?->name,
                'number' => $a->account_number,
                'iban' => $a->iban,
                'currency' => $a->currency,
                'balance' => $a->balance,
                'available_balance' => $a->available_balance,
                'status' => $a->status,
                'label' => $a->label,
                'is_default' => $a->is_default,
            ]),
        ]);
    }

    public function show(Account $account, Request $request): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $account->load('accountType');

        return response()->json([
            'account' => [
                'id' => $account->id,
                'type' => $account->accountType?->name,
                'number' => $account->account_number,
                'iban' => $account->iban,
                'bic' => $account->swift_bic,
                'currency' => $account->currency,
                'balance' => $account->balance,
                'available_balance' => $account->available_balance,
                'ledger_balance' => $account->ledger_balance,
                'status' => $account->status,
                'label' => $account->label,
            ],
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_type_code' => 'required|string|exists:account_types,code',
            'currency' => 'nullable|string|size:3',
            'label' => 'nullable|string|max:255',
        ]);

        try {
            $account = $this->accountService->createAccount(
                $request->user(),
                $validated['account_type_code'],
                $validated['currency'] ?? 'EUR',
                $validated['label'] ?? null,
            );

            return response()->json([
                'message' => 'Account created successfully',
                'account' => [
                    'id' => $account->id,
                    'number' => $account->account_number,
                    'iban' => $account->iban,
                    'currency' => $account->currency,
                    'label' => $account->label,
                    'status' => $account->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function statement(Account $account, Request $request): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $statement = $this->reportService->accountStatement(
            $account,
            $validated['from'],
            $validated['to'],
            $validated['per_page'] ?? 50,
        );

        return response()->json($statement);
    }

    public function toggleDefault(Account $account, Request $request): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->user()->accounts()->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        return response()->json(['message' => 'Default account updated']);
    }

    public function getAccountTypes(): JsonResponse
    {
        $types = AccountType::where('is_active', true)->get(['id', 'code', 'name', 'description', 'currency', 'minimum_balance', 'monthly_fee', 'features']);

        return response()->json(['account_types' => $types]);
    }
}
