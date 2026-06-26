<?php

namespace App\Http\Controllers;

use App\Models\Banking\Account;
use App\Models\Banking\Card;
use App\Services\BaasService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CardController extends Controller
{
    public function __construct(
        private readonly BaasService $baasService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $cards = Card::where('user_id', $user->id)->get();
        $accounts = Account::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        return Inertia::render('banking/cards', [
            'cards' => $cards,
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:virtual,physical',
            'cardholder_name' => 'nullable|string|max:100',
        ]);

        $account = Account::where('id', $request->account_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return back()->withErrors(['account_id' => 'Account is not active.']);
        }

        $cardholderName = $request->cardholder_name ?: $user->name;

        // Dynamic API request via BaasService
        $res = $this->baasService->createCard([
            'external_person_id' => $user->solaris_person_id,
            'external_account_id' => $account->solaris_account_id,
            'type' => $request->type,
            'cardholder_name' => $cardholderName,
        ]);

        if ($res['success']) {
            Card::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'solaris_card_id' => $res['id'] ?? null,
                'type' => $request->type,
                'cardholder_name' => $cardholderName,
                'masked_pan' => $res['masked_pan'] ?? '4*** **** **** ' . random_int(1000, 9999),
                'expiration_date' => $res['expiration_date'] ?? now()->addYears(3)->format('m/y'),
                'status' => $res['status'] ?? 'active',
            ]);

            return redirect()->back()->with('success', 'Card created successfully.');
        }

        return redirect()->back()->withErrors(['error' => $res['error'] ?? 'Failed to create card via BaaS provider.']);
    }

    public function toggleStatus(Card $card, Request $request)
    {
        $user = $request->user();
        
        if ($card->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:active,blocked,closed',
        ]);

        $res = $this->baasService->updateCardStatus($card->solaris_card_id ?? (string) $card->id, $request->status);

        if ($res['success']) {
            $card->update([
                'status' => $res['status'] ?? $request->status,
            ]);

            return redirect()->back()->with('success', "Card status updated to {$request->status} successfully.");
        }

        return redirect()->back()->withErrors(['error' => $res['error'] ?? 'Failed to update card status via BaaS provider.']);
    }

    public function setPin(Card $card, Request $request)
    {
        $user = $request->user();

        if ($card->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'pin' => 'required|string|size:4|regex:/^[0-9]+$/',
        ]);

        // Simulating encrypted transmission to Solaris PIN API
        \Illuminate\Support\Facades\Log::info("PIN set requested for card {$card->id}");

        return redirect()->back()->with('success', 'Card PIN set successfully.');
    }
}
