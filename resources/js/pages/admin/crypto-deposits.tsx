import { Head, router } from '@inertiajs/react';
import { Database, CheckCircle, Clock, Download } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Deposit {
    id: number; reference: string;
    user: { id: number; name: string; email: string };
    currency: string; amount: number; net_amount: number;
    status: string; tx_hash: string | null; confirmations: number; created_at: string;
}

export default function CryptoDeposits({ deposits }: { deposits: { data: Deposit[] } }) {
    const confirm = (id: number) => router.post(route('admin.crypto-deposits.confirm', id));

    return (
        <>
            <Head title="Crypto Deposits" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Crypto Deposits</h1>
                <Card>
                    <CardHeader><CardTitle>All Deposits ({deposits.data.length})</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {deposits.data.map(d => (
                            <div key={d.id} className="flex items-center justify-between rounded-lg border p-3">
                                <div className="flex items-center gap-3">
                                    <Database className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="font-medium">{d.reference}</p>
                                        <p className="text-xs text-muted-foreground">{d.user.name} ({d.user.email})</p>
                                        <p className="text-xs text-muted-foreground">{d.currency} · {d.tx_hash ? d.tx_hash.slice(0, 16) + '...' : 'No TX hash'}</p>
                                        <p className="text-xs text-muted-foreground">{new Date(d.created_at).toLocaleString()}</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className="font-bold">{d.net_amount} {d.currency}</p>
                                    <Badge variant={d.status === 'confirmed' ? 'default' : 'secondary'}>{d.status}</Badge>
                                    {d.confirmations > 0 && <p className="text-xs text-muted-foreground">{d.confirmations} confirmations</p>}
                                    {d.status === 'pending' && (
                                        <Button variant="outline" size="sm" onClick={() => confirm(d.id)} className="mt-1">
                                            <CheckCircle className="mr-1 h-3 w-3" />Confirm
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
