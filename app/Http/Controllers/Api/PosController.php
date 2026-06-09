<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
