import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowUpRight, ArrowDownRight, Wallet, CreditCard, TrendingUp, ArrowLeftRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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

interface ExchangeRate {
    base: string;
    quote: string;
    rate: number;
}

interface DashboardData {
    accounts: Account[];
    recent_transactions: Transaction[];
    total_balance: number;
    monthly_spending: number;
    monthly_income: number;
    exchange_rates?: ExchangeRate[];
}

const getCurrencySymbol = (currency: string) => {
    switch (currency) {
        case 'EUR': return '€';
        case 'USD': return '$';
        case 'GBP': return '£';
        case 'CHF': return 'CHF';
        default: return currency;
    }
};

export default function BankingDashboard({ accounts, recent_transactions, total_balance, monthly_spending, monthly_income, exchange_rates = [] }: DashboardData) {
    const [displayCurrency, setDisplayCurrency] = useState<'EUR' | 'USD' | 'GBP'>('EUR');

    const convertAmount = (amount: number, fromCurrency: string, targetCurrency: string) => {
        if (fromCurrency === targetCurrency) return amount;

        const rateObj = exchange_rates.find(r => r.base === fromCurrency && r.quote === targetCurrency);
        if (rateObj) {
            return amount * rateObj.rate;
        }

        const inverseRateObj = exchange_rates.find(r => r.base === targetCurrency && r.quote === fromCurrency);
        if (inverseRateObj && inverseRateObj.rate > 0) {
            return amount / inverseRateObj.rate;
        }

        // Hardcoded fallbacks in case DB sync is incomplete
        if (fromCurrency === 'EUR') {
            if (targetCurrency === 'USD') return amount * 1.08;
            if (targetCurrency === 'GBP') return amount * 0.85;
        } else if (targetCurrency === 'EUR') {
            if (fromCurrency === 'USD') return amount / 1.08;
            if (fromCurrency === 'GBP') return amount / 0.85;
        }

        return amount;
    };

    const formatMoney = (amount: number) => {
        const symbol = getCurrencySymbol(displayCurrency);
        return `${symbol}${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    // Calculate aggregated stats in selected display currency
    const convertedTotalBalance = accounts.reduce((sum, account) => {
        return sum + convertAmount(account.balance, account.currency, displayCurrency);
    }, 0);

    const convertedMonthlyIncome = convertAmount(monthly_income, 'EUR', displayCurrency);
    const convertedMonthlySpending = convertAmount(monthly_spending, 'EUR', displayCurrency);

    return (
        <>
            <Head title="Banking Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 min-w-0">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h2 className="text-2xl font-bold tracking-tight">Banking Overview</h2>
                    <div className="flex items-center gap-2">
                        <span className="text-xs text-muted-foreground">Display Currency:</span>
                        <Select value={displayCurrency} onValueChange={(v: any) => setDisplayCurrency(v)}>
                            <SelectTrigger className="w-[100px] h-8 text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="EUR">EUR (€)</SelectItem>
                                <SelectItem value="USD">USD ($)</SelectItem>
                                <SelectItem value="GBP">GBP (£)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Balance</CardTitle>
                            <Wallet className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatMoney(convertedTotalBalance)}</div>
                            <p className="text-xs text-muted-foreground">Across {accounts.length} accounts</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Monthly Income</CardTitle>
                            <ArrowUpRight className="size-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-500">{formatMoney(convertedMonthlyIncome)}</div>
                            <p className="text-xs text-muted-foreground">This month</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Monthly Spending</CardTitle>
                            <ArrowDownRight className="size-4 text-rose-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-rose-500">{formatMoney(convertedMonthlySpending)}</div>
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

                <div className="grid gap-4 grid-cols-1 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Your Accounts</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {accounts.map(account => (
                                <Link key={account.id} href={`/banking/accounts/${account.id}`} className="flex flex-col sm:flex-row sm:items-center justify-between rounded-lg border p-3 gap-2 sm:gap-4 transition-colors hover:bg-accent min-w-0">
                                    <div className="flex items-center gap-3 min-w-0">
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                            <CreditCard className="size-5 text-primary" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium leading-none truncate">{account.label}</p>
                                            <p className="text-xs text-muted-foreground mt-1">****{account.number.slice(-4)} ({account.currency})</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 justify-between sm:justify-end">
                                        <p className="text-sm font-bold">{getCurrencySymbol(account.currency)}{account.balance.toLocaleString()}</p>
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
                                <div key={tx.id} className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 min-w-0">
                                    <div className="flex items-center gap-3 min-w-0">
                                        <div className={`flex size-8 shrink-0 items-center justify-center rounded-full ${
                                            tx.type === 'deposit' ? 'bg-emerald-100 dark:bg-emerald-900/20' :
                                            tx.type === 'withdrawal' ? 'bg-rose-100 dark:bg-rose-900/20' :
                                            'bg-blue-100 dark:bg-blue-900/20'
                                        }`}>
                                            {tx.type === 'deposit' ? <ArrowUpRight className="size-4 text-emerald-600" /> :
                                             tx.type === 'withdrawal' ? <ArrowDownRight className="size-4 text-rose-600" /> :
                                             <ArrowLeftRight className="size-4 text-blue-600" />}
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium leading-none truncate">{tx.description || tx.type}</p>
                                            <p className="text-xs text-muted-foreground mt-1">{new Date(tx.created_at).toLocaleDateString()}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 justify-between sm:justify-end">
                                        <p className={`text-sm font-bold ${
                                            tx.type === 'deposit' ? 'text-emerald-600' :
                                            tx.type === 'withdrawal' ? 'text-rose-600' : ''
                                        }`}>
                                            {tx.type === 'deposit' ? '+' : '-'}{getCurrencySymbol(tx.currency || 'EUR')}{tx.amount.toLocaleString()}
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
