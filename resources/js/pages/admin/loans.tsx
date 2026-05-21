import { Head, Link } from '@inertiajs/react';
import { Landmark, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Pagination } from '@/components/ui/pagination';

interface Loan { id: number; loan_number: string; user: { id: number; name: string; email: string }; amount: number; interest_rate: number; term_months: number; monthly_payment: number; total_payable: number; paid_amount: number; status: string; purpose: string | null; applied_at: string; }

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    underwriting: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-900/20 dark:text-rose-400',
    active: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400',
    paid: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    defaulted: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
};

export default function AdminLoans({ loans }: { loans: { data: Loan[] } }) {
    return (
        <>
            <Head title="Loan Management" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Loan Management</h1>

                <Card>
                    <CardHeader><CardTitle>All Loan Applications</CardTitle></CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {loans.data.length === 0 && <p className="text-sm text-muted-foreground">No loan applications.</p>}
                            {loans.data.map(l => (
                                <Link key={l.id} href={route('admin.loans.show', l.id)} className="block rounded-lg border p-3 transition hover:bg-accent">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <Landmark className="size-5 text-muted-foreground" />
                                            <div>
                                                <p className="font-medium">{l.loan_number} - {l.user.name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    €{Number(l.amount).toLocaleString()} · {l.term_months}mo · {l.interest_rate}%
                                                    {l.purpose && ` · ${l.purpose}`}
                                                    {l.applied_at && ` · ${new Date(l.applied_at).toLocaleDateString()}`}
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
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminLoans.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'Loans', href: '' },
    ],
};
