import { Head, useForm, Link } from '@inertiajs/react';
import { Landmark, Plus, ArrowRight, CheckCircle, XCircle, Clock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Account { id: number; number: string; label: string; balance: number; currency: string; }
interface Loan { id: number; loan_number: string; amount: number; interest_rate: number; term_months: number; monthly_payment: number; total_payable: number; paid_amount: number; status: string; purpose: string | null; applied_at: string; account: { id: number; number: string; label: string }; }

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    underwriting: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-900/20 dark:text-rose-400',
    active: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400',
    paid: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    defaulted: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
};

export default function Loans({ loans, accounts }: { loans: { data: Loan[] }; accounts: Account[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        account_id: '', amount: '', interest_rate: '5.0', term_months: '12', purpose: '',
        collateral_description: '', collateral_value: '',
    });

    const submit = (e: React.FormEvent) => { e.preventDefault(); post(route('banking.loans'), { onSuccess: () => reset() }); };

    return (
        <>
            <Head title="Loans" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Loans</h1>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle><Plus className="mr-2 inline h-4 w-4" />Apply for a Loan</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Disbursement Account *</Label>
                                        <Select value={data.account_id} onValueChange={v => setData('account_id', v)}>
                                            <SelectTrigger><SelectValue placeholder="Select account" /></SelectTrigger>
                                            <SelectContent>{accounts.map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toFixed(2)})</SelectItem>))}</SelectContent>
                                        </Select>
                                        <InputError message={errors.account_id} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Loan Amount (EUR) *</Label>
                                        <Input type="number" step="0.01" min="100" value={data.amount} onChange={e => setData('amount', e.target.value)} required />
                                        <InputError message={errors.amount} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Interest Rate (% APR) *</Label>
                                        <Input type="number" step="0.1" min="0.1" max="50" value={data.interest_rate} onChange={e => setData('interest_rate', e.target.value)} required />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Term (months) *</Label>
                                        <Input type="number" min="1" max="120" value={data.term_months} onChange={e => setData('term_months', e.target.value)} required />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Purpose</Label>
                                        <Input value={data.purpose} onChange={e => setData('purpose', e.target.value)} placeholder="e.g. Home renovation" />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Collateral Value (EUR)</Label>
                                        <Input type="number" step="0.01" min="0" value={data.collateral_value} onChange={e => setData('collateral_value', e.target.value)} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Collateral Description</Label>
                                    <Input value={data.collateral_description} onChange={e => setData('collateral_description', e.target.value)} placeholder="e.g. Property deed" />
                                </div>
                                <Button type="submit" disabled={processing}><Landmark className="mr-1 h-4 w-4" />Submit Loan Application</Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Loan Status Guide</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex items-center gap-2"><Clock className="size-4 text-yellow-500" />Pending - Awaiting review</div>
                            <div className="flex items-center gap-2"><Clock className="size-4 text-blue-500" />Underwriting - Being assessed</div>
                            <div className="flex items-center gap-2"><CheckCircle className="size-4 text-emerald-500" />Approved - Ready for disbursement</div>
                            <div className="flex items-center gap-2"><XCircle className="size-4 text-rose-500" />Rejected - Not approved</div>
                            <div className="flex items-center gap-2"><ArrowRight className="size-4 text-indigo-500" />Active - Being repaid</div>
                            <div className="flex items-center gap-2"><CheckCircle className="size-4 text-green-500" />Paid - Fully repaid</div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader><CardTitle>Your Loan Applications ({loans.data.length})</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {loans.data.length === 0 && <p className="text-sm text-muted-foreground">No loan applications yet.</p>}
                        {loans.data.map(l => (
                            <Link key={l.id} href={route('banking.loans.show', l.id)} className="block rounded-lg border p-3 transition hover:bg-accent">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <Landmark className="size-5 text-muted-foreground" />
                                        <div>
                                            <p className="font-medium">{l.loan_number} - €{Number(l.amount).toLocaleString()}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {l.purpose || 'No purpose'} · {l.term_months} months · {l.interest_rate}% APR
                                                {l.account && ` · ${l.account.label}`}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge className={statusColors[l.status] || ''}>{l.status}</Badge>
                                        <ArrowRight className="size-4 text-muted-foreground" />
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Loans.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Loans', href: '' },
    ],
};
