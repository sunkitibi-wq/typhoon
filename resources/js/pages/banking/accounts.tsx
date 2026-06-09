import { Head, Link } from '@inertiajs/react';
import { Plus, CreditCard, ExternalLink } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Account {
    id: number;
    type: string;
    number: string;
    iban: string;
    currency: string;
    balance: number;
    available_balance: number;
    status: string;
    label: string;
    is_default: boolean;
}

export default function Accounts({ accounts }: { accounts: Account[] }) {
    return (
        <>
            <Head title="Accounts" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight">Accounts</h2>
                    <Button asChild>
                        <Link href="/banking/accounts/create">
                            <Plus className="mr-2 size-4" />
                            New Account
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {accounts.map(account => (
                        <Card key={account.id} className={account.is_default ? 'ring-2 ring-primary' : ''}>
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <div>
                                        <CardTitle className="text-lg">{account.label}</CardTitle>
                                        <p className="text-sm text-muted-foreground">{account.type}</p>
                                    </div>
                                    {account.is_default && (
                                        <Badge>Default</Badge>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">Balance</p>
                                    <p className="text-3xl font-bold">
                                        {account.currency === 'EUR' ? '\u20AC' : '$'}{account.balance.toLocaleString()}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Available: {account.currency === 'EUR' ? '\u20AC' : '$'}{account.available_balance.toLocaleString()}
                                    </p>
                                </div>
                                <div className="space-y-1 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Account</span>
                                        <span className="font-mono">{account.number}</span>
                                    </div>
                                    {account.iban && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">IBAN</span>
                                            <span className="font-mono text-xs">{account.iban}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Status</span>
                                        <Badge variant={account.status === 'active' ? 'secondary' : 'outline'} className="text-[10px]">
                                            {account.status}
                                        </Badge>
                                    </div>
                                </div>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" asChild className="flex-1">
                                        <Link href={`/banking/accounts/${account.id}`}>
                                            <ExternalLink className="mr-1 size-3" />
                                            Details
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

Accounts.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Accounts', href: '/banking/accounts' },
    ],
};
