import { Head, useForm } from '@inertiajs/react';
import { ArrowLeftRight, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';

interface Account {
    id: number;
    number: string;
    label: string;
    balance: number;
    currency: string;
}

export default function Transfer({ accounts }: { accounts: Account[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        from_account_id: '',
        to_account_id: '',
        amount: '',
        description: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/banking/transfer', {
            onSuccess: () => reset(),
        });
    }

    return (
        <>
            <Head title="Transfer" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h2 className="text-2xl font-bold tracking-tight">Transfer Funds</h2>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>New Transfer</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="from_account_id">From Account</Label>
                                    <Select value={data.from_account_id} onValueChange={v => setData('from_account_id', v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select source account" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {accounts.map(a => (
                                                <SelectItem key={a.id} value={String(a.id)}>
                                                    {a.label} - {a.number} (&euro;{a.balance.toLocaleString()})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.from_account_id} />
                                </div>

                                <div className="flex justify-center">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-primary/10">
                                        <ArrowRight className="size-5 text-primary" />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="to_account_id">To Account</Label>
                                    <Select value={data.to_account_id} onValueChange={v => setData('to_account_id', v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select destination account" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {accounts.map(a => (
                                                <SelectItem key={a.id} value={String(a.id)}>
                                                    {a.label} - {a.number}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.to_account_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="amount">Amount (&euro;)</Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="0.00"
                                        value={data.amount}
                                        onChange={e => setData('amount', e.target.value)}
                                    />
                                    <InputError message={errors.amount} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Description (optional)</Label>
                                    <Input
                                        id="description"
                                        placeholder="What's this for?"
                                        value={data.description}
                                        onChange={e => setData('description', e.target.value)}
                                    />
                                    <InputError message={errors.description} />
                                </div>

                                <Button type="submit" className="w-full" disabled={processing}>
                                    {processing ? 'Processing...' : 'Send Transfer'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Transfer Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm text-muted-foreground">
                            <div className="rounded-lg border p-4">
                                <h4 className="mb-2 font-medium text-foreground">Transfer Rules</h4>
                                <ul className="list-inside list-disc space-y-1">
                                    <li>Minimum transfer: &euro;0.01</li>
                                    <li>Maximum transfer: &euro;100,000.00 per transaction</li>
                                    <li>Transfers between your accounts are instant</li>
                                    <li>Fees may apply based on account type</li>
                                </ul>
                            </div>
                            <div className="rounded-lg border p-4">
                                <h4 className="mb-2 font-medium text-foreground">Need Help?</h4>
                                <p>Contact support if you need assistance with international transfers or larger amounts.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Transfer.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Transfer', href: '/banking/transfer' },
    ],
};
