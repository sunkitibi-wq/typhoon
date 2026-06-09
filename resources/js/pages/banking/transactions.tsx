import { Head } from '@inertiajs/react';
import { ArrowUpRight, ArrowDownRight, ArrowLeftRight, Filter } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Transaction {
    id: number;
    reference: string;
    type: string;
    amount: number;
    fee: number;
    currency: string;
    description: string;
    status: string;
    created_at: string;
    debit_account?: { id: number; account_number: string; label: string };
    credit_account?: { id: number; account_number: string; label: string };
}

export default function Transactions({ transactions }: { transactions: { data: Transaction[] } }) {
    return (
        <>
            <Head title="Transactions" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight">Transactions</h2>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-4">
                            <div className="flex-1">
                                <Input placeholder="Search transactions..." />
                            </div>
                            <Select>
                                <SelectTrigger className="w-[150px]">
                                    <SelectValue placeholder="Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem value="deposit">Deposit</SelectItem>
                                    <SelectItem value="withdrawal">Withdrawal</SelectItem>
                                    <SelectItem value="transfer">Transfer</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button variant="outline"><Filter className="mr-2 size-4" />Filter</Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {transactions.data.map(tx => (
                                <div key={tx.id} className="flex items-center justify-between rounded-lg border p-3">
                                    <div className="flex items-center gap-3">
                                        <div className={`flex size-10 items-center justify-center rounded-full ${
                                            tx.type === 'deposit' ? 'bg-emerald-100 dark:bg-emerald-900/20' :
                                            tx.type === 'withdrawal' ? 'bg-rose-100 dark:bg-rose-900/20' :
                                            'bg-blue-100 dark:bg-blue-900/20'
                                        }`}>
                                            {tx.type === 'deposit' ? <ArrowUpRight className="size-5 text-emerald-600" /> :
                                             tx.type === 'withdrawal' ? <ArrowDownRight className="size-5 text-rose-600" /> :
                                             <ArrowLeftRight className="size-5 text-blue-600" />}
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium">{tx.description || tx.type}</p>
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <span className="font-mono">{tx.reference}</span>
                                                <span>{new Date(tx.created_at).toLocaleString()}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="text-right">
                                            <p className={`text-sm font-bold ${
                                                tx.type === 'deposit' ? 'text-emerald-600' :
                                                tx.type === 'withdrawal' ? 'text-rose-600' : ''
                                            }`}>
                                                {tx.type === 'deposit' ? '+' : '-'}{tx.currency === 'EUR' ? '\u20AC' : '$'}{tx.amount.toLocaleString()}
                                            </p>
                                            {tx.fee > 0 && <p className="text-xs text-muted-foreground">Fee: {tx.fee}</p>}
                                        </div>
                                        <Badge variant={tx.status === 'completed' ? 'secondary' : tx.status === 'pending' ? 'outline' : 'destructive'}>
                                            {tx.status}
                                        </Badge>
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

Transactions.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Transactions', href: '/banking/transactions' },
    ],
};
