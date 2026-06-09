import { Head, useForm } from '@inertiajs/react';
import { Shield, ShieldCheck, ShieldAlert, Upload, X, FileCheck, AlertCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Progress } from '@/components/ui/progress';
import InputError from '@/components/input-error';
import { useState, useRef } from 'react';

interface KycStatus {
    status: string;
    kyc_level: string;
    submitted: boolean;
}

interface KycDocument {
    id: number;
    document_type: string;
    file_path: string;
    status: string;
    created_at: string;
}

interface Props {
    kyc_status: KycStatus;
    kyc_documents?: KycDocument[];
}

export default function Kyc({ kyc_status, kyc_documents = [] }: Props) {
    const [selectedFiles, setSelectedFiles] = useState<Record<string, File | null>>({
        identity_document: null,
        proof_of_address: null,
        proof_of_income: null,
    });

    const fileInputRefs = useRef<Record<string, HTMLInputElement | null>>({});

    const { data, setData, post, processing, errors, progress } = useForm({
        country: '',
        nationality: '',
        date_of_birth: '',
        id_type: '',
        id_number: '',
        id_expiry_date: '',
        address_line1: '',
        address_line2: '',
        city: '',
        state: '',
        postal_code: '',
        source_of_funds: '',
        occupation: '',
        employer: '',
        annual_income_range: '',
        identity_document: null as File | null,
        proof_of_address: null as File | null,
        proof_of_income: null as File | null,
    });

    function handleFileSelect(documentType: string, file: File | null) {
        if (file) {
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                return;
            }
            // Validate file type
            const validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Only PDF, JPG, PNG, and WebP files are allowed');
                return;
            }
        }
        setSelectedFiles({
            ...selectedFiles,
            [documentType]: file,
        });
        setData({
            ...data,
            [documentType]: file as any,
        });
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/banking/kyc', {
            preserveScroll: true,
        });
    }

    const statusIcon =
        kyc_status.status === 'approved' ? (
            <ShieldCheck className="size-8 text-emerald-500" />
        ) : kyc_status.status === 'pending' ? (
            <ShieldAlert className="size-8 text-amber-500" />
        ) : (
            <Shield className="size-8 text-muted-foreground" />
        );

    const statusColor =
        kyc_status.status === 'approved'
            ? 'bg-emerald-500/10 border-emerald-500/20'
            : kyc_status.status === 'pending'
            ? 'bg-amber-500/10 border-amber-500/20'
            : 'bg-slate-500/10 border-slate-500/20';

    const completionPercentage = kyc_status.submitted
        ? kyc_status.status === 'approved'
            ? 100
            : kyc_status.status === 'pending'
            ? 66
            : 33
        : 0;

    const documentTypes = [
        { id: 'identity_document', label: 'Identity Document', description: 'Passport, National ID, or Driver\'s License', required: true },
        { id: 'proof_of_address', label: 'Proof of Address', description: 'Utility bill, Bank statement, or Government letter', required: true },
        { id: 'proof_of_income', label: 'Proof of Income', description: 'Pay stub, Tax return, or Business license', required: false },
    ];

    return (
        <>
            <Head title="KYC Verification" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Identity Verification (KYC)</h1>
                    <p className="text-muted-foreground mt-2">Complete your Know Your Customer verification to unlock all features</p>
                </div>

                {/* Status Overview */}
                <div className="grid gap-6 md:grid-cols-3">
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-8">
                            {statusIcon}
                            <h3 className="mt-4 text-lg font-semibold capitalize">{kyc_status.status.replace('_', ' ')}</h3>
                            <p className="text-sm text-muted-foreground">Level: {kyc_status.kyc_level}</p>
                            <Badge
                                className="mt-2"
                                variant={kyc_status.status === 'approved' ? 'secondary' : 'outline'}
                            >
                                {kyc_status.status}
                            </Badge>
                        </CardContent>
                    </Card>

                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Verification Progress</CardTitle>
                            <CardDescription>Complete all required steps</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="flex justify-between text-sm mb-2">
                                    <span className="font-medium">Overall Progress</span>
                                    <span className="text-muted-foreground">{completionPercentage}%</span>
                                </div>
                                <Progress value={completionPercentage} className="h-2" />
                            </div>

                            <div className="grid grid-cols-3 gap-4 text-sm">
                                <div className="flex flex-col items-center">
                                    <div
                                        className={`w-10 h-10 rounded-full flex items-center justify-center mb-2 ${
                                            kyc_status.submitted ? 'bg-emerald-500/20' : 'bg-slate-500/20'
                                        }`}
                                    >
                                        {kyc_status.submitted ? (
                                            <FileCheck className="w-5 h-5 text-emerald-500" />
                                        ) : (
                                            <span className="text-slate-400">1</span>
                                        )}
                                    </div>
                                    <span className="text-center font-medium">Submitted</span>
                                </div>
                                <div className="flex flex-col items-center">
                                    <div
                                        className={`w-10 h-10 rounded-full flex items-center justify-center mb-2 ${
                                            kyc_status.status === 'pending' || kyc_status.status === 'approved'
                                                ? 'bg-blue-500/20'
                                                : 'bg-slate-500/20'
                                        }`}
                                    >
                                        {kyc_status.status === 'pending' || kyc_status.status === 'approved' ? (
                                            <FileCheck className="w-5 h-5 text-blue-500" />
                                        ) : (
                                            <span className="text-slate-400">2</span>
                                        )}
                                    </div>
                                    <span className="text-center font-medium">In Review</span>
                                </div>
                                <div className="flex flex-col items-center">
                                    <div
                                        className={`w-10 h-10 rounded-full flex items-center justify-center mb-2 ${
                                            kyc_status.status === 'approved' ? 'bg-emerald-500/20' : 'bg-slate-500/20'
                                        }`}
                                    >
                                        {kyc_status.status === 'approved' ? (
                                            <FileCheck className="w-5 h-5 text-emerald-500" />
                                        ) : (
                                            <span className="text-slate-400">3</span>
                                        )}
                                    </div>
                                    <span className="text-center font-medium">Approved</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Alerts */}
                {kyc_status.status === 'approved' && (
                    <Alert className="bg-emerald-500/10 border-emerald-500/20">
                        <ShieldCheck className="h-4 w-4 text-emerald-500" />
                        <AlertDescription className="text-emerald-200 ml-2">
                            Your identity has been verified! You now have full access to all platform features.
                        </AlertDescription>
                    </Alert>
                )}

                {kyc_status.status === 'pending' && (
                    <Alert className={`${statusColor}`}>
                        <AlertCircle className="h-4 w-4 text-amber-500" />
                        <AlertDescription className="text-amber-200 ml-2">
                            Your KYC submission is under review. This typically takes 1-2 business days.
                        </AlertDescription>
                    </Alert>
                )}

                {kyc_status.status === 'rejected' && (
                    <Alert className="bg-red-500/10 border-red-500/20">
                        <AlertCircle className="h-4 w-4 text-red-500" />
                        <AlertDescription className="text-red-200 ml-2">
                            Your KYC submission was rejected. Please resubmit with valid documents.
                        </AlertDescription>
                    </Alert>
                )}

                {/* KYC Form */}
                {kyc_status.status !== 'approved' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Verification Details</CardTitle>
                            <CardDescription>Fill in your information and upload required documents</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Tabs defaultValue="personal" className="w-full">
                                <TabsList className="grid w-full grid-cols-3">
                                    <TabsTrigger value="personal">Personal Info</TabsTrigger>
                                    <TabsTrigger value="identity">Identity</TabsTrigger>
                                    <TabsTrigger value="documents">Documents</TabsTrigger>
                                </TabsList>

                                <form onSubmit={submit} className="mt-6 space-y-6">
                                    {/* Personal Information Tab */}
                                    <TabsContent value="personal" className="space-y-4">
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="country">Country of Residence *</Label>
                                                <Input
                                                    id="country"
                                                    placeholder="e.g. US"
                                                    maxLength={2}
                                                    value={data.country}
                                                    onChange={(e) => setData('country', e.target.value.toUpperCase())}
                                                />
                                                <InputError message={errors.country} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="nationality">Nationality *</Label>
                                                <Input
                                                    id="nationality"
                                                    placeholder="e.g. American"
                                                    value={data.nationality}
                                                    onChange={(e) => setData('nationality', e.target.value)}
                                                />
                                                <InputError message={errors.nationality} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="date_of_birth">Date of Birth *</Label>
                                                <Input
                                                    id="date_of_birth"
                                                    type="date"
                                                    value={data.date_of_birth}
                                                    onChange={(e) => setData('date_of_birth', e.target.value)}
                                                />
                                                <InputError message={errors.date_of_birth} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="occupation">Occupation</Label>
                                                <Input
                                                    id="occupation"
                                                    placeholder="Your occupation"
                                                    value={data.occupation}
                                                    onChange={(e) => setData('occupation', e.target.value)}
                                                />
                                                <InputError message={errors.occupation} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="employer">Employer</Label>
                                                <Input
                                                    id="employer"
                                                    placeholder="Your employer"
                                                    value={data.employer}
                                                    onChange={(e) => setData('employer', e.target.value)}
                                                />
                                                <InputError message={errors.employer} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="source_of_funds">Source of Funds</Label>
                                                <Select value={data.source_of_funds} onValueChange={(v) => setData('source_of_funds', v)}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select source" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="employment">Employment Income</SelectItem>
                                                        <SelectItem value="business">Business Income</SelectItem>
                                                        <SelectItem value="investment">Investment</SelectItem>
                                                        <SelectItem value="inheritance">Inheritance</SelectItem>
                                                        <SelectItem value="savings">Savings</SelectItem>
                                                        <SelectItem value="other">Other</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.source_of_funds} />
                                            </div>
                                        </div>
                                    </TabsContent>

                                    {/* Identity Tab */}
                                    <TabsContent value="identity" className="space-y-4">
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="id_type">ID Type *</Label>
                                                <Select value={data.id_type} onValueChange={(v) => setData('id_type', v)}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select ID type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="passport">Passport</SelectItem>
                                                        <SelectItem value="national_id">National ID</SelectItem>
                                                        <SelectItem value="drivers_license">Driver's License</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.id_type} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="id_number">ID Number *</Label>
                                                <Input
                                                    id="id_number"
                                                    placeholder="Your ID number"
                                                    value={data.id_number}
                                                    onChange={(e) => setData('id_number', e.target.value)}
                                                />
                                                <InputError message={errors.id_number} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="id_expiry_date">ID Expiry Date</Label>
                                                <Input
                                                    id="id_expiry_date"
                                                    type="date"
                                                    value={data.id_expiry_date}
                                                    onChange={(e) => setData('id_expiry_date', e.target.value)}
                                                />
                                                <InputError message={errors.id_expiry_date} />
                                            </div>
                                        </div>
                                    </TabsContent>

                                    {/* Address Tab */}
                                    <TabsContent value="documents" className="space-y-6">
                                        <div className="space-y-4 mb-6">
                                            <Label>Address Information</Label>
                                            <div className="grid gap-4 md:grid-cols-2">
                                                <div className="md:col-span-2 space-y-2">
                                                    <Label htmlFor="address_line1">Address Line 1 *</Label>
                                                    <Input
                                                        id="address_line1"
                                                        placeholder="Street address"
                                                        value={data.address_line1}
                                                        onChange={(e) => setData('address_line1', e.target.value)}
                                                    />
                                                    <InputError message={errors.address_line1} />
                                                </div>
                                                <div className="md:col-span-2 space-y-2">
                                                    <Label htmlFor="address_line2">Address Line 2</Label>
                                                    <Input
                                                        id="address_line2"
                                                        placeholder="Apartment, suite, etc."
                                                        value={data.address_line2}
                                                        onChange={(e) => setData('address_line2', e.target.value)}
                                                    />
                                                    <InputError message={errors.address_line2} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="city">City *</Label>
                                                    <Input
                                                        id="city"
                                                        placeholder="City"
                                                        value={data.city}
                                                        onChange={(e) => setData('city', e.target.value)}
                                                    />
                                                    <InputError message={errors.city} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="state">State/Province</Label>
                                                    <Input
                                                        id="state"
                                                        placeholder="State"
                                                        value={data.state}
                                                        onChange={(e) => setData('state', e.target.value)}
                                                    />
                                                    <InputError message={errors.state} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="postal_code">Postal Code *</Label>
                                                    <Input
                                                        id="postal_code"
                                                        placeholder="12345"
                                                        value={data.postal_code}
                                                        onChange={(e) => setData('postal_code', e.target.value)}
                                                    />
                                                    <InputError message={errors.postal_code} />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="border-t pt-6 space-y-4">
                                            <Label>Required Documents</Label>
                                            <div className="grid gap-4">
                                                {documentTypes.map((docType) => (
                                                    <div key={docType.id} className="border rounded-lg p-4">
                                                        <div className="flex justify-between items-start mb-3">
                                                            <div>
                                                                <Label className="text-base">
                                                                    {docType.label} {docType.required && <span className="text-red-500">*</span>}
                                                                </Label>
                                                                <p className="text-sm text-muted-foreground mt-1">{docType.description}</p>
                                                            </div>
                                                            {selectedFiles[docType.id] && (
                                                                <Badge variant="secondary" className="ml-2">
                                                                    Selected
                                                                </Badge>
                                                            )}
                                                        </div>

                                                        {selectedFiles[docType.id] ? (
                                                            <div className="flex items-center justify-between bg-emerald-50 dark:bg-emerald-500/10 p-3 rounded border border-emerald-200 dark:border-emerald-500/20">
                                                                <div className="flex items-center gap-2">
                                                                    <FileCheck className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                                                    <div>
                                                                        <p className="text-sm font-medium text-emerald-900 dark:text-emerald-200">
                                                                            {selectedFiles[docType.id]?.name}
                                                                        </p>
                                                                        <p className="text-xs text-emerald-700 dark:text-emerald-300">
                                                                            {(selectedFiles[docType.id]?.size || 0 / 1024 / 1024).toFixed(2)} MB
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleFileSelect(docType.id, null)}
                                                                    className="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
                                                                >
                                                                    <X className="w-5 h-5" />
                                                                </button>
                                                            </div>
                                                        ) : (
                                                            <div
                                                                className="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-500/5 transition"
                                                                onClick={() => fileInputRefs.current[docType.id]?.click()}
                                                            >
                                                                <Upload className="w-8 h-8 mx-auto mb-2 text-muted-foreground" />
                                                                <p className="text-sm font-medium">Click to upload or drag and drop</p>
                                                                <p className="text-xs text-muted-foreground">PDF, JPG, PNG or WebP (Max 10MB)</p>
                                                            </div>
                                                        )}
                                                        <input
                                                            ref={(el) => {
                                                                if (el) fileInputRefs.current[docType.id] = el;
                                                            }}
                                                            type="file"
                                                            hidden
                                                            accept="application/pdf,image/jpeg,image/png,image/webp"
                                                            onChange={(e) =>
                                                                handleFileSelect(docType.id, e.currentTarget.files?.[0] || null)
                                                            }
                                                            name={docType.id}
                                                        />
                                                        <InputError message={errors[docType.id as keyof typeof errors]} />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </TabsContent>

                                    {/* Existing Documents */}
                                    {kyc_documents.length > 0 && (
                                        <div className="border-t pt-6">
                                            <Label className="text-base mb-4">Submitted Documents</Label>
                                            <div className="grid gap-3 mt-4">
                                                {kyc_documents.map((doc) => (
                                                    <div key={doc.id} className="flex items-center justify-between p-3 border rounded">
                                                        <div className="flex items-center gap-3">
                                                            <FileCheck className="w-5 h-5 text-blue-500" />
                                                            <div>
                                                                <p className="text-sm font-medium capitalize">{doc.document_type.replace('_', ' ')}</p>
                                                                <p className="text-xs text-muted-foreground">{new Date(doc.created_at).toLocaleDateString()}</p>
                                                            </div>
                                                        </div>
                                                        <Badge
                                                            variant={doc.status === 'approved' ? 'secondary' : 'outline'}
                                                            className="capitalize"
                                                        >
                                                            {doc.status}
                                                        </Badge>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Submit Button */}
                                    <div className="flex gap-4 pt-6 border-t">
                                        <Button type="submit" disabled={processing} className="flex-1">
                                            {processing ? 'Submitting...' : 'Submit Verification'}
                                        </Button>
                                    </div>
                                </form>
                            </Tabs>
                        </CardContent>
                    </Card>
                )}
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

Kyc.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'KYC', href: '/banking/kyc' },
    ],
};
