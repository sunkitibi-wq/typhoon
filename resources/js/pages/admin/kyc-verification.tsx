import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Clock, 
    CheckCircle2, 
    XCircle,
    FileText,
    User,
    Search,
    Loader
} from 'lucide-react';
import { router } from '@inertiajs/react';

interface KycSubmission {
    id: number;
    user_id: number;
    user_name: string;
    user_email: string;
    status: 'pending' | 'approved' | 'rejected';
    kyc_level: string;
    country: string;
    date_of_birth: string;
    id_type: string;
    id_number: string;
    address_line1: string;
    city: string;
    postal_code: string;
    documents_count: number;
    created_at: string;
    submitted_at: string;
    verified_at?: string;
    verification_details: {
        user: {
            id: number;
            name: string;
            email: string;
            phone?: string;
        };
        documents: {
            id: number;
            document_type: string;
            status: string;
            created_at: string;
        }[];
    };
}

interface Props {
    submissions: KycSubmission[];
    stats: {
        pending: number;
        approved: number;
        rejected: number;
        total: number;
    };
}

export default function KycVerification({ submissions, stats }: Props) {
    const [filter, setFilter] = useState<'all' | 'pending' | 'approved' | 'rejected'>('pending');
    const [search, setSearch] = useState('');
    const [selectedSubmission, setSelectedSubmission] = useState<KycSubmission | null>(null);
    const [rejectionReason, setRejectionReason] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);

    const filtered = submissions.filter(sub => {
        const matchesStatus = filter === 'all' || sub.status === filter;
        const matchesSearch = 
            sub.user_name.toLowerCase().includes(search.toLowerCase()) ||
            sub.user_email.toLowerCase().includes(search.toLowerCase()) ||
            sub.id_number.toLowerCase().includes(search.toLowerCase());
        return matchesStatus && matchesSearch;
    });

    const handleApprove = async (submission: KycSubmission) => {
        if (!window.confirm(`Approve KYC for ${submission.user_name}?`)) return;

        setIsProcessing(true);
        router.post(`/admin/kyc/${submission.id}/approve`, {}, {
            onSuccess: () => {
                setIsProcessing(false);
                setSelectedSubmission(null);
            },
            onError: () => {
                setIsProcessing(false);
                alert('Error approving KYC');
            },
        });
    };

    const handleReject = async (submission: KycSubmission) => {
        if (!rejectionReason.trim()) {
            alert('Please provide a rejection reason');
            return;
        }

        setIsProcessing(true);
        router.post(`/admin/kyc/${submission.id}/reject`, {
            rejection_reason: rejectionReason,
        }, {
            onSuccess: () => {
                setIsProcessing(false);
                setSelectedSubmission(null);
                setRejectionReason('');
            },
            onError: () => {
                setIsProcessing(false);
                alert('Error rejecting KYC');
            },
        });
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved':
                return <CheckCircle2 className="w-5 h-5 text-emerald-500" />;
            case 'pending':
                return <Clock className="w-5 h-5 text-amber-500" />;
            case 'rejected':
                return <XCircle className="w-5 h-5 text-red-500" />;
            default:
                return null;
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved':
                return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
            case 'pending':
                return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
            case 'rejected':
                return 'bg-red-500/10 text-red-700 dark:text-red-300';
            default:
                return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
        }
    };

    return (
        <>
            <Head title="KYC Verification Queue" />
            <div className="space-y-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">KYC Verification Queue</h1>
                    <p className="text-muted-foreground mt-2">Review and approve customer identity verifications</p>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Total Submissions</p>
                                    <p className="text-3xl font-bold mt-2">{stats.total}</p>
                                </div>
                                <FileText className="w-8 h-8 text-slate-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Pending</p>
                                    <p className="text-3xl font-bold mt-2 text-amber-600">{stats.pending}</p>
                                </div>
                                <Clock className="w-8 h-8 text-amber-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Approved</p>
                                    <p className="text-3xl font-bold mt-2 text-emerald-600">{stats.approved}</p>
                                </div>
                                <CheckCircle2 className="w-8 h-8 text-emerald-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Rejected</p>
                                    <p className="text-3xl font-bold mt-2 text-red-600">{stats.rejected}</p>
                                </div>
                                <XCircle className="w-8 h-8 text-red-500" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters and Search */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Filter & Search</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex gap-4 items-end">
                            <div className="flex-1">
                                <Label htmlFor="search" className="text-sm">Search by name, email, or ID</Label>
                                <div className="relative mt-2">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder="Search submissions..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <Tabs value={filter} onValueChange={(v: any) => setFilter(v)}>
                                <TabsList>
                                    <TabsTrigger value="all">All</TabsTrigger>
                                    <TabsTrigger value="pending">Pending</TabsTrigger>
                                    <TabsTrigger value="approved">Approved</TabsTrigger>
                                    <TabsTrigger value="rejected">Rejected</TabsTrigger>
                                </TabsList>
                            </Tabs>
                        </div>
                    </CardContent>
                </Card>

                {/* Submissions List */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="w-5 h-5" />
                            KYC Submissions ({filtered.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {filtered.length === 0 ? (
                                <div className="text-center py-8">
                                    <p className="text-muted-foreground">No submissions found</p>
                                </div>
                            ) : (
                                filtered.map((submission) => (
                                    <Dialog key={submission.id}>
                                        <DialogTrigger asChild>
                                            <div 
                                                onClick={() => setSelectedSubmission(submission)}
                                                className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 cursor-pointer transition"
                                            >
                                                <div className="flex items-center gap-4 flex-1">
                                                    <div className="flex-shrink-0">
                                                        {getStatusIcon(submission.status)}
                                                    </div>
                                                    <div className="flex-1">
                                                        <h4 className="font-semibold">{submission.user_name}</h4>
                                                        <p className="text-sm text-muted-foreground">{submission.user_email}</p>
                                                        <div className="flex gap-2 mt-2">
                                                            <Badge variant="outline">{submission.country}</Badge>
                                                            <Badge variant="outline" className="capitalize">{submission.id_type.replace('_', ' ')}</Badge>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <Badge className={`${getStatusColor(submission.status)} capitalize`}>
                                                        {submission.status}
                                                    </Badge>
                                                    <p className="text-xs text-muted-foreground mt-2">
                                                        {new Date(submission.created_at).toLocaleDateString()}
                                                    </p>
                                                </div>
                                            </div>
                                        </DialogTrigger>
                                        <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                                            <DialogHeader>
                                                <DialogTitle>KYC Review - {submission.user_name}</DialogTitle>
                                                <DialogDescription>
                                                    Review and approve or reject this KYC submission
                                                </DialogDescription>
                                            </DialogHeader>

                                            <div className="space-y-6">
                                                {/* User Info */}
                                                <div className="space-y-3">
                                                    <h4 className="font-semibold flex items-center gap-2">
                                                        <User className="w-4 h-4" />
                                                        User Information
                                                    </h4>
                                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                                        <div>
                                                            <p className="text-muted-foreground">Name</p>
                                                            <p className="font-medium">{submission.verification_details.user.name}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-muted-foreground">Email</p>
                                                            <p className="font-medium">{submission.verification_details.user.email}</p>
                                                        </div>
                                                        {submission.verification_details.user.phone && (
                                                            <div>
                                                                <p className="text-muted-foreground">Phone</p>
                                                                <p className="font-medium">{submission.verification_details.user.phone}</p>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Verification Details */}
                                                <div className="border-t pt-6 space-y-3">
                                                    <h4 className="font-semibold flex items-center gap-2">
                                                        <FileText className="w-4 h-4" />
                                                        Verification Details
                                                    </h4>
                                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                                        <div>
                                                            <p className="text-muted-foreground">Country</p>
                                                            <p className="font-medium">{submission.country}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-muted-foreground">Date of Birth</p>
                                                            <p className="font-medium">{new Date(submission.date_of_birth).toLocaleDateString()}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-muted-foreground">ID Type</p>
                                                            <p className="font-medium capitalize">{submission.id_type.replace('_', ' ')}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-muted-foreground">ID Number</p>
                                                            <p className="font-medium">{submission.id_number}</p>
                                                        </div>
                                                        <div className="col-span-2">
                                                            <p className="text-muted-foreground">Address</p>
                                                            <p className="font-medium">{submission.address_line1}, {submission.city}, {submission.postal_code}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Documents */}
                                                {submission.verification_details.documents.length > 0 && (
                                                    <div className="border-t pt-6 space-y-3">
                                                        <h4 className="font-semibold">Submitted Documents ({submission.verification_details.documents.length})</h4>
                                                        <div className="grid gap-2">
                                                            {submission.verification_details.documents.map((doc) => (
                                                                <div key={doc.id} className="flex items-center justify-between p-3 border rounded">
                                                                    <div>
                                                                        <p className="text-sm font-medium capitalize">{doc.document_type.replace('_', ' ')}</p>
                                                                        <p className="text-xs text-muted-foreground">{new Date(doc.created_at).toLocaleDateString()}</p>
                                                                    </div>
                                                                    <Badge className={getStatusColor(doc.status)} variant="outline">
                                                                        {doc.status}
                                                                    </Badge>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Actions */}
                                                {submission.status === 'pending' && (
                                                    <div className="border-t pt-6 space-y-3">
                                                        <h4 className="font-semibold">Decision</h4>
                                                        <div className="space-y-3">
                                                            <Button 
                                                                onClick={() => handleApprove(submission)}
                                                                disabled={isProcessing}
                                                                className="w-full bg-emerald-600 hover:bg-emerald-700"
                                                            >
                                                                {isProcessing && <Loader className="w-4 h-4 mr-2 animate-spin" />}
                                                                Approve KYC
                                                            </Button>
                                                            <div className="space-y-2">
                                                                <Label htmlFor="rejection-reason">Rejection Reason (if rejecting)</Label>
                                                                <textarea
                                                                    id="rejection-reason"
                                                                    className="w-full border rounded px-3 py-2 text-sm"
                                                                    placeholder="Provide a reason for rejection..."
                                                                    rows={3}
                                                                    value={rejectionReason}
                                                                    onChange={(e) => setRejectionReason(e.target.value)}
                                                                />
                                                            </div>
                                                            <Button 
                                                                onClick={() => handleReject(submission)}
                                                                disabled={isProcessing || !rejectionReason.trim()}
                                                                variant="destructive"
                                                                className="w-full"
                                                            >
                                                                {isProcessing && <Loader className="w-4 h-4 mr-2 animate-spin" />}
                                                                Reject KYC
                                                            </Button>
                                                        </div>
                                                    </div>
                                                )}

                                                {submission.status !== 'pending' && (
                                                    <Alert className={submission.status === 'approved' ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-red-500/10 border-red-500/20'}>
                                                        <AlertDescription className={submission.status === 'approved' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'}>
                                                            <strong>Status: {submission.status.toUpperCase()}</strong>
                                                            {submission.verified_at && (
                                                                <p className="text-sm mt-1">{new Date(submission.verified_at).toLocaleString()}</p>
                                                            )}
                                                        </AlertDescription>
                                                    </Alert>
                                                )}
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                ))
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

KycVerification.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'KYC Queue', href: '/admin/kyc' },
    ],
};
