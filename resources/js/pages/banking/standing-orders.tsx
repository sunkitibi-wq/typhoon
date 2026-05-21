import { Head, useForm, router } from '@inertiajs/react';
import { CalendarClock, Plus, Pause, Play, XCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Account { id: number; label: string; number: string; currency: string; balance: number; }
interface Order { id: number; debit_account?: { label: string }; beneficiary_name: string; amount: number; currency: string; frequency: string; status: string; next_execution_at: string; reference: string | null; }

export default function StandingOrders({ orders, accounts }: { orders: Order[]; accounts: Account[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        debit_account_id: '', beneficiary_name: '', beneficiary_iban: '', amount: '', currency: 'EUR',
        frequency: 'monthly', reference: '', notes: '', starts_at: '', ends_at: '',
    });

    const submit = (e: React.FormEvent) => { e.preventDefault(); post(route('banking.standing-orders'), { onSuccess: () => reset() }); };

    return (
        <>
            <Head title="Standing Orders" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Standing Orders</h1>

                <Card>
                    <CardHeader><CardTitle><Plus className="mr-2 inline h-4 w-4" />New Standing Order</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Debit Account *</Label>
                                    <Select value={data.debit_account_id} onValueChange={v => setData('debit_account_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select account" /></SelectTrigger>
                                        <SelectContent>{accounts.map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toFixed(2)})</SelectItem>))}</SelectContent>
                                    </Select>
                                    <InputError message={errors.debit_account_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Beneficiary Name *</Label>
                                    <Input value={data.beneficiary_name} onChange={e => setData('beneficiary_name', e.target.value)} required />
                                </div>
                                <div className="space-y-2">
                                    <Label>Beneficiary IBAN</Label>
                                    <Input value={data.beneficiary_iban} onChange={e => setData('beneficiary_iban', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Amount *</Label>
                                    <Input type="number" step="0.01" value={data.amount} onChange={e => setData('amount', e.target.value)} required />
                                    <InputError message={errors.amount} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Currency</Label>
                                    <Select value={data.currency} onValueChange={v => setData('currency', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent><SelectItem value="EUR">EUR</SelectItem><SelectItem value="USD">USD</SelectItem><SelectItem value="GBP">GBP</SelectItem></SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Frequency *</Label>
                                    <Select value={data.frequency} onValueChange={v => setData('frequency', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="weekly">Weekly</SelectItem>
                                            <SelectItem value="monthly">Monthly</SelectItem>
                                            <SelectItem value="quarterly">Quarterly</SelectItem>
                                            <SelectItem value="yearly">Yearly</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Start Date *</Label>
                                    <Input type="date" value={data.starts_at} onChange={e => setData('starts_at', e.target.value)} required />
                                </div>
                                <div className="space-y-2">
                                    <Label>End Date</Label>
                                    <Input type="date" value={data.ends_at} onChange={e => setData('ends_at', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Reference</Label>
                                    <Input value={data.reference} onChange={e => setData('reference', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Notes</Label>
                                    <Input value={data.notes} onChange={e => setData('notes', e.target.value)} />
                                </div>
                            </div>
                            <Button type="submit" disabled={processing}><CalendarClock className="mr-1 h-4 w-4" />Create Standing Order</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Your Standing Orders ({orders.length})</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {orders.length === 0 && <p className="text-sm text-muted-foreground">No standing orders yet.</p>}
                        {orders.map(o => (
                            <div key={o.id} className="flex items-center justify-between rounded-lg border p-3">
                                <div className="flex items-center gap-3">
                                    <CalendarClock className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="font-medium">{o.beneficiary_name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            €{Number(o.amount).toFixed(2)} {o.currency} · {o.frequency}
                                            {o.debit_account && ` · ${o.debit_account.label}`}
                                            {o.reference && ` · ${o.reference}`}
                                        </p>
                                        <p className="text-xs text-muted-foreground">Next: {new Date(o.next_execution_at).toLocaleDateString()}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge variant={o.status === 'active' ? 'default' : 'secondary'}>{o.status}</Badge>
                                    <Button variant="ghost" size="icon" onClick={() => router.post(route('banking.standing-orders.toggle', o.id))} title={o.status === 'active' ? 'Pause' : 'Resume'}>
                                        {o.status === 'active' ? <Pause className="size-4" /> : <Play className="size-4" />}
                                    </Button>
                                    <Button variant="ghost" size="icon" onClick={() => { if (confirm('Cancel this standing order?')) router.delete(route('banking.standing-orders.destroy', o.id)); }} title="Cancel">
                                        <XCircle className="size-4 text-rose-500" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
