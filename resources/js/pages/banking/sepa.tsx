import { Head, useForm } from '@inertiajs/react';
import { Landmark, Send, ArrowDownToLine, CheckCircle, XCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Account { id: number; label: string; number: string; currency: string; balance: number; }
interface Transfer { id: number; creditor_name: string; creditor_iban: string; transaction?: { amount: number; status: string; created_at: string; reference: string }; }

export default function Sepa({ accounts, transfers }: { accounts: Account[]; transfers: Transfer[] }) {
    const creditForm = useForm({
        action: 'credit_transfer', debit_account_id: '', amount: '', creditor_name: '', creditor_iban: '', creditor_bic: '', remittance_info: '',
    });

    const debitForm = useForm({
        action: 'direct_debit', credit_account_id: '', amount: '', debtor_name: '', debtor_iban: '', mandate_reference: '',
    });

    return (
        <>
            <Head title="SEPA Transfer" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">SEPA Transfers</h1>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader><CardTitle><Send className="mr-2 inline h-4 w-4" />Credit Transfer</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={e => { e.preventDefault(); creditForm.post(route('banking.sepa')); }} className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Debit Account *</Label>
                                    <Select value={creditForm.data.debit_account_id} onValueChange={v => creditForm.setData('debit_account_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select account" /></SelectTrigger>
                                        <SelectContent>{accounts.map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toFixed(2)})</SelectItem>))}</SelectContent>
                                    </Select>
                                    <InputError message={creditForm.errors.debit_account_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Creditor Name *</Label>
                                    <Input value={creditForm.data.creditor_name} onChange={e => creditForm.setData('creditor_name', e.target.value)} required />
                                </div>
                                <div className="space-y-2">
                                    <Label>Creditor IBAN *</Label>
                                    <Input value={creditForm.data.creditor_iban} onChange={e => creditForm.setData('creditor_iban', e.target.value)} placeholder="EEkk bbbb kkkk kkkk kkkk k" required />
                                    <InputError message={creditForm.errors.creditor_iban} />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>BIC</Label>
                                        <Input value={creditForm.data.creditor_bic} onChange={e => creditForm.setData('creditor_bic', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Amount (EUR) *</Label>
                                        <Input type="number" step="0.01" min="0.01" value={creditForm.data.amount} onChange={e => creditForm.setData('amount', e.target.value)} required />
                                        <InputError message={creditForm.errors.amount} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Remittance Info</Label>
                                    <Input value={creditForm.data.remittance_info} onChange={e => creditForm.setData('remittance_info', e.target.value)} />
                                </div>
                                <Button type="submit" disabled={creditForm.processing} className="w-full"><Send className="mr-1 h-4 w-4" />Send SEPA Transfer</Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle><ArrowDownToLine className="mr-2 inline h-4 w-4" />Direct Debit</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={e => { e.preventDefault(); debitForm.post(route('banking.sepa')); }} className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Credit Account *</Label>
                                    <Select value={debitForm.data.credit_account_id} onValueChange={v => debitForm.setData('credit_account_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select account" /></SelectTrigger>
                                        <SelectContent>{accounts.map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toFixed(2)})</SelectItem>))}</SelectContent>
                                    </Select>
                                    <InputError message={debitForm.errors.credit_account_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Debtor Name *</Label>
                                    <Input value={debitForm.data.debtor_name} onChange={e => debitForm.setData('debtor_name', e.target.value)} required />
                                </div>
                                <div className="space-y-2">
                                    <Label>Debtor IBAN *</Label>
                                    <Input value={debitForm.data.debtor_iban} onChange={e => debitForm.setData('debtor_iban', e.target.value)} placeholder="EEkk bbbb kkkk kkkk kkkk k" required />
                                </div>
                                <div className="space-y-2">
                                    <Label>Amount (EUR) *</Label>
                                    <Input type="number" step="0.01" min="0.01" value={debitForm.data.amount} onChange={e => debitForm.setData('amount', e.target.value)} required />
                                    <InputError message={debitForm.errors.amount} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Mandate Reference</Label>
                                    <Input value={debitForm.data.mandate_reference} onChange={e => debitForm.setData('mandate_reference', e.target.value)} />
                                </div>
                                <Button type="submit" disabled={debitForm.processing} className="w-full"><ArrowDownToLine className="mr-1 h-4 w-4" />Create Direct Debit</Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Transfer History</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            {transfers.length === 0 && <p className="text-sm text-muted-foreground">No SEPA transfers yet.</p>}
                            {transfers.map(t => (
                                <div key={t.id} className="flex items-center justify-between rounded-lg border p-3">
                                    <div className="flex items-center gap-3">
                                        <Landmark className="size-5 text-muted-foreground" />
                                        <div>
                                            <p className="font-medium">{t.creditor_name}</p>
                                            <p className="font-mono text-xs text-muted-foreground">{t.creditor_iban}</p>
                                            {t.transaction && (
                                                <div className="flex gap-2 text-xs text-muted-foreground">
                                                    <span>{t.transaction.reference}</span>
                                                    <span>{new Date(t.transaction.created_at).toLocaleDateString()}</span>
                                                </div>
                                            )}
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
