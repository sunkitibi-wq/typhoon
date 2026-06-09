import { Head } from '@inertiajs/react';
import { Users, CreditCard, TrendingUp, AlertTriangle, Activity } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface AdminDashboardData {
    total_users: number;
    active_accounts: number;
    total_volume_today: number;
    total_volume_month: number;
    pending_kyc: number;
    pending_withdrawals: number;
    open_alerts: number;
    recent_transactions: Array<{
        id: number;
        reference: string;
        type: string;
        amount: number;
        status: string;
        created_at: string;
    }>;
}

export default function AdminDashboard({ data }: { data: AdminDashboardData }) {
    return (
        <>
            <Head title="Admin Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 min-w-0">
                <h2 className="text-2xl font-bold tracking-tight">Admin Overview</h2>
                <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                            <Users className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{data.total_users}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Active Accounts</CardTitle>
                            <CreditCard className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{data.active_accounts}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Volume Today</CardTitle>
                            <TrendingUp className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">&euro;{data.total_volume_today.toLocaleString()}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Volume This Month</CardTitle>
                            <Activity className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">&euro;{data.total_volume_month.toLocaleString()}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Pending KYC</CardTitle>
                            <Users className="size-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-amber-500">{data.pending_kyc}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Open Alerts</CardTitle>
                            <AlertTriangle className="size-4 text-rose-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-rose-500">{data.open_alerts}</div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Transactions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {data.recent_transactions.map(tx => (
                                <div key={tx.id} className="flex flex-col sm:flex-row sm:items-center justify-between rounded-lg border p-3 gap-2 sm:gap-4 min-w-0">
                                    <div className="min-w-0">
                                        <p className="text-sm font-medium font-mono truncate">{tx.reference}</p>
                                        <p className="text-xs text-muted-foreground">{new Date(tx.created_at).toLocaleString()}</p>
                                    </div>
                                    <div className="flex items-center gap-3 justify-between sm:justify-end">
                                        <span className="text-sm font-bold">&euro;{tx.amount.toLocaleString()}</span>
                                        <Badge variant={tx.status === 'completed' ? 'secondary' : 'outline'}>{tx.status}</Badge>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/dashboard' },
    ],
};
