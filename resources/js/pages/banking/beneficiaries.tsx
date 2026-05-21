import { Head, useForm, router } from '@inertiajs/react';
import { UserPlus, Trash2, Building2, Landmark } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';

interface Beneficiary {
    id: number; name: string; iban: string | null; bic: string | null;
    account_number: string | null; bank_name: string | null;
    bank_country: string | null; email: string | null; notes: string | null;
}

export default function Beneficiaries({ beneficiaries }: { beneficiaries: Beneficiary[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', iban: '', bic: '', account_number: '', bank_name: '', bank_country: '', email: '', notes: '',
    });

    const submit = (e: React.FormEvent) => { e.preventDefault(); post(route('banking.beneficiaries'), { onSuccess: () => reset() }); };

    const remove = (id: number) => {
        if (confirm('Remove this beneficiary?')) {
            router.delete(route('banking.beneficiaries.destroy', id));
        }
    };

    return (
        <>
            <Head title="Beneficiaries" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Beneficiaries</h1>

                <Card>
                    <CardHeader><CardTitle><UserPlus className="mr-2 inline h-4 w-4" />Add Beneficiary</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Name *</Label>
                                    <Input value={data.name} onChange={e => setData('name', e.target.value)} required />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="space-y-2">
                                    <Label>IBAN</Label>
                                    <Input value={data.iban} onChange={e => setData('iban', e.target.value)} placeholder="EEkk bbbb kkkk kkkk kkkk k" />
                                    <InputError message={errors.iban} />
                                </div>
                                <div className="space-y-2">
                                    <Label>BIC / SWIFT</Label>
                                    <Input value={data.bic} onChange={e => setData('bic', e.target.value)} placeholder="AAAA BB CC DDD" />
                                </div>
                                <div className="space-y-2">
                                    <Label>Account Number</Label>
                                    <Input value={data.account_number} onChange={e => setData('account_number', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Bank Name</Label>
                                    <Input value={data.bank_name} onChange={e => setData('bank_name', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Bank Country (ISO 2-letter)</Label>
                                    <Input value={data.bank_country} onChange={e => setData('bank_country', e.target.value)} maxLength={2} placeholder="DE" />
                                </div>
                                <div className="space-y-2">
                                    <Label>Email</Label>
                                    <Input type="email" value={data.email} onChange={e => setData('email', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Notes</Label>
                                    <Input value={data.notes} onChange={e => setData('notes', e.target.value)} />
                                </div>
                            </div>
                            <Button type="submit" disabled={processing}><UserPlus className="mr-1 h-4 w-4" />Add Beneficiary</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Saved Beneficiaries ({beneficiaries.length})</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {beneficiaries.length === 0 && <p className="text-sm text-muted-foreground">No beneficiaries yet.</p>}
                        {beneficiaries.map(b => (
                            <div key={b.id} className="flex items-start justify-between rounded-lg border p-3">
                                <div className="flex gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20">
                                        <Building2 className="size-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <p className="font-medium">{b.name}</p>
                                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                            {b.iban && <span><Landmark className="mr-1 inline h-3 w-3" />{b.iban}</span>}
                                            {b.bic && <span>BIC: {b.bic}</span>}
                                            {b.bank_name && <span>{b.bank_name}</span>}
                                            {b.email && <span>{b.email}</span>}
                                        </div>
                                        {b.notes && <p className="mt-1 text-xs text-muted-foreground">{b.notes}</p>}
                                    </div>
                                </div>
                                <Button variant="ghost" size="icon" onClick={() => remove(b.id)}><Trash2 className="size-4 text-rose-500" /></Button>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
