<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\CryptoDeposit;
use App\Models\Banking\CryptoWallet;
use App\Models\Banking\CryptoWithdrawal;
use App\Models\Banking\ExchangeRate;
use App\Services\CryptoExchangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoController extends Controller
{
    public function __construct(
        private readonly CryptoExchangeService $cryptoService,
    ) {}

    public function currencies(): JsonResponse
    {
        $currencies = CryptoCurrency::where('status', 'active')->get(['code', 'name', 'network', 'minimum_withdrawal', 'withdrawal_fee', 'minimum_deposit', 'icon_url']);

        return response()->json(['currencies' => $currencies]);
    }

    public function wallets(Request $request): JsonResponse
    {
        $wallets = $request->user()->cryptoWallets()->with('cryptoCurrency')->get();

        return response()->json([
            'wallets' => $wallets->map(fn($w) => [
                'id' => $w->id,
                'currency' => $w->cryptoCurrency->code,
                'name' => $w->cryptoCurrency->name,
                'network' => $w->cryptoCurrency->network,
                'address' => $w->address,
                'balance' => $w->balance,
                'locked_balance' => $w->locked_balance,
                'label' => $w->label,
            ]),
        ]);
    }

    public function createWallet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency_code' => 'required|string|exists:crypto_currencies,code',
            'label' => 'nullable|string|max:255',
        ]);

        $currency = CryptoCurrency::where('code', $validated['currency_code'])->firstOrFail();

        try {
            $wallet = $this->cryptoService->createWallet($request->user(), $currency, $validated['label'] ?? null);

            return response()->json([
                'message' => 'Wallet created successfully',
                'wallet' => [
                    'id' => $wallet->id,
                    'address' => $wallet->address,
                    'currency' => $currency->code,
                    'label' => $wallet->label,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function rates(): JsonResponse
    {
        $rates = ExchangeRate::latest('last_refreshed_at')->get();

        return response()->json([
            'rates' => $rates->map(fn($r) => [
                'pair' => "{$r->base_currency}/{$r->quote_currency}",
                'bid' => $r->bid,
                'ask' => $r->ask,
                'mid' => $r->mid_rate,
                'change_24h' => $r->change_24h,
                'volume_24h' => $r->volume_24h,
                'last_refreshed' => $r->last_refreshed_at,
            ]),
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        try {
            $quote = $this->cryptoService->getQuote($validated['from'], $validated['to'], $validated['amount']);

            return response()->json($quote);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base_currency' => 'required|string|size:3',
            'quote_currency' => 'required|string|size:3',
            'side' => 'required|in:buy,sell',
            'amount' => 'required|numeric|min:0.00000001',
            'order_type' => 'nullable|in:market,limit,stop',
            'price' => 'nullable|numeric|min:0',
            'stop_price' => 'nullable|numeric|min:0',
            'time_in_force' => 'nullable|in:GTC,IOC,FOK',
        ]);

        try {
            $order = $this->cryptoService->placeOrder($request->user(), $validated);

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->only(['order_number', 'side', 'base_currency', 'quote_currency', 'amount', 'price', 'status', 'filled_amount']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = $request->user()->cryptoOrders()->latest()->paginate($request->per_page ?? 20);

        return response()->json($orders);
    }

    public function deposits(Request $request): JsonResponse
    {
        $deposits = CryptoDeposit::where('user_id', $request->user()->id)
            ->with('cryptoCurrency')
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json(['deposits' => $deposits]);
    }

    public function recordDeposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'crypto_currency_id' => 'required|exists:crypto_currencies,id',
            'crypto_wallet_id' => 'required|exists:crypto_wallets,id',
            'amount' => 'required|numeric|min:0.00000001',
            'tx_hash' => 'nullable|string|max:255',
            'from_address' => 'nullable|string|max:255',
        ]);

        try {
            $deposit = $this->cryptoService->recordDeposit(array_merge($validated, [
                'user_id' => $request->user()->id,
            ]));

            return response()->json([
                'message' => 'Deposit recorded',
                'deposit' => $deposit->only(['reference', 'amount', 'status']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function withdrawals(Request $request): JsonResponse
    {
        $withdrawals = CryptoWithdrawal::where('user_id', $request->user()->id)
            ->with('cryptoCurrency')
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json(['withdrawals' => $withdrawals]);
    }

    public function requestWithdrawal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:crypto_wallets,id',
            'amount' => 'required|numeric|min:0.00000001',
            'to_address' => 'required|string|max:255',
        ]);

        $wallet = CryptoWallet::findOrFail($validated['wallet_id']);

        try {
            $withdrawal = $this->cryptoService->requestWithdrawal(
                $request->user(),
                $wallet,
                $validated['amount'],
                $validated['to_address'],
            );

            return response()->json([
                'message' => 'Withdrawal requested',
                'withdrawal' => $withdrawal->only(['reference', 'amount', 'status']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function sendTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:crypto_wallets,id',
            'amount' => 'required|numeric|min:0.00000001',
            'to_address' => 'required|string|max:255',
            'gas_limit' => 'nullable|integer|min:21000',
            'gas_price' => 'nullable|string|max:255',
        ]);

        $wallet = CryptoWallet::findOrFail($validated['wallet_id']);

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $withdrawal = $this->cryptoService->sendCrypto(
                $request->user(),
                $wallet,
                $validated['amount'],
                $validated['to_address'],
                $validated['gas_limit'] ?? null,
                $validated['gas_price'] ?? null,
            );

            return response()->json([
                'message' => 'Transaction submitted',
                'withdrawal' => $withdrawal->only(['reference', 'tx_hash', 'status']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function confirmDeposit(Request $request, CryptoDeposit $deposit): JsonResponse
    {
        if ($deposit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $this->cryptoService->confirmDeposit($deposit);

            return response()->json(['message' => 'Deposit confirmed']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
