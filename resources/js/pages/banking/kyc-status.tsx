import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { 
    CheckCircle2, 
    Clock, 
    AlertCircle, 
    FileCheck, 
    Calendar,
    User,
    MapPin,
    FileText,
    Eye,
    Bell
} from 'lucide-react';
import { kyc as kycRoute } from '@/routes/banking';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

interface Document {
    id: number;
    document_type: string;
    status: string;
    created_at: string;
}

interface KycData {
    status: string;
    kyc_level: string;
    submitted: boolean;
    submitted_at?: string;
    verified_at?: string;
    rejection_reason?: string;
    documents: Document[];
    verification_details?: {
        country: string;
        nationality: string;
        date_of_birth: string;
        id_type: string;
        id_number: string;
        address: string;
        city: string;
        postal_code: string;
        occupation?: string;
    };
}

export default function KycStatus() {
    const { props, auth } = usePage();
    const [kycData, setKycData] = useState<KycData>(props.kyc_data as KycData);
    const [isExpandedDetails, setIsExpandedDetails] = useState(false);
    const [showNotification, setShowNotification] = useState(false);

    useEffect(() => {
        // Set up Echo listener for real-time status updates
        if (window.Echo) {
            const channel = window.Echo.private(`user.${auth.user?.id}`);
            channel.listen('kyc.status_changed', (data: any) => {
                setKycData(prev => ({
                    ...prev,
                    status: data.status,
                    kyc_level: data.kyc_level,
                    verified_at: new Date().toISOString(),
                }));
                setShowNotification(true);
                setTimeout(() => setShowNotification(false), 5000);
            });

            return () => {
                channel.stopListening('kyc.status_changed');
            };
        }
    }, [auth.user?.id]);

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved':
                return 'bg-emerald-500/10 border-emerald-500/20 text-emerald-700 dark:text-emerald-300';
            case 'pending':
                return 'bg-amber-500/10 border-amber-500/20 text-amber-700 dark:text-amber-300';
            case 'rejected':
                return 'bg-red-500/10 border-red-500/20 text-red-700 dark:text-red-300';
            default:
                return 'bg-slate-500/10 border-slate-500/20 text-slate-700 dark:text-slate-300';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved':
                return <CheckCircle2 className="w-6 h-6 text-emerald-500" />;
            case 'pending':
                return <Clock className="w-6 h-6 text-amber-500" />;
            case 'rejected':
                return <AlertCircle className="w-6 h-6 text-red-500" />;
            default:
                return <FileText className="w-6 h-6 text-slate-500" />;
        }
    };

    const completionPercentage = kycData.submitted
        ? kycData.status === 'approved'
            ? 100
            : kycData.status === 'pending'
            ? 66
            : 33
        : 0;

    const statusMessages = {
        approved: 'Your identity has been verified successfully. You have full access to all platform features.',
        pending: 'Your KYC submission is under review. This typically takes 1-2 business days.',
        rejected: 'Your KYC submission was rejected. Please review the reason and resubmit with valid documents.',
        unsubmitted: 'Complete your KYC verification to unlock all features and increase your transaction limits.',
    };

    const documentTypeLabels: Record<string, string> = {
        identity_document: 'Identity Document',
        proof_of_address: 'Proof of Address',
        proof_of_income: 'Proof of Income',
    };

    return (
        <>
            <Head title="KYC Status" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                {/* Real-time Update Notification */}
                {showNotification && (
                    <Alert className={`${getStatusColor(kycData.status)}`}>
                        <Bell className="h-4 w-4" />
                        <AlertDescription className="ml-2">
                            Your KYC status has been updated! Current status: <strong>{kycData.status}</strong>
                        </AlertDescription>
                    </Alert>
                )}

                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">KYC Status</h1>
                    <p className="text-muted-foreground mt-2">Track your identity verification progress</p>
                </div>

                {/* Main Status Card */}
                <Card className="border-l-4" style={{
                    borderLeftColor: kycData.status === 'approved' ? '#10b981' : kycData.status === 'pending' ? '#f59e0b' : '#ef4444'
                }}>
                    <CardContent className="pt-6">
                        <div className="flex items-start justify-between">
                            <div className="flex items-start gap-6">
                                <div className="flex-shrink-0">
                                    {getStatusIcon(kycData.status)}
                                </div>
                                <div>
                                    <h2 className="text-2xl font-bold capitalize">{kycData.status}</h2>
                                    <p className="text-muted-foreground mt-2 max-w-2xl">
                                        {statusMessages[kycData.status as keyof typeof statusMessages] || statusMessages.unsubmitted}
                                    </p>
                                    {kycData.rejection_reason && (
                                        <Alert className="mt-4 bg-red-500/10 border-red-500/20">
                                            <AlertCircle className="h-4 w-4 text-red-500" />
                                            <AlertDescription className="text-red-700 dark:text-red-300 ml-2">
                                                <strong>Rejection Reason:</strong> {kycData.rejection_reason}
                                            </AlertDescription>
                                        </Alert>
                                    )}
                                </div>
                            </div>
                            <Badge 
                                className="capitalize"
                                variant={kycData.status === 'approved' ? 'secondary' : 'outline'}
                            >
                                {kycData.status}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                {/* Progress and Timeline */}
                <div className="grid md:grid-cols-3 gap-6">
                    {/* Progress */}
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Verification Progress</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div>
                                <div className="flex justify-between text-sm mb-2">
                                    <span className="font-medium">Overall Progress</span>
                                    <span className="text-muted-foreground">{completionPercentage}%</span>
                                </div>
                                <Progress value={completionPercentage} className="h-3" />
                            </div>

                            {/* Timeline */}
                            <div className="space-y-4">
                                <div className="flex gap-4">
                                    <div className="flex flex-col items-center gap-2">
                                        <div className="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center border border-emerald-500/50">
                                            <CheckCircle2 className="w-5 h-5 text-emerald-500" />
                                        </div>
                                        <div className="w-1 h-8 bg-emerald-500/30"></div>
                                    </div>
                                    <div className="pb-4">
                                        <h4 className="font-semibold text-emerald-700 dark:text-emerald-300">Submitted</h4>
                                        <p className="text-sm text-muted-foreground">
                                            {kycData.submitted_at 
                                                ? new Date(kycData.submitted_at).toLocaleDateString()
                                                : 'Not yet submitted'
                                            }
                                        </p>
                                    </div>
                                </div>

                                <div className="flex gap-4">
                                    <div className="flex flex-col items-center gap-2">
                                        <div className={`w-10 h-10 rounded-full flex items-center justify-center border ${
                                            kycData.status === 'pending' || kycData.status === 'approved'
                                                ? 'bg-blue-500/20 border-blue-500/50'
                                                : 'bg-slate-500/20 border-slate-500/50'
                                        }`}>
                                            <Clock className={`w-5 h-5 ${
                                                kycData.status === 'pending' || kycData.status === 'approved'
                                                    ? 'text-blue-500'
                                                    : 'text-slate-500'
                                            }`} />
                                        </div>
                                        <div className={`w-1 h-8 ${
                                            kycData.status === 'approved' 
                                                ? 'bg-emerald-500/30'
                                                : 'bg-slate-500/20'
                                        }`}></div>
                                    </div>
                                    <div className="pb-4">
                                        <h4 className={`font-semibold ${
                                            kycData.status === 'pending' || kycData.status === 'approved'
                                                ? 'text-blue-700 dark:text-blue-300'
                                                : 'text-muted-foreground'
                                        }`}>In Review</h4>
                                        <p className="text-sm text-muted-foreground">
                                            {kycData.status === 'pending' ? '1-2 business days' : 'Not started'}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex gap-4">
                                    <div className="flex flex-col items-center gap-2">
                                        <div className={`w-10 h-10 rounded-full flex items-center justify-center border ${
                                            kycData.status === 'approved'
                                                ? 'bg-emerald-500/20 border-emerald-500/50'
                                                : 'bg-slate-500/20 border-slate-500/50'
                                        }`}>
                                            <CheckCircle2 className={`w-5 h-5 ${
                                                kycData.status === 'approved'
                                                    ? 'text-emerald-500'
                                                    : 'text-slate-500'
                                            }`} />
                                        </div>
                                    </div>
                                    <div>
                                        <h4 className={`font-semibold ${
                                            kycData.status === 'approved'
                                                ? 'text-emerald-700 dark:text-emerald-300'
                                                : 'text-muted-foreground'
                                        }`}>Approved</h4>
                                        <p className="text-sm text-muted-foreground">
                                            {kycData.verified_at 
                                                ? new Date(kycData.verified_at).toLocaleDateString()
                                                : 'Pending'
                                            }
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Quick Stats */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Verification Level</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Level</span>
                                <Badge>{kycData.kyc_level}</Badge>
                            </div>
                            <div className="flex items-center justify-between pt-4 border-t">
                                <span className="text-muted-foreground">Documents</span>
                                <span className="font-semibold">{kycData.documents.length}/3</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Submitted Documents */}
                {kycData.documents.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileCheck className="w-5 h-5" />
                                Submitted Documents
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid md:grid-cols-3 gap-4">
                                {kycData.documents.map((doc) => (
                                    <Card key={doc.id} className="border">
                                        <CardContent className="pt-6">
                                            <div className="flex items-start justify-between mb-4">
                                                <FileText className="w-5 h-5 text-blue-500 flex-shrink-0" />
                                                <Badge 
                                                    variant={doc.status === 'approved' ? 'secondary' : 'outline'}
                                                    className="capitalize"
                                                >
                                                    {doc.status}
                                                </Badge>
                                            </div>
                                            <h4 className="font-semibold text-sm">
                                                {documentTypeLabels[doc.document_type] || doc.document_type}
                                            </h4>
                                            <p className="text-xs text-muted-foreground mt-2">
                                                Submitted {new Date(doc.created_at).toLocaleDateString()}
                                            </p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Verification Details */}
                {kycData.verification_details && (
                    <Card>
                        <CardHeader 
                            className="cursor-pointer hover:bg-muted/50 transition"
                            onClick={() => setIsExpandedDetails(!isExpandedDetails)}
                        >
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2">
                                    <User className="w-5 h-5" />
                                    Submitted Information
                                </CardTitle>
                                <Eye className="w-4 h-4 text-muted-foreground" />
                            </div>
                        </CardHeader>
                        {isExpandedDetails && (
                            <CardContent>
                                <div className="grid md:grid-cols-2 gap-6">
                                    <div className="space-y-3">
                                        <div>
                                            <p className="text-xs font-semibold text-muted-foreground uppercase">Country</p>
                                            <p className="text-sm font-medium">{kycData.verification_details.country}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-muted-foreground uppercase">Nationality</p>
                                            <p className="text-sm font-medium">{kycData.verification_details.nationality}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-muted-foreground uppercase">Date of Birth</p>
                                            <p className="text-sm font-medium">{new Date(kycData.verification_details.date_of_birth).toLocaleDateString()}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-muted-foreground uppercase">ID Type</p>
                                            <p className="text-sm font-medium capitalize">{kycData.verification_details.id_type.replace('_', ' ')}</p>
                                        </div>
                                    </div>
                                    <div className="space-y-3">
                                        <div>
                                            <p className="text-xs font-semibold text-muted-foreground uppercase">ID Number</p>
                                            <p className="text-sm font-medium">{kycData.verification_details.id_number}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-muted-foreground uppercase">Address</p>
                                            <p className="text-sm font-medium">{kycData.verification_details.address}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-muted-foreground uppercase">City</p>
                                            <p className="text-sm font-medium">{kycData.verification_details.city}, {kycData.verification_details.postal_code}</p>
                                        </div>
                                        {kycData.verification_details.occupation && (
                                            <div>
                                                <p className="text-xs font-semibold text-muted-foreground uppercase">Occupation</p>
                                                <p className="text-sm font-medium">{kycData.verification_details.occupation}</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        )}
                    </Card>
                )}

                {/* Action Buttons */}
                {kycData.status !== 'approved' && (
                    <div className="flex gap-4">
                        <Button asChild>
                            <a href={kycRoute()}>
                                {kycData.status === 'rejected' ? 'Resubmit KYC' : 'Complete KYC Verification'}
                            </a>
                        </Button>
                        <Button variant="outline">Contact Support</Button>
                    </div>
                )}
            </div>
        </>
    );
}

KycStatus.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'KYC Status', href: '/banking/kyc-status' },
    ],
};
