import { Head, router } from '@inertiajs/react';
import { ShieldCheck, ShieldX, ExternalLink } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useState } from 'react';

interface KycItem {
    id: number;
    user: { id: number; name: string; email: string };
    kyc_level: string;
    status: string;
    created_at: string;
}

export default function KycVerification({ pending }: { pending: KycItem[] }) {
    const [reason, setReason] = useState('');
    const [selectedId, setSelectedId] = useState<number | null>(null);

    const approve = (id: number) => { router.post(route('admin.kyc.approve', id)); };

    const reject = () => {
        if (selectedId && reason.trim()) {
            router.post(route('admin.kyc.reject', selectedId), { rejection_reason: reason });
            setReason('');
            setSelectedId(null);
        }
    };

    return (
        <>
            <Head title="KYC Verification" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h2 className="text-2xl font-bold tracking-tight">KYC Verification Queue</h2>
                <Card>
                    <CardHeader>
                        <CardTitle>Pending Verifications ({pending.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {pending.map(item => (
                                <div key={item.id} className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="flex items-center gap-4">
                                        <div className="flex size-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/20">
                                            <ShieldCheck className="size-5 text-amber-600" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium">{item.user.name}</p>
                                            <p className="text-xs text-muted-foreground">{item.user.email}</p>
                                            <p className="text-xs text-muted-foreground">Level: {item.kyc_level}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="outline">{item.status}</Badge>
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button variant="outline" size="sm">Review</Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogHeader>
                                                    <DialogTitle>Review KYC - {item.user.name}</DialogTitle>
                                                </DialogHeader>
                                                <div className="space-y-4">
                                                    <div className="rounded-lg border p-4">
                                                        <p className="text-sm text-muted-foreground">KYC Level: {item.kyc_level}</p>
                                                        <p className="text-sm text-muted-foreground">Submitted: {new Date(item.created_at).toLocaleDateString()}</p>
                                                    </div>
                                                    <div className="space-y-3">
                                                        <Textarea
                                                            placeholder="Rejection reason (required for reject)"
                                                            value={selectedId === item.id ? reason : ''}
                                                            onChange={e => { setSelectedId(item.id); setReason(e.target.value); }}
                                                        />
                                                        <div className="flex gap-2">
                                                            <Button className="flex-1" onClick={() => approve(item.id)}>
                                                                <ShieldCheck className="mr-2 size-4" />Approve
                                                            </Button>
                                                            <Button variant="destructive" className="flex-1" onClick={reject}>
                                                                <ShieldX className="mr-2 size-4" />Reject
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </DialogContent>
                                        </Dialog>
                                    </div>
                                </div>
                            ))}
                            {pending.length === 0 && (
                                <p className="py-8 text-center text-sm text-muted-foreground">No pending verifications</p>
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
        { title: 'KYC', href: '/admin/kyc' },
    ],
};
