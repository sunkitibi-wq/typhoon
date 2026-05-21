import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, ArrowDownRight, Wallet, CreditCard, TrendingUp, ArrowLeftRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { dashboard as dashboardRoute } from '@/routes';

interface Account {
    id: number;
    number: string;
    iban: string;
    type: string;
    currency: string;
    balance: number;
    available_balance: number;
    status: string;
    label: string;
    is_default: boolean;
}

interface Transaction {
    id: number;
    reference: string;
    type: string;
    amount: number;
    currency: string;
    description: string;
    status: string;
    created_at: string;
}

interface DashboardData {
    accounts: Account[];
    recent_transactions: Transaction[];
    total_balance: number;
    monthly_spending: number;
    monthly_income: number;
}

export default function BankingDashboard({ accounts, recent_transactions, total_balance, monthly_spending, monthly_income }: DashboardData) {
    return (
        <>
            <Head title="Banking Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Balance</CardTitle>
                            <Wallet className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">&euro;{total_balance.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">Across {accounts.length} accounts</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Monthly Income</CardTitle>
                            <ArrowUpRight className="size-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-500">&euro;{monthly_income.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">This month</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Monthly Spending</CardTitle>
                            <ArrowDownRight className="size-4 text-rose-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-rose-500">&euro;{monthly_spending.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">This month</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Accounts</CardTitle>
                            <CreditCard className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{accounts.length}</div>
                            <p className="text-xs text-muted-foreground">
                                {accounts.filter(a => a.status === 'active').length} active
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Your Accounts</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {accounts.map(account => (
                                <Link key={account.id} href={`/banking/accounts/${account.id}`} className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-accent">
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-full bg-primary/10">
                                            <CreditCard className="size-5 text-primary" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium leading-none">{account.label}</p>
                                            <p className="text-xs text-muted-foreground">****{account.number.slice(-4)}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-bold">&euro;{account.balance.toLocaleString()}</p>
                                        <Badge variant={account.status === 'active' ? 'secondary' : 'outline'} className="text-[10px]">
                                            {account.status}
                                        </Badge>
                                    </div>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Transactions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {recent_transactions.slice(0, 5).map(tx => (
                                <div key={tx.id} className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className={`flex size-8 items-center justify-center rounded-full ${
                                            tx.type === 'deposit' ? 'bg-emerald-100 dark:bg-emerald-900/20' :
                                            tx.type === 'withdrawal' ? 'bg-rose-100 dark:bg-rose-900/20' :
                                            'bg-blue-100 dark:bg-blue-900/20'
                                        }`}>
                                            {tx.type === 'deposit' ? <ArrowUpRight className="size-4 text-emerald-600" /> :
                                             tx.type === 'withdrawal' ? <ArrowDownRight className="size-4 text-rose-600" /> :
                                             <ArrowLeftRight className="size-4 text-blue-600" />}
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium leading-none">{tx.description || tx.type}</p>
                                            <p className="text-xs text-muted-foreground">{new Date(tx.created_at).toLocaleDateString()}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className={`text-sm font-bold ${
                                            tx.type === 'deposit' ? 'text-emerald-600' :
                                            tx.type === 'withdrawal' ? 'text-rose-600' : ''
                                        }`}>
                                            {tx.type === 'deposit' ? '+' : '-'}&euro;{tx.amount.toLocaleString()}
                                        </p>
                                        <Badge variant={tx.status === 'completed' ? 'secondary' : 'outline'} className="text-[10px]">
                                            {tx.status}
                                        </Badge>
                                    </div>
                                </div>
                            ))}
                            {recent_transactions.length === 0 && (
                                <p className="text-sm text-muted-foreground">No transactions yet</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

BankingDashboard.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
    ],
};
