import { Head, useForm } from '@inertiajs/react';
import { Globe, Send } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Account { id: number; label: string; number: string; currency: string; balance: number; }
interface Transfer { id: number; beneficiary_name: string; beneficiary_account: string; beneficiary_bank_name: string; transaction?: { amount: number; status: string; created_at: string; reference: string }; }

export default function Swift({ accounts, transfers }: { accounts: Account[]; transfers: Transfer[] }) {
    const { data, setData, post, processing, errors } = useForm({
        debit_account_id: '', amount: '', beneficiary_name: '', beneficiary_account: '', beneficiary_bic: '',
        beneficiary_bank_name: '', beneficiary_address: '', beneficiary_bank_address: '',
        remittance_info: '', purpose_of_payment: '',
    });

    return (
        <>
            <Head title="SWIFT Transfer" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">SWIFT International Transfer</h1>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle><Send className="mr-2 inline h-4 w-4" />New Transfer</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={e => { e.preventDefault(); post(route('banking.swift')); }} className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Debit Account *</Label>
                                    <Select value={data.debit_account_id} onValueChange={v => setData('debit_account_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select account" /></SelectTrigger>
                                        <SelectContent>{accounts.map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toFixed(2)})</SelectItem>))}</SelectContent>
                                    </Select>
                                    <InputError message={errors.debit_account_id} />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>Beneficiary Name *</Label>
                                        <Input value={data.beneficiary_name} onChange={e => setData('beneficiary_name', e.target.value)} required />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Amount (EUR) *</Label>
                                        <Input type="number" step="0.01" min="0.01" value={data.amount} onChange={e => setData('amount', e.target.value)} required />
                                        <InputError message={errors.amount} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Beneficiary Account / IBAN *</Label>
                                    <Input value={data.beneficiary_account} onChange={e => setData('beneficiary_account', e.target.value)} required />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>BIC / SWIFT Code *</Label>
                                        <Input value={data.beneficiary_bic} onChange={e => setData('beneficiary_bic', e.target.value)} required placeholder="AAAA BB CC DDD" />
                                        <InputError message={errors.beneficiary_bic} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Bank Name *</Label>
                                        <Input value={data.beneficiary_bank_name} onChange={e => setData('beneficiary_bank_name', e.target.value)} required />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Beneficiary Address</Label>
                                    <Input value={data.beneficiary_address} onChange={e => setData('beneficiary_address', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Bank Address</Label>
                                    <Input value={data.beneficiary_bank_address} onChange={e => setData('beneficiary_bank_address', e.target.value)} />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>Remittance Info</Label>
                                        <Input value={data.remittance_info} onChange={e => setData('remittance_info', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Purpose of Payment</Label>
                                        <Input value={data.purpose_of_payment} onChange={e => setData('purpose_of_payment', e.target.value)} />
                                    </div>
                                </div>
                                <Button type="submit" disabled={processing} className="w-full"><Globe className="mr-1 h-4 w-4" />Send SWIFT Transfer</Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Transfer History</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            {transfers.length === 0 && <p className="text-sm text-muted-foreground">No SWIFT transfers yet.</p>}
                            {transfers.map(t => (
                                <div key={t.id} className="flex items-center justify-between rounded-lg border p-3">
                                    <div className="flex items-center gap-3">
                                        <Globe className="size-5 text-muted-foreground" />
                                        <div>
                                            <p className="font-medium">{t.beneficiary_name}</p>
                                            <p className="font-mono text-xs text-muted-foreground">{t.beneficiary_account}</p>
                                            <p className="text-xs text-muted-foreground">{t.beneficiary_bank_name}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        {t.transaction && <p className="font-medium">-€{Number(t.transaction.amount).toFixed(2)}</p>}
                                        {t.transaction && <Badge variant={t.transaction.status === 'completed' ? 'default' : 'secondary'}>{t.transaction.status}</Badge>}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
