@extends('layouts.client')

@section('title', 'Client Portal Home')

@section('content')
    @parent

    <div class="mt-8 space-y-8">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-foreground">Welcome to Typhoon Banking</h2>
            <p class="text-muted-foreground mt-1 text-sm">Real-time balances and active operations for your retail banking portal.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Accounts List -->
            <div class="bg-card border border-border p-6 rounded-xl shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-border pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-foreground">My Accounts</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">List of active checking, savings, and virtual accounts.</p>
                    </div>
                    <a href="{{ route('banking.accounts.create') }}" class="text-xs font-semibold text-primary hover:text-primary-hover hover:underline transition">
                        + Open Account
                    </a>
                </div>

                @if($accounts->isEmpty())
                    <div class="text-center py-6">
                        <p class="text-sm text-muted-foreground">You don't have any accounts opened yet.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($accounts as $account)
                            <div class="p-4 rounded-lg border border-border bg-muted/10 flex items-center justify-between hover:bg-muted/20 transition duration-150">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-semibold text-sm text-foreground">{{ $account->label }}</span>
                                        <span class="text-xs text-muted-foreground">({{ $account->accountType?->name ?? $account->currency }})</span>
                                    </div>
                                    <p class="text-xs font-mono text-muted-foreground">IBAN: {{ $account->iban ?? 'N/A' }}</p>
                                </div>
                                <div class="text-right space-y-1">
                                    <p class="text-lg font-bold text-foreground">
                                        @if($account->currency === 'EUR') € @elseif($account->currency === 'USD') $ @else {{ $account->currency }} @endif{{ number_format($account->balance, 2) }}
                                    </p>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                                        @if($account->status === 'active') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                        @elseif($account->status === 'frozen') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                        @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                                        {{ ucfirst($account->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="pt-2">
                    <a href="{{ route('banking.dashboard') }}" class="text-sm font-semibold text-primary hover:text-primary-hover inline-flex items-center hover:underline transition">
                        Go to Interactive Accounts Dashboard &rarr;
                    </a>
                </div>
            </div>

            <!-- Recent Transactions List -->
            <div class="bg-card border border-border p-6 rounded-xl shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-border pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-foreground">Recent Transactions</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Your last 5 ledger operations.</p>
                    </div>
                    <a href="{{ route('banking.transfer') }}" class="text-xs font-semibold text-primary hover:text-primary-hover hover:underline transition">
                        Send Funds &rarr;
                    </a>
                </div>

                @if($transactions->isEmpty())
                    <div class="text-center py-6">
                        <p class="text-sm text-muted-foreground">No recent transactions recorded.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($transactions as $tx)
                            <div class="flex items-center justify-between text-sm py-1 border-b border-border/40 last:border-b-0 pb-3 last:pb-0">
                                <div class="space-y-1">
                                    <p class="font-medium text-foreground truncate max-w-[200px]">{{ $tx->description ?? ucfirst($tx->type) }}</p>
                                    <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                                        <span>{{ $tx->created_at->format('M d, Y') }}</span>
                                        <span>•</span>
                                        <span class="font-mono text-[10px]">{{ $tx->reference }}</span>
                                    </div>
                                </div>
                                <div class="text-right space-y-1">
                                    <p class="font-bold text-sm @if($tx->type === 'deposit') text-emerald-400 @else text-foreground @endif">
                                        {{ $tx->type === 'deposit' ? '+' : '-' }}@if($tx->currency === 'EUR')€@elseif($tx->currency === 'USD')$@else{{ $tx->currency }}@endif{{ number_format($tx->amount, 2) }}
                                    </p>
                                    <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[9px] font-semibold
                                        @if($tx->status === 'completed') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                        @elseif($tx->status === 'pending') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                        @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                                        {{ ucfirst($tx->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="pt-2">
                    <a href="{{ route('banking.transactions') }}" class="text-sm font-semibold text-primary hover:text-primary-hover inline-flex items-center hover:underline transition">
                        View All Transaction History &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
