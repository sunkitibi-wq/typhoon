import { Head, useForm, router } from '@inertiajs/react';
import { DollarSign, Plus, ToggleLeft, ToggleRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Fee {
    id: number; name: string; fee_type: string; calculation_method: string;
    fee_value: number; min_fee: number | null; max_fee: number | null;
    currency: string; is_active: boolean; created_at: string;
}

export default function FeeSchedules({ fees }: { fees: Fee[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', fee_type: 'transfer', calculation_method: 'fixed',
        fee_value: '', min_fee: '', max_fee: '', currency: 'EUR',
    });

    const submit = (e: React.FormEvent) => { e.preventDefault(); post(route('admin.fee-schedules.create'), { onSuccess: () => reset() }); };
    const toggle = (id: number) => router.post(route('admin.fee-schedules.toggle', id));

    return (
        <>
            <Head title="Fee Schedules" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Fee Schedules</h1>

                <Card>
                    <CardHeader><CardTitle><Plus className="mr-2 inline h-4 w-4" />New Fee Schedule</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label>Name *</Label>
                                    <Input value={data.name} onChange={e => setData('name', e.target.value)} required placeholder="SEPA Transfer Fee" />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Fee Type *</Label>
                                    <Select value={data.fee_type} onValueChange={v => setData('fee_type', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="transfer">Transfer</SelectItem>
                                            <SelectItem value="withdrawal">Withdrawal</SelectItem>
                                            <SelectItem value="crypto">Crypto</SelectItem>
                                            <SelectItem value="sepa">SEPA</SelectItem>
                                            <SelectItem value="swift">SWIFT</SelectItem>
                                            <SelectItem value="monthly">Monthly</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Method *</Label>
                                    <Select value={data.calculation_method} onValueChange={v => setData('calculation_method', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="fixed">Fixed</SelectItem>
                                            <SelectItem value="percentage">Percentage</SelectItem>
                                            <SelectItem value="tiered">Tiered</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.calculation_method} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Value *</Label>
                                    <Input type="number" step="0.000001" value={data.fee_value} onChange={e => setData('fee_value', e.target.value)} required />
                                    <InputError message={errors.fee_value} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Min Fee</Label>
                                    <Input type="number" step="0.01" value={data.min_fee} onChange={e => setData('min_fee', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Max Fee</Label>
                                    <Input type="number" step="0.01" value={data.max_fee} onChange={e => setData('max_fee', e.target.value)} />
                                </div>
                            </div>
                            <Button type="submit" disabled={processing}><Plus className="mr-1 h-4 w-4" />Create Fee Schedule</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Fee Schedules ({fees.length})</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {fees.map(f => (
                            <div key={f.id} className="flex items-center justify-between rounded-lg border p-3">
                                <div className="flex items-center gap-3">
                                    <DollarSign className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="font-medium">{f.name}</p>
                                        <div className="flex gap-2 text-xs text-muted-foreground">
                                            <Badge variant="outline" className="text-[10px]">{f.fee_type}</Badge>
                                            <Badge variant="outline" className="text-[10px]">{f.calculation_method}</Badge>
                                            <span>{f.calculation_method === 'percentage' ? `${f.fee_value}%` : f.fee_value} {f.currency}</span>
                                            {f.min_fee && <span>Min: {f.min_fee}</span>}
                                            {f.max_fee && <span>Max: {f.max_fee}</span>}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge variant={f.is_active ? 'default' : 'secondary'}>{f.is_active ? 'Active' : 'Inactive'}</Badge>
                                    <Button variant="ghost" size="sm" onClick={() => toggle(f.id)}>
                                        {f.is_active ? <ToggleRight className="h-4 w-4" /> : <ToggleLeft className="h-4 w-4" />}
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
