import { Head, useForm } from '@inertiajs/react';
import { Landmark, ArrowDownToLine } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Account { id: number; number: string; label: string; balance: number; currency: string; }

export default function Deposit({ accounts }: { accounts: Account[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        account_id: '', amount: '', method: 'bank_transfer', reference: '',
    });

    const submit = (e: React.FormEvent) => { e.preventDefault(); post(route('banking.deposit'), { onSuccess: () => reset() }); };

    return (
        <>
            <Head title="Deposit Funds" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Deposit Funds</h1>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle><ArrowDownToLine className="mr-2 inline h-4 w-4" />New Deposit</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Destination Account *</Label>
                                    <Select value={data.account_id} onValueChange={v => setData('account_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select account" /></SelectTrigger>
                                        <SelectContent>{accounts.map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toFixed(2)})</SelectItem>))}</SelectContent>
                                    </Select>
                                    <InputError message={errors.account_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Amount (EUR) *</Label>
                                    <Input type="number" step="0.01" min="0.01" value={data.amount} onChange={e => setData('amount', e.target.value)} required />
                                    <InputError message={errors.amount} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Deposit Method</Label>
                                    <Select value={data.method} onValueChange={v => setData('method', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                                            <SelectItem value="wire">Wire Transfer</SelectItem>
                                            <SelectItem value="card">Card Payment</SelectItem>
                                            <SelectItem value="cash">Cash Deposit</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Reference (optional)</Label>
                                    <Input value={data.reference} onChange={e => setData('reference', e.target.value)} placeholder="e.g. Salary March" />
                                </div>
                                <Button type="submit" disabled={processing} className="w-full"><ArrowDownToLine className="mr-1 h-4 w-4" />Deposit Funds</Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Account Balances</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            {accounts.length === 0 && <p className="text-sm text-muted-foreground">No active accounts</p>}
                            {accounts.map(a => (
                                <div key={a.id} className="flex items-center justify-between rounded-lg border p-3">
                                    <div className="flex items-center gap-3">
                                        <Landmark className="size-5 text-muted-foreground" />
                                        <div>
                                            <p className="font-medium">{a.label}</p>
                                            <p className="font-mono text-xs text-muted-foreground">{a.number}</p>
                                        </div>
                                    </div>
                                    <p className="font-bold">€{Number(a.balance).toFixed(2)}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Deposit.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Deposit', href: '' },
    ],
};
