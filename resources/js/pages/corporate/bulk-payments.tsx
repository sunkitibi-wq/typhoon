import { Head, useForm } from '@inertiajs/react';
import { Send, Plus, Trash2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Account {
    id: number;
    number: string;
    label: string;
    balance: number;
    currency: string;
}

interface PaymentItem {
    beneficiary_name: string;
    beneficiary_iban: string;
    amount: string;
    reference: string;
}

export default function BulkPayments({ payments, accounts }: { payments: any; accounts: Account[] }) {
    const { data, setData, post, processing, errors } = useForm({
        debit_account_id: '',
        type: 'supplier',
        payments: [{ beneficiary_name: '', beneficiary_iban: '', amount: '', reference: '' }] as PaymentItem[],
    });

    const addPayment = () => {
        setData('payments', [...data.payments, { beneficiary_name: '', beneficiary_iban: '', amount: '', reference: '' }]);
    };

    const removePayment = (index: number) => {
        setData('payments', data.payments.filter((_, i) => i !== index));
    };

    const updatePayment = (index: number, field: keyof PaymentItem, value: string) => {
        const updated = data.payments.map((p, i) => i === index ? { ...p, [field]: value } : p);
        setData('payments', updated);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('corporate.bulk-payments'));
    };

    const totalAmount = data.payments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);

    return (
        <>
            <Head title="Bulk Payments" />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-bold">Bulk Payments</h1>
                    <p className="text-muted-foreground">Process multiple payments in a single batch</p>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Send className="h-5 w-5" />
                            <CardTitle>New Batch Payment</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="debit_account_id">Debit Account *</Label>
                                    <Select value={data.debit_account_id} onValueChange={v => setData('debit_account_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select account" /></SelectTrigger>
                                        <SelectContent>
                                            {accounts.map(a => (
                                                <SelectItem key={a.id} value={String(a.id)}>
                                                    {a.label || a.number} — €{a.balance.toLocaleString()}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.debit_account_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="type">Payment Type</Label>
                                    <Select value={data.type} onValueChange={v => setData('type', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="supplier">Supplier Payments</SelectItem>
                                            <SelectItem value="payroll">Payroll</SelectItem>
                                            <SelectItem value="dividend">Dividends</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <Label>Payments ({data.payments.length})</Label>
                                    <Button type="button" variant="outline" size="sm" onClick={addPayment}>
                                        <Plus className="mr-1 h-3 w-3" /> Add Row
                                    </Button>
                                </div>
                                {data.payments.map((payment, index) => (
                                    <div key={index} className="flex items-end gap-2 rounded-lg border p-3">
                                        <div className="flex-1 space-y-1">
                                            <Label className="text-xs">Beneficiary</Label>
                                            <Input value={payment.beneficiary_name} onChange={e => updatePayment(index, 'beneficiary_name', e.target.value)} placeholder="Name" required />
                                        </div>
                                        <div className="flex-1 space-y-1">
                                            <Label className="text-xs">IBAN</Label>
                                            <Input value={payment.beneficiary_iban} onChange={e => updatePayment(index, 'beneficiary_iban', e.target.value)} placeholder="IBAN" required />
                                        </div>
                                        <div className="w-28 space-y-1">
                                            <Label className="text-xs">Amount</Label>
                                            <Input type="number" step="0.01" min="0.01" value={payment.amount} onChange={e => updatePayment(index, 'amount', e.target.value)} placeholder="0.00" required />
                                        </div>
                                        <div className="flex-1 space-y-1">
                                            <Label className="text-xs">Reference</Label>
                                            <Input value={payment.reference} onChange={e => updatePayment(index, 'reference', e.target.value)} placeholder="Ref" />
                                        </div>
                                        {data.payments.length > 1 && (
                                            <Button type="button" variant="ghost" size="icon" onClick={() => removePayment(index)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>
                                ))}
                            </div>

                            <div className="flex items-center justify-between rounded-lg bg-muted p-3">
                                <span className="font-medium">Total: {data.payments.length} payments</span>
                                <span className="text-lg font-bold">€{totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                            </div>

                            <Button type="submit" disabled={processing}>
                                <Send className="mr-2 h-4 w-4" /> Process Batch
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment History</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {payments.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No bulk payments processed yet.</p>
                        ) : (
                            <div className="space-y-3">
                                {payments.data.map((payment: any) => (
                                    <div key={payment.id} className="flex items-center justify-between rounded-lg border p-3">
                                        <div>
                                            <p className="font-medium">{payment.batch_reference}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {payment.total_transactions} items · {payment.type}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-medium">€{parseFloat(payment.total_amount).toLocaleString()}</p>
                                            <Badge variant={payment.status === 'completed' ? 'default' : payment.status === 'partial' ? 'secondary' : 'destructive'}>
                                                {payment.status}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
