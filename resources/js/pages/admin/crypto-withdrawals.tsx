import { Head, router } from '@inertiajs/react';
import { Send, CheckCircle, Clock, ExternalLink } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Withdrawal {
    id: number; reference: string;
    user: { id: number; name: string; email: string };
    currency: string; amount: number; net_amount: number;
    to_address: string; status: string; created_at: string;
}

export default function CryptoWithdrawals({ withdrawals }: { withdrawals: { data: Withdrawal[] } }) {
    const approve = (id: number) => router.post(route('admin.crypto-withdrawals.approve', id));

    return (
        <>
            <Head title="Crypto Withdrawals" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Crypto Withdrawals</h1>
                <Card>
                    <CardHeader><CardTitle>All Withdrawals ({withdrawals.data.length})</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {withdrawals.data.map(w => (
                            <div key={w.id} className="flex items-center justify-between rounded-lg border p-3">
                                <div className="flex items-center gap-3">
                                    <Send className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="font-medium">{w.reference}</p>
                                        <p className="text-xs text-muted-foreground">{w.user.name} ({w.user.email})</p>
                                        <p className="font-mono text-xs text-muted-foreground">To: {w.to_address.slice(0, 10)}...{w.to_address.slice(-6)}</p>
                                        <p className="text-xs text-muted-foreground">{new Date(w.created_at).toLocaleString()}</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className="font-bold">{w.net_amount} {w.currency}</p>
                                    <Badge variant={w.status === 'approved' ? 'default' : w.status === 'pending' ? 'secondary' : 'destructive'}>{w.status}</Badge>
                                    {w.status === 'pending' && (
                                        <Button variant="outline" size="sm" onClick={() => approve(w.id)} className="mt-1">
                                            <CheckCircle className="mr-1 h-3 w-3" />Approve
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
