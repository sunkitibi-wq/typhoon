<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\PosTerminal;
use App\Services\OraclePosIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OraclePosController extends Controller
{
    public function __construct(
        private readonly OraclePosIntegrationService $integrationService,
    ) {}

    public function configureTerminal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'terminal_id' => 'required|integer|exists:pos_terminals,id',
            'oracle_terminal_id' => 'required|string|max:50',
            'crypto_processor_enabled' => 'required|boolean',
            'default_crypto_currency' => 'required|string|in:BTC,ETH,USDT,USDC',
            'settlement_mode' => 'required|string|in:fiat,crypto',
            'oracle_api_url' => 'nullable|url|max:255',
            'oracle_api_key' => 'nullable|string|max:255',
        ]);

        $terminal = PosTerminal::findOrFail($validated['terminal_id']);

        if ($terminal->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $updatedTerminal = $this->integrationService->configureTerminal($terminal, $validated);

            return response()->json([
                'message' => 'Oracle POS and Crypto configuration updated successfully.',
                'terminal' => $updatedTerminal,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function charge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'oracle_terminal_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'preferred_crypto' => 'nullable|string|in:BTC,ETH,USDT,USDC',
        ]);

        try {
            $response = $this->integrationService->createOracleCharge(
                $validated['oracle_terminal_id'],
                (float) $validated['amount'],
                $validated['currency'],
                $validated['preferred_crypto'] ?? null
            );

            return response()->json($response, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function status(string $reference): JsonResponse
    {
        try {
            $status = $this->integrationService->checkChargeStatus($reference);
            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function simulatePayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'tx_hash' => 'required|string',
        ]);

        try {
            $posTransaction = $this->integrationService->simulateCryptoPayment(
                $validated['reference'],
                $validated['tx_hash']
            );

            return response()->json([
                'message' => 'Crypto payment simulation successful.',
                'pos_transaction' => $posTransaction->load('posTerminal'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
