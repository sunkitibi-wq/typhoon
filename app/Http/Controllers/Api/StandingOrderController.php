<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Account;
use App\Models\Banking\StandingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandingOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = StandingOrder::where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'standing_orders' => $orders->map(fn($o) => [
                'id' => $o->id,
                'user_id' => $o->user_id,
                'debit_account_id' => $o->debit_account_id,
                'beneficiary_name' => $o->beneficiary_name,
                'beneficiary_iban' => $o->beneficiary_iban,
                'amount' => (float) $o->amount,
                'currency' => $o->currency,
                'frequency' => $o->frequency,
                'start_date' => $o->starts_at?->toDateString(),
                'end_date' => $o->ends_at?->toDateString(),
                'reference' => $o->reference,
                'notes' => $o->notes,
                'active' => $o->status === 'active',
                'next_execution_date' => $o->next_execution_at?->toIso8601String(),
                'last_execution_date' => $o->last_executed_at?->toIso8601String(),
                'created_at' => $o->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'debit_account_id' => 'required|exists:accounts,id',
            'beneficiary_name' => 'required|string|max:255',
            'beneficiary_iban' => 'required|string|max:34',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'frequency' => 'required|in:weekly,monthly,quarterly,yearly',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $account = Account::findOrFail($validated['debit_account_id']);
        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order = StandingOrder::create([
            'user_id' => $user->id,
            'debit_account_id' => $validated['debit_account_id'],
            'beneficiary_name' => $validated['beneficiary_name'],
            'beneficiary_iban' => $validated['beneficiary_iban'],
            'amount' => (float) $validated['amount'],
            'currency' => $validated['currency'],
            'frequency' => $validated['frequency'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'starts_at' => $validated['start_date'],
            'ends_at' => $validated['end_date'] ?? null,
            'next_execution_at' => $validated['start_date'],
            'status' => 'active',
        ]);

        return response()->json([
            'id' => $order->id,
            'user_id' => $order->user_id,
            'debit_account_id' => $order->debit_account_id,
            'beneficiary_name' => $order->beneficiary_name,
            'beneficiary_iban' => $order->beneficiary_iban,
            'amount' => (float) $order->amount,
            'currency' => $order->currency,
            'frequency' => $order->frequency,
            'start_date' => $order->starts_at?->toDateString(),
            'end_date' => $order->ends_at?->toDateString(),
            'reference' => $order->reference,
            'notes' => $order->notes,
            'active' => $order->status === 'active',
            'next_execution_date' => $order->next_execution_at?->toIso8601String(),
            'last_execution_date' => $order->last_executed_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
        ], 201);
    }

    public function toggle(StandingOrder $order, Request $request): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order->update([
            'status' => $order->status === 'active' ? 'paused' : 'active',
        ]);

        return response()->json(['message' => 'Standing order status updated successfully']);
    }

    public function destroy(StandingOrder $order, Request $request): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order->delete();

        return response()->json(['message' => 'Standing order cancelled successfully']);
    }
}
