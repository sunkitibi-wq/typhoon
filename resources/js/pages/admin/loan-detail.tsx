import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Landmark, CheckCircle, XCircle, Send, ThumbsUp, ThumbsDown } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface Loan { id: number; loan_number: string; user: { id: number; name: string; email: string }; account: { id: number; account_number: string; label: string }; amount: number; interest_rate: number; term_months: number; monthly_payment: number; total_payable: number; paid_amount: number; status: string; purpose: string | null; collateral_description: string | null; collateral_value: number; applied_at: string; approved_at: string | null; disbursed_at: string | null; paid_at: string | null; rejection_reason: string | null; approved_by: { id: number; name: string } | null; }
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

export default function AdminLoanDetail({ loan, schedule }: { loan: Loan; schedule: any }) {
    return (
        <>
            <Head title={`Loan ${loan.loan_number}`} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/admin/loans"><ArrowLeft className="size-4" /></Link>
                    </Button>
                    <h2 className="text-2xl font-bold tracking-tight">{loan.loan_number}</h2>
                    <Badge className={statusColors[loan.status] || ''}>{loan.status}</Badge>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Amount</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{Number(loan.amount).toLocaleString()}</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Monthly Payment</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{Number(loan.monthly_payment).toFixed(2)}</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Rate / Term</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">{loan.interest_rate}% / {loan.term_months}mo</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Paid</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{Number(loan.paid_amount).toLocaleString()}</p></CardContent></Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle>Repayment Schedule</CardTitle></CardHeader>
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
                                        <Badge variant={r.status === 'paid' ? 'default' : r.status === 'overdue' ? 'destructive' : 'secondary'}>{r.status}</Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Applicant Details</CardTitle></CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Name</span><span>{loan.user.name}</span></div>
                                <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Email</span><span>{loan.user.email}</span></div>
                                <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Account</span><span>{loan.account?.label} ({loan.account?.account_number})</span></div>
                                <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Purpose</span><span>{loan.purpose || 'N/A'}</span></div>
                                {loan.collateral_description && (
                                    <div className="flex justify-between border-b pb-1"><span className="text-muted-foreground">Collateral</span><span>{loan.collateral_description} (€{Number(loan.collateral_value).toLocaleString()})</span></div>
                                )}
                                <div className="flex justify-between"><span className="text-muted-foreground">Applied</span><span>{new Date(loan.applied_at).toLocaleDateString()}</span></div>
                            </CardContent>
                        </Card>

                        {loan.status === 'pending' && (
                            <Card>
                                <CardHeader><CardTitle>Actions</CardTitle></CardHeader>
                                <CardContent className="space-y-3">
                                    <Button className="w-full" onClick={() => router.post(route('admin.loans.underwrite', loan.id))}>
                                        <Send className="mr-2 size-4" />Move to Underwriting
                                    </Button>
                                </CardContent>
                            </Card>
                        )}

                        {loan.status === 'underwriting' && (
                            <Card>
                                <CardHeader><CardTitle>Actions</CardTitle></CardHeader>
                                <CardContent className="space-y-3">
                                    <Button className="w-full" onClick={() => router.post(route('admin.loans.approve', loan.id))}>
                                        <ThumbsUp className="mr-2 size-4" />Approve Loan
                                    </Button>
                                    <Button variant="destructive" className="w-full" onClick={() => { const r = prompt('Rejection reason:'); if (r) router.post(route('admin.loans.reject', loan.id), { reason: r }); }}>
                                        <ThumbsDown className="mr-2 size-4" />Reject Loan
                                    </Button>
                                </CardContent>
                            </Card>
                        )}

                        {loan.status === 'approved' && (
                            <Card>
                                <CardHeader><CardTitle>Actions</CardTitle></CardHeader>
                                <CardContent>
                                    <Button className="w-full" onClick={() => router.post(route('admin.loans.disburse', loan.id))}>
                                        <Send className="mr-2 size-4" />Disburse Funds (€{Number(loan.amount).toLocaleString()})
                                    </Button>
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

AdminLoanDetail.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'Loans', href: '/admin/loans' },
        { title: 'Loan Detail', href: '' },
    ],
};
