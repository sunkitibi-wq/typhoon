@extends('layouts.client')

@section('title', 'Client Portal Home')

@section('content')
    @parent

    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-4 text-foreground">Welcome to your Typhoon Account</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-card border border-border p-6 rounded-xl shadow-sm">
                <h3 class="text-md font-bold mb-2">My Accounts</h3>
                <p class="text-sm text-muted-foreground mb-4">View your balances, download bank statements, and manage standing orders.</p>
                <a href="{{ route('banking.dashboard') }}" class="text-sm font-semibold text-primary hover:text-primary-hover">Go to Accounts Dashboard &rarr;</a>
            </div>

            <div class="bg-card border border-border p-6 rounded-xl shadow-sm">
                <h3 class="text-md font-bold mb-2">Transfers & Payments</h3>
                <p class="text-sm text-muted-foreground mb-4">Send local transfers, SEPA payments, or SWIFT international wire transfers.</p>
                <a href="{{ route('banking.transfer') }}" class="text-sm font-semibold text-primary hover:text-primary-hover">Initiate Transfer &rarr;</a>
            </div>
        </div>
    </div>
@endsection
