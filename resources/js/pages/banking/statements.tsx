import { Head, router } from '@inertiajs/react';
import { FileText, Download, ArrowUpRight, ArrowDownRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Account { id: number; label: string; number: string; currency: string; }
interface Statement { account: Account; period: { from: string; to: string }; opening_balance: number; closing_balance: number; total_debits: number; total_credits: number; transaction_count: number; transactions: { data: Array<{ id: number; reference: string; type: string; amount: number; description: string; status: string; created_at: string }> }; }

export default function Statements({ accounts, selected_account_id, from, to, statement }: {
    accounts: Account[]; selected_account_id: number | null; from: string; to: string; statement: Statement | null;
}) {
    const selectAccount = (id: string) => { router.get(route('banking.statements', { account_id: id, from, to })); };

    return (
        <>
            <Head title="Account Statements" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Account Statements</h1>

                <Card>
                    <CardHeader><CardTitle><FileText className="mr-2 inline h-4 w-4" />Select Account</CardTitle></CardHeader>
                    <CardContent className="flex gap-4 items-end">
                        <div className="w-72 space-y-2">
                            <Select value={selected_account_id ? String(selected_account_id) : ''} onValueChange={selectAccount}>
                                <SelectTrigger><SelectValue placeholder="Choose account" /></SelectTrigger>
                                <SelectContent>{accounts.map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (****{a.number.slice(-4)})</SelectItem>))}</SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {statement && (
                    <>
                        <div className="flex justify-end">
                            <Button asChild>
                                <a href={route('banking.statements.download', { account: selected_account_id!, from, to })}>
                                    <Download className="mr-2 size-4" />Download PDF
                                </a>
                            </Button>
                        </div>
                        <div className="grid gap-4 md:grid-cols-4">
                            <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Opening Balance</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{Number(statement.opening_balance).toFixed(2)}</p></CardContent></Card>
                            <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Closing Balance</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{Number(statement.closing_balance).toFixed(2)}</p></CardContent></Card>
                            <Card><CardHeader className="pb-2"><CardTitle className="text-sm text-emerald-600">Credits</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold text-emerald-600">€{Number(statement.total_credits).toFixed(2)}</p></CardContent></Card>
                            <Card><CardHeader className="pb-2"><CardTitle className="text-sm text-rose-600">Debits</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold text-rose-600">€{Number(statement.total_debits).toFixed(2)}</p></CardContent></Card>
                        </div>

                        <Card>
                            <CardHeader><CardTitle>Transactions ({statement.transaction_count})</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                {statement.transactions.data.map(tx => (
                                    <div key={tx.id} className="flex items-center justify-between rounded-lg border p-3">
                                        <div className="flex items-center gap-3">
                                            {tx.type === 'deposit' ? <ArrowUpRight className="size-5 text-emerald-500" /> : <ArrowDownRight className="size-5 text-rose-500" />}
                                            <div>
                                                <p className="font-medium">{tx.description || tx.type}</p>
                                                <p className="text-xs text-muted-foreground">{tx.reference} · {new Date(tx.created_at).toLocaleDateString()}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className={`font-bold ${tx.type === 'deposit' ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                {tx.type === 'deposit' ? '+' : '-'}€{Number(tx.amount).toFixed(2)}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </>
                )}

                {!statement && accounts.length > 0 && <p className="text-sm text-muted-foreground">Select an account to view its statement.</p>}
            </div>
        </>
    );
}
