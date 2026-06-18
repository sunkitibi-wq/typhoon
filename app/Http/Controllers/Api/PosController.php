<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Account;
use App\Models\Banking\PosTerminal;
use App\Models\Banking\PosTransaction;
use App\Services\PosGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        private readonly PosGatewayService $posGatewayService,
    ) {}

    public function processTerminalPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|exists:pos_terminals,serial_number',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'card_brand' => 'nullable|string|max:50',
            'card_last4' => 'nullable|string|size:4',
            'payment_method' => 'nullable|string|in:contactless,dip,swipe,manual',
        ]);

        $terminal = PosTerminal::where('serial_number', $validated['serial_number'])->firstOrFail();

        // Ensure the terminal belongs to the authenticated user/merchant
        if ($terminal->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $posTransaction = $this->posGatewayService->processPayment(
                $validated['serial_number'],
                (float) $validated['amount'],
                $validated['currency'],
                [
                    'card_brand' => $validated['card_brand'] ?? 'Visa',
                    'card_last4' => $validated['card_last4'] ?? '4242',
                    'payment_method' => $validated['payment_method'] ?? 'contactless',
                ]
            );

            return response()->json([
                'message' => 'Payment authorized and completed successfully',
                'pos_transaction' => $posTransaction->load('posTerminal'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function processTerminalRefund(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'terminal_reference' => 'required|string|exists:pos_transactions,terminal_reference',
        ]);

        $posTransaction = PosTransaction::where('terminal_reference', $validated['terminal_reference'])->firstOrFail();

        // Ensure the transaction's terminal belongs to the authenticated user/merchant
        $terminal = $posTransaction->posTerminal;
        if ($terminal && $terminal->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $posTransaction = $this->posGatewayService->refundPayment($posTransaction, $request->user());

            return response()->json([
                'message' => 'POS Transaction refunded successfully',
                'pos_transaction' => $posTransaction,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function indexTerminals(Request $request): JsonResponse
    {
        $terminals = PosTerminal::where('user_id', $request->user()->id)->latest()->get();
        return response()->json([
            'terminals' => $terminals->map(fn($t) => [
                'id' => $t->id,
                'user_id' => $t->user_id,
                'account_id' => $t->account_id,
                'serial_number' => $t->serial_number,
                'label' => $t->label,
                'device_model' => $t->model,
                'active' => $t->status === 'active',
                'last_active_at' => $t->last_active_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function pairTerminal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'serial_number' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
            'pairing_code' => 'nullable|string|max:20',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $data = $validated;
            $data['model'] = $validated['device_model'] ?? null;
            unset($data['device_model']);

            $terminal = $this->posGatewayService->pairTerminal($request->user(), $account, $data);

            return response()->json([
                'message' => 'Terminal paired successfully',
                'terminal' => [
                    'id' => $terminal->id,
                    'user_id' => $terminal->user_id,
                    'account_id' => $terminal->account_id,
                    'serial_number' => $terminal->serial_number,
                    'label' => $terminal->label,
                    'device_model' => $terminal->model,
                    'active' => $terminal->status === 'active',
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function deleteTerminal(PosTerminal $terminal, Request $request): JsonResponse
    {
        if ($terminal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $terminal->delete();

        return response()->json(['message' => 'Terminal deleted successfully']);
    }

    public function toggleTerminal(PosTerminal $terminal, Request $request): JsonResponse
    {
        if ($terminal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $terminal->update([
            'status' => $terminal->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json(['message' => 'Terminal status toggled successfully']);
    }

    public function indexTransactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $transactions = PosTransaction::whereHas('posTerminal', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with('posTerminal')
        ->latest()
        ->get();

        return response()->json([
            'transactions' => $transactions->map(fn($tx) => [
                'id' => $tx->id,
                'card_brand' => $tx->card_brand,
                'masked_number' => '****' . $tx->card_last4,
                'reference' => $tx->terminal_reference,
                'device_serial' => $tx->posTerminal?->serial_number,
                'amount' => (float) $tx->amount,
                'method' => $tx->payment_method,
                'status' => $tx->status,
                'refundable' => $tx->status === 'completed',
                'created_at' => $tx->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function refundTransaction(PosTransaction $posTransaction, Request $request): JsonResponse
    {
        $terminal = $posTransaction->posTerminal;
        if ($terminal && $terminal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $refundedTx = $this->posGatewayService->refundPayment($posTransaction, $request->user());

            return response()->json([
                'message' => 'POS Transaction refunded successfully',
                'pos_transaction' => $refundedTx,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
