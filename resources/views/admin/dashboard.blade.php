@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    @parent

    <div class="mt-8 space-y-8">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-foreground">Platform Control Panel</h2>
            <p class="text-muted-foreground mt-1 text-sm">Traditional Blade Layout rendering actual platform statistics and compliance queues.</p>
        </div>

        <!-- Metric Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Users</p>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-extrabold text-foreground">{{ $data['total_users'] }}</span>
                    <a href="/admin/users" class="text-[11px] font-medium text-primary hover:underline">View All &rarr;</a>
                </div>
            </div>

            <div class="bg-card border border-border p-5 rounded-xl shadow-sm space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Active Accounts</p>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-extrabold text-foreground">{{ $data['active_accounts'] }}</span>
                    <a href="/admin/accounts" class="text-[11px] font-medium text-primary hover:underline">Manage &rarr;</a>
                </div>
            </div>

            <div class="bg-card border border-border p-5 rounded-xl shadow-sm space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Pending KYC</p>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-extrabold text-foreground">{{ $data['pending_kyc'] }}</span>
                    <a href="/admin/kyc" class="text-[11px] font-medium text-primary hover:underline">Review Queue &rarr;</a>
                </div>
            </div>

            <div class="bg-card border border-border p-5 rounded-xl shadow-sm space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Compliance Alerts</p>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-extrabold text-foreground">{{ $data['open_alerts'] }}</span>
                    <a href="/admin/monitoring" class="text-[11px] font-medium text-primary hover:underline">Investigate &rarr;</a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Pending KYC Verification Queue -->
            <div class="bg-card border border-border p-6 rounded-xl shadow-sm space-y-6">
                <div class="border-b border-border pb-4">
                    <h3 class="text-lg font-bold text-foreground">Pending KYC Queue</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Sanctions check and identity documentation review queue.</p>
                </div>

                @if($pendingKycs->isEmpty())
                    <div class="text-center py-6">
                        <p class="text-sm text-muted-foreground">No pending identity verifications in queue.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-border text-xs text-muted-foreground uppercase">
                                    <th class="py-2">User</th>
                                    <th class="py-2">Country</th>
                                    <th class="py-2">Submitted</th>
                                    <th class="py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/40 text-sm text-foreground">
                                @foreach($pendingKycs as $kyc)
                                    <tr>
                                        <td class="py-3">
                                            <div class="font-medium">{{ $kyc->user?->name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ $kyc->user?->email }}</div>
                                        </td>
                                        <td class="py-3 font-mono text-xs uppercase">{{ $kyc->country }}</td>
                                        <td class="py-3 text-xs text-muted-foreground">{{ $kyc->created_at->diffForHumans() }}</td>
                                        <td class="py-3 text-right">
                                            <a href="/admin/kyc" class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-semibold bg-primary hover:bg-primary-hover text-primary-foreground rounded transition">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Recent Compliance / AML Alerts -->
            <div class="bg-card border border-border p-6 rounded-xl shadow-sm space-y-6">
                <div class="border-b border-border pb-4">
                    <h3 class="text-lg font-bold text-foreground">Recent Compliance Alerts</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Flagged transactions requiring immediate compliance officer triage.</p>
                </div>

                @if($recentAlerts->isEmpty())
                    <div class="text-center py-6">
                        <p class="text-sm text-muted-foreground">No compliance alerts generated recently.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($recentAlerts as $alert)
                            <div class="p-4 rounded-lg border border-border bg-muted/10 flex items-start justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider
                                            @if($alert->severity === 'high') bg-rose-500/10 text-rose-400 border border-rose-500/20
                                            @elseif($alert->severity === 'medium') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                            @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                                            {{ ucfirst($alert->severity) }}
                                        </span>
                                        <span class="font-semibold text-xs text-foreground">{{ $alert->alert_type }}</span>
                                    </div>
                                    <p class="text-xs text-muted-foreground">{{ $alert->description }}</p>
                                    @if($alert->transaction)
                                        <p class="text-[11px] text-muted-foreground font-mono">
                                            TX Amount: €{{ number_format($alert->transaction->amount, 2) }} (Ref: {{ $alert->transaction->reference }})
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right space-y-1">
                                    <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[9px] font-semibold capitalize
                                        @if($alert->status === 'open') bg-rose-500/10 text-rose-400 border border-rose-500/20
                                        @else bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 @endif">
                                        {{ ucfirst($alert->status) }}
                                    </span>
                                    <div>
                                        <a href="/admin/monitoring" class="text-[10px] font-semibold text-primary hover:underline block mt-2">
                                            Investigate &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
