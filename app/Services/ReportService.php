<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function accountStatement(Account $account, string $from, string $to, int $perPage = 50): array
    {
        $transactions = Transaction::where(function ($q) use ($account) {
            $q->where('debit_account_id', $account->id)
                ->orWhere('credit_account_id', $account->id);
        })
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $openingBalance = $this->getBalanceAt($account, $from);
        $closingBalance = $this->getBalanceAt($account, $to);

        return [
            'account' => $account->only(['account_number', 'iban', 'currency', 'label']),
            'period' => ['from' => $from, 'to' => $to],
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_debits' => $transactions->sum(fn($t) => $t->debit_account_id === $account->id ? $t->net_amount : 0),
            'total_credits' => $transactions->sum(fn($t) => $t->credit_account_id === $account->id ? $t->net_amount : 0),
            'transaction_count' => $transactions->total(),
            'transactions' => $transactions,
        ];
    }

    public function userPortfolio(User $user): array
    {
        $accounts = $user->accounts()->with('accountType')->get();
        $cryptoWallets = $user->cryptoWallets()->with('cryptoCurrency')->get();

        $fiatTotal = $accounts->sum('balance');
        $cryptoTotal = 0;

        $cryptoBreakdown = $cryptoWallets->map(function ($wallet) use (&$cryptoTotal) {
            $rate = \App\Models\Banking\ExchangeRate::where('base_currency', $wallet->cryptoCurrency->code)
                ->where('quote_currency', 'EUR')
                ->first();
            $eurValue = $rate ? $wallet->balance * $rate->mid_rate : 0;
            $cryptoTotal += $eurValue;

            return [
                'currency' => $wallet->cryptoCurrency->code,
                'name' => $wallet->cryptoCurrency->name,
                'balance' => $wallet->balance,
                'eur_value' => round($eurValue, 2),
                'address' => $wallet->address,
            ];
        });

        return [
            'total_portfolio_value_eur' => round($fiatTotal + $cryptoTotal, 2),
            'fiat_total_eur' => round($fiatTotal, 2),
            'crypto_total_eur' => round($cryptoTotal, 2),
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->accountType->name,
                'number' => $a->account_number,
                'currency' => $a->currency,
                'balance' => $a->balance,
                'label' => $a->label,
            ]),
            'crypto_wallets' => $cryptoBreakdown,
        ];
    }

    public function adminDashboard(): array
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        return [
            'total_users' => User::count(),
            'active_accounts' => Account::where('status', 'active')->count(),
            'total_volume_today' => Transaction::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->sum('net_amount'),
            'total_volume_month' => Transaction::where('status', 'completed')
                ->where('created_at', '>=', $monthStart)
                ->sum('net_amount'),
            'pending_kyc' => \App\Models\Banking\KycVerification::where('status', 'pending')->count(),
            'pending_withdrawals' => \App\Models\Banking\CryptoWithdrawal::where('status', 'pending')->count(),
            'open_alerts' => \App\Models\Banking\MonitoringAlert::where('status', 'open')->count(),
            'recent_transactions' => Transaction::with(['debitAccount', 'creditAccount'])
                ->latest()
                ->take(10)
                ->get()
                ->toArray(),
        ];
    }

    private function getBalanceAt(Account $account, string $date): float
    {
        $totalCredits = Transaction::where('credit_account_id', $account->id)
            ->where('status', 'completed')
            ->where('created_at', '<', $date)
            ->sum('net_amount');

        $totalDebits = Transaction::where('debit_account_id', $account->id)
            ->where('status', 'completed')
            ->where('created_at', '<', $date)
            ->sum(DB::raw('net_amount + fee'));

        return $totalCredits - $totalDebits;
    }
}
