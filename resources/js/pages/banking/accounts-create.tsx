import { Head, useForm } from '@inertiajs/react';
import { CreditCard, Info } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';

interface AccountType {
    code: string;
    name: string;
    description: string;
    currency: string;
    minimum_balance: number;
    monthly_fee: number;
}

export default function CreateAccount({ account_types }: { account_types: AccountType[] }) {
    const { data, setData, post, processing, errors } = useForm({
        account_type_code: '',
        label: '',
        currency: 'EUR',
    });

    const selectedType = account_types.find(t => t.code === data.account_type_code);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/banking/accounts', {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Open New Account" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h2 className="text-2xl font-bold tracking-tight">Open New Account</h2>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Account Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="type">Account Type *</Label>
                                    <Select value={data.account_type_code} onValueChange={v => setData('account_type_code', v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select account type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {account_types.map(t => (
                                                <SelectItem key={t.code} value={t.code}>
                                                    {t.name} ({t.currency})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.account_type_code} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="currency">Account Currency *</Label>
                                    <Select value={data.currency} onValueChange={v => setData('currency', v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select currency" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="EUR">EUR (&euro;)</SelectItem>
                                            <SelectItem value="USD">USD ($)</SelectItem>
                                            <SelectItem value="GBP">GBP (&pound;)</SelectItem>
                                            <SelectItem value="CHF">CHF (Fr.)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.currency} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="label">Account Label</Label>
                                    <Input
                                        id="label"
                                        placeholder="e.g. My Savings"
                                        value={data.label}
                                        onChange={e => setData('label', e.target.value)}
                                    />
                                    <InputError message={errors.label} />
                                </div>

                                <Button type="submit" disabled={processing || !data.account_type_code}>
                                    {processing ? 'Creating...' : 'Open Account'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Account Type Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {selectedType ? (
                                <div className="space-y-4">
                                    <div className="flex items-center gap-3 rounded-lg border p-4">
                                        <CreditCard className="size-8 text-primary" />
                                        <div>
                                            <p className="font-medium">{selectedType.name}</p>
                                            <p className="text-sm text-muted-foreground">{selectedType.description}</p>
                                        </div>
                                    </div>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between rounded-lg border p-3">
                                            <span className="text-muted-foreground">Currency</span>
                                            <span className="font-medium">{selectedType.currency}</span>
                                        </div>
                                        <div className="flex justify-between rounded-lg border p-3">
                                            <span className="text-muted-foreground">Minimum Balance</span>
                                            <span className="font-medium">&euro;{selectedType.minimum_balance.toLocaleString()}</span>
                                        </div>
                                        <div className="flex justify-between rounded-lg border p-3">
                                            <span className="text-muted-foreground">Monthly Fee</span>
                                            <span className="font-medium">&euro;{selectedType.monthly_fee.toLocaleString()}</span>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-col items-center gap-3 py-8 text-center text-sm text-muted-foreground">
                                    <Info className="size-8" />
                                    <p>Select an account type to see details</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

CreateAccount.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Accounts', href: '/banking/accounts' },
        { title: 'Open Account', href: '' },
    ],
};
