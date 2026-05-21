import { Head, useForm } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';

interface Business {
    id: number;
    company_name: string;
    registration_number: string | null;
    tax_id: string | null;
    vat_number: string | null;
    registered_address: string | null;
    business_type: string | null;
    industry: string | null;
    website: string | null;
    contact_email: string;
    contact_phone: string | null;
    founded_year: number | null;
    employee_count: number | null;
    annual_revenue: number | null;
    status: string;
}

export default function BusinessProfile({ business }: { business: Business | null }) {
    const { data, setData, post, processing, errors } = useForm({
        company_name: business?.company_name ?? '',
        registration_number: business?.registration_number ?? '',
        tax_id: business?.tax_id ?? '',
        vat_number: business?.vat_number ?? '',
        registered_address: business?.registered_address ?? '',
        business_type: business?.business_type ?? '',
        industry: business?.industry ?? '',
        website: business?.website ?? '',
        contact_email: business?.contact_email ?? '',
        contact_phone: business?.contact_phone ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('corporate.business-profile'));
    };

    return (
        <>
            <Head title="Business Profile" />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-bold">Business Profile</h1>
                    <p className="text-muted-foreground">
                        {business ? 'Update your business information' : 'Register your business'}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            <CardTitle>Company Details</CardTitle>
                            {business && (
                                <Badge variant={business.status === 'verified' ? 'default' : 'secondary'}>
                                    {business.status}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="company_name">Company Name *</Label>
                                    <Input id="company_name" value={data.company_name} onChange={e => setData('company_name', e.target.value)} required />
                                    <InputError message={errors.company_name} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="registration_number">Registration Number</Label>
                                    <Input id="registration_number" value={data.registration_number} onChange={e => setData('registration_number', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="tax_id">Tax ID</Label>
                                    <Input id="tax_id" value={data.tax_id} onChange={e => setData('tax_id', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="vat_number">VAT Number</Label>
                                    <Input id="vat_number" value={data.vat_number} onChange={e => setData('vat_number', e.target.value)} />
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="registered_address">Registered Address</Label>
                                    <Input id="registered_address" value={data.registered_address} onChange={e => setData('registered_address', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="business_type">Business Type</Label>
                                    <Input id="business_type" value={data.business_type} onChange={e => setData('business_type', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="industry">Industry</Label>
                                    <Input id="industry" value={data.industry} onChange={e => setData('industry', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="website">Website</Label>
                                    <Input id="website" type="url" value={data.website} onChange={e => setData('website', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="contact_email">Contact Email *</Label>
                                    <Input id="contact_email" type="email" value={data.contact_email} onChange={e => setData('contact_email', e.target.value)} required />
                                    <InputError message={errors.contact_email} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="contact_phone">Contact Phone</Label>
                                    <Input id="contact_phone" value={data.contact_phone} onChange={e => setData('contact_phone', e.target.value)} />
                                </div>
                            </div>
                            <Button type="submit" disabled={processing}>
                                {business ? 'Update Profile' : 'Create Profile'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
