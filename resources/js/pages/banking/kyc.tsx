import { Head, useForm } from '@inertiajs/react';
import { Shield, ShieldCheck, ShieldAlert, Upload } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';

interface KycStatus {
    status: string;
    kyc_level: string;
    submitted: boolean;
}

export default function Kyc({ kyc_status }: { kyc_status: KycStatus }) {
    const { data, setData, post, processing, errors } = useForm({
        country: '',
        date_of_birth: '',
        id_type: '',
        id_number: '',
        address_line1: '',
        city: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/banking/kyc', {
            preserveScroll: true,
        });
    }

    const statusIcon = kyc_status.status === 'approved' ? <ShieldCheck className="size-8 text-emerald-500" /> :
        kyc_status.status === 'pending' ? <ShieldAlert className="size-8 text-amber-500" /> :
        <Shield className="size-8 text-muted-foreground" />;

    return (
        <>
            <Head title="KYC Verification" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h2 className="text-2xl font-bold tracking-tight">Identity Verification</h2>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-8">
                            {statusIcon}
                            <h3 className="mt-4 text-lg font-semibold capitalize">{kyc_status.status.replace('_', ' ')}</h3>
                            <p className="text-sm text-muted-foreground">Level: {kyc_status.kyc_level}</p>
                            <Badge className="mt-2" variant={kyc_status.status === 'approved' ? 'secondary' : 'outline'}>
                                {kyc_status.status}
                            </Badge>
                        </CardContent>
                    </Card>

                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Submit Verification</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {kyc_status.status === 'approved' ? (
                                <div className="flex flex-col items-center gap-4 py-8 text-center">
                                    <ShieldCheck className="size-12 text-emerald-500" />
                                    <p className="text-lg font-medium">Verification Complete</p>
                                    <p className="text-sm text-muted-foreground">Your identity has been verified. You have full access to all platform features.</p>
                                </div>
                            ) : (
                                <form onSubmit={submit} className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="country">Country of Residence *</Label>
                                            <Input id="country" placeholder="e.g. DE" maxLength={2} value={data.country} onChange={e => setData('country', e.target.value)} />
                                            <InputError message={errors.country} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="date_of_birth">Date of Birth *</Label>
                                            <Input id="date_of_birth" type="date" value={data.date_of_birth} onChange={e => setData('date_of_birth', e.target.value)} />
                                            <InputError message={errors.date_of_birth} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="id_type">ID Type</Label>
                                            <Select value={data.id_type} onValueChange={v => setData('id_type', v)}>
                                                <SelectTrigger><SelectValue placeholder="Select ID type" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="passport">Passport</SelectItem>
                                                    <SelectItem value="national_id">National ID</SelectItem>
                                                    <SelectItem value="drivers_license">Driver's License</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.id_type} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="id_number">ID Number</Label>
                                            <Input id="id_number" value={data.id_number} onChange={e => setData('id_number', e.target.value)} />
                                            <InputError message={errors.id_number} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="address_line1">Address Line 1</Label>
                                            <Input id="address_line1" value={data.address_line1} onChange={e => setData('address_line1', e.target.value)} />
                                            <InputError message={errors.address_line1} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="city">City</Label>
                                            <Input id="city" value={data.city} onChange={e => setData('city', e.target.value)} />
                                            <InputError message={errors.city} />
                                        </div>
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Submitting...' : 'Submit Verification'}
                                    </Button>
                                </form>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Kyc.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'KYC', href: '/banking/kyc' },
    ],
};
