import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CreditCard, ArrowUpRight, ArrowDownRight, Snowflake, XCircle, Play, ArrowLeftRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface AccountDetail {
    id: number;
    type: string;
    number: string;
    iban: string;
    bic: string;
    currency: string;
    balance: number;
    available_balance: number;
    ledger_balance: number;
    status: string;
    label: string;
    is_default: boolean;
}

interface Transaction {
    id: number;
    reference: string;
    type: string;
    amount: number;
    fee: number;
    currency: string;
    description: string;
    status: string;
    debit_account_id?: number;
    credit_account_id?: number;
    created_at: string;
}

export default function AccountDetail({ account, transactions }: { account: AccountDetail; transactions: Transaction[] }) {
    return (
        <>
            <Head title={account.label} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/banking/accounts"><ArrowLeft className="size-4" /></Link>
                    </Button>
                    <h2 className="text-2xl font-bold tracking-tight">{account.label}</h2>
                    <Badge variant={account.status === 'active' ? 'secondary' : 'outline'}>{account.status}</Badge>
                    {account.is_default && <Badge>Default</Badge>}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Account Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-xs text-muted-foreground">Current Balance</p>
                                <p className="text-4xl font-bold">&euro;{account.balance.toLocaleString()}</p>
                            </div>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">Available</p>
                                    <p className="font-semibold">&euro;{account.available_balance.toLocaleString()}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">Ledger</p>
                                    <p className="font-semibold">&euro;{account.ledger_balance.toLocaleString()}</p>
                                </div>
                            </div>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between rounded-lg border p-3">
                                    <span className="text-muted-foreground">Account Number</span>
                                    <span className="font-mono font-medium">{account.number}</span>
                                </div>
                                {account.iban && (
                                    <div className="flex justify-between rounded-lg border p-3">
                                        <span className="text-muted-foreground">IBAN</span>
                                        <span className="font-mono font-medium">{account.iban}</span>
                                    </div>
                                )}
                                {account.bic && (
                                    <div className="flex justify-between rounded-lg border p-3">
                                        <span className="text-muted-foreground">BIC/SWIFT</span>
                                        <span className="font-mono font-medium">{account.bic}</span>
                                    </div>
                                )}
                                <div className="flex justify-between rounded-lg border p-3">
                                    <span className="text-muted-foreground">Type</span>
                                    <span>{account.type}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Button className="w-full" asChild>
                                <Link href="/banking/transfer"><ArrowUpRight className="mr-2 size-4" />Send Transfer</Link>
                            </Button>
                            <Button variant="outline" className="w-full" asChild>
                                <Link href="/banking/transactions"><ArrowDownRight className="mr-2 size-4" />View All Transactions</Link>
                            </Button>
                            {account.status === 'active' && (
                                <Button variant="secondary" className="w-full" onClick={() => router.post(route('banking.accounts.freeze', account.id))}>
                                    <Snowflake className="mr-2 size-4" />Freeze Account
                                </Button>
                            )}
                            {account.status === 'frozen' && (
                                <Button variant="secondary" className="w-full" onClick={() => router.post(route('banking.accounts.unfreeze', account.id))}>
                                    <Play className="mr-2 size-4" />Unfreeze Account
                                </Button>
                            )}
                            {account.status === 'active' && (
                                <Button variant="destructive" className="w-full" onClick={() => { if (confirm('Are you sure you want to close this account? This cannot be undone.')) router.post(route('banking.accounts.close', account.id)); }}>
                                    <XCircle className="mr-2 size-4" />Close Account
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Transactions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {transactions.map(tx => {
                                const isCredit = tx.type === 'deposit' || (tx.type === 'transfer' && tx.credit_account_id === account.id);
                                const isDebit = tx.type === 'withdrawal' || (tx.type === 'transfer' && tx.debit_account_id === account.id);
                                const sign = isCredit ? '+' : '-';
                                return (
                                    <div key={tx.id} className="flex items-center justify-between rounded-lg border p-3">
                                        <div className="flex items-center gap-3">
                                            <div className={`flex size-8 items-center justify-center rounded-full ${
                                                isCredit ? 'bg-emerald-100 dark:bg-emerald-900/20' :
                                                isDebit ? 'bg-rose-100 dark:bg-rose-900/20' : 'bg-blue-100 dark:bg-blue-900/20'
                                            }`}>
                                                {isCredit ? <ArrowUpRight className="size-4 text-emerald-600" /> :
                                                 isDebit ? <ArrowDownRight className="size-4 text-rose-600" /> :
                                                 <ArrowLeftRight className="size-4 text-blue-600" />}
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium">{tx.description || tx.type}</p>
                                                <p className="text-xs text-muted-foreground font-mono">{tx.reference}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className={`text-sm font-bold ${
                                                isCredit ? 'text-emerald-600' :
                                                isDebit ? 'text-rose-600' : ''
                                            }`}>
                                                {sign}&euro;{tx.amount.toLocaleString()}
                                            </span>
                                            <Badge variant={tx.status === 'completed' ? 'secondary' : 'outline'} className="text-[10px]">
                                                {tx.status}
                                            </Badge>
                                        </div>
                                    </div>
                                );
                            })}
                            {transactions.length === 0 && (
                                <p className="py-8 text-center text-sm text-muted-foreground">No transactions on this account</p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AccountDetail.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Accounts', href: '/banking/accounts' },
        { title: 'Account', href: '' },
    ],
};
