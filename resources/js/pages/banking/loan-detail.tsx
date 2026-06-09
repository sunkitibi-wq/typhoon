import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Landmark, CheckCircle, XCircle, Clock, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Account { id: number; number: string; label: string; balance: number; currency: string; }
interface Repayment { id: number; installment_number: number; due_date: string; scheduled_amount: number; paid_amount: number; status: string; paid_at: string | null; }

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    underwriting: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-900/20 dark:text-rose-400',
    active: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400',
    paid: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    defaulted: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    overdue: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
};

export default function LoanDetail({ loan, schedule, accounts }: { loan: any; schedule: any; accounts: Account[] }) {
    const { data, setData, post, processing } = useForm({ from_account_id: '', installment_number: '' });

    const payInstallment = (e: React.FormEvent) => { e.preventDefault(); post(route('banking.loans.pay', loan.id)); };

    return (
        <>
            <Head title={loan.loan_number} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/banking/loans"><ArrowLeft className="size-4" /></Link>
                    </Button>
                    <h2 className="text-2xl font-bold tracking-tight">{loan.loan_number}</h2>
                    <Badge className={statusColors[loan.status] || ''}>{loan.status}</Badge>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Amount</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{Number(loan.amount).toLocaleString()}</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Monthly Payment</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{Number(loan.monthly_payment).toFixed(2)}</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Interest Rate</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">{loan.interest_rate}%</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Total Payable</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{Number(loan.total_payable).toLocaleString()}</p></CardContent></Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle>Repayment Schedule ({schedule.repayments.length} installments)</CardTitle></CardHeader>
                        <CardContent>
                            <div className="mb-4 flex items-center gap-2">
                                <div className="h-2 flex-1 rounded-full bg-muted">
                                    <div className="h-2 rounded-full bg-emerald-500" style={{ width: `${schedule.progress_percent}%` }} />
                                </div>
                                <span className="text-xs text-muted-foreground">{schedule.progress_percent}% paid</span>
                            </div>
                            <div className="space-y-2">
                                {schedule.repayments.map((r: Repayment) => (
                                    <div key={r.id} className="flex items-center justify-between rounded-lg border p-3 text-sm">
                                        <div className="flex items-center gap-3">
                                            <span className="font-mono font-medium">#{r.installment_number}</span>
                                            <div>
                                                <p>Due: {new Date(r.due_date).toLocaleDateString()}</p>
                                                <p className="text-xs text-muted-foreground">€{Number(r.scheduled_amount).toFixed(2)}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant={r.status === 'paid' ? 'default' : r.status === 'overdue' ? 'destructive' : 'secondary'}>
                                                {r.status}
                                            </Badge>
                                            {r.status === 'paid' && <CheckCircle className="size-4 text-emerald-500" />}
                                            {r.status === 'overdue' && <XCircle className="size-4 text-rose-500" />}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Loan Details</CardTitle></CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Account</span><span>{loan.account?.label}</span></div>
                                <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Term</span><span>{loan.term_months} months</span></div>
                                <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Paid</span><span>€{Number(loan.paid_amount).toFixed(2)}</span></div>
                                <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Purpose</span><span>{loan.purpose || 'N/A'}</span></div>
                                {loan.collateral_description && (
                                    <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Collateral</span><span>{loan.collateral_description}</span></div>
                                )}
                                <div className="flex justify-between"><span className="text-muted-foreground">Applied</span><span>{new Date(loan.applied_at).toLocaleDateString()}</span></div>
                            </CardContent>
                        </Card>

                        {loan.status === 'active' && schedule.next_due && (
                            <Card>
                                <CardHeader><CardTitle>Make a Payment</CardTitle></CardHeader>
                                <CardContent>
                                    <form onSubmit={payInstallment} className="space-y-4">
                                        <div className="space-y-2">
                                            <Label className="text-sm">From Account</Label>
                                            <Select value={data.from_account_id} onValueChange={v => setData('from_account_id', v)}>
                                                <SelectTrigger><SelectValue placeholder="Select account" /></SelectTrigger>
                                                <SelectContent>{accounts.map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toFixed(2)})</SelectItem>))}</SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label className="text-sm">Installment</Label>
                                            <Select value={data.installment_number} onValueChange={v => setData('installment_number', v)}>
                                                <SelectTrigger><SelectValue placeholder="Next due" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">Next due (€{Number(schedule.next_due.scheduled_amount).toFixed(2)})</SelectItem>
                                                    {schedule.repayments.filter((r: Repayment) => r.status === 'pending').map((r: Repayment) => (
                                                        <SelectItem key={r.id} value={String(r.installment_number)}>
                                                            #{r.installment_number} - €{Number(r.scheduled_amount).toFixed(2)} - Due {new Date(r.due_date).toLocaleDateString()}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <Button type="submit" disabled={processing} className="w-full">
                                            <ArrowRight className="mr-1 h-4 w-4" />Pay Installment
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>
                        )}

                        {loan.status === 'rejected' && loan.rejection_reason && (
                            <Card>
                                <CardHeader><CardTitle className="text-rose-600">Rejection Reason</CardTitle></CardHeader>
                                <CardContent><p className="text-sm">{loan.rejection_reason}</p></CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

LoanDetail.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Loans', href: '/banking/loans' },
        { title: 'Loan Detail', href: '' },
    ],
};
