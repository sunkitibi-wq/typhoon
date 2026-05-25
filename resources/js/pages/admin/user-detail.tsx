import { Head, Link, router } from '@inertiajs/react';
import { User, Mail, Calendar, Shield, CreditCard, ArrowLeft, Edit2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface UserData {
    id: number;
    name: string;
    email: string;
    kyc_level: string;
    phone: string | null;
    nationality: string | null;
    status: string;
    created_at: string;
    role_id: number;
    role?: { name: string };
    two_factor_enabled?: boolean;
    kycVerification?: { status: string; kyc_level: string };
    accounts: Array<{ id: number; label: string; number: string; balance: number; currency: string; status: string }>;
    cryptoWallets: Array<{ id: number; balance: number; locked_balance: number; cryptoCurrency?: { code: string; name: string } }>;
}

const getCurrencySymbol = (currency: string) => {
    switch (currency) {
        case 'EUR': return '€';
        case 'USD': return '$';
        case 'GBP': return '£';
        case 'CHF': return 'CHF';
        default: return currency;
    }
};

export default function UserDetail({ user, portfolio }: { user: UserData; portfolio: { total_portfolio_value_eur: number; fiat_total_eur: number; crypto_total_eur: number } }) {
    const handleApprove = (accountId: number) => {
        if (window.confirm('Are you sure you want to approve this account?')) {
            router.post(route('admin.accounts.approve', accountId));
        }
    };

    const handleReject = (accountId: number) => {
        if (window.confirm('Are you sure you want to reject this account?')) {
            router.post(route('admin.accounts.reject', accountId));
        }
    };

    const handleFreeze = (accountId: number) => {
        if (window.confirm('Are you sure you want to freeze this account?')) {
            router.post(route('admin.accounts.freeze', accountId));
        }
    };

    const handleUnfreeze = (accountId: number) => {
        if (window.confirm('Are you sure you want to unfreeze this account?')) {
            router.post(route('admin.accounts.unfreeze', accountId));
        }
    };

    const handleClose = (accountId: number) => {
        if (window.confirm('Are you sure you want to close this account? This cannot be undone.')) {
            router.post(route('admin.accounts.close', accountId));
        }
    };

    return (
        <>
            <Head title={`User: ${user.name}`} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.users')} className="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1">
                        <ArrowLeft className="h-4 w-4" />Back to Users
                    </Link>
                </div>
                
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">{user.name}</h1>
                    <Link href={route('admin.users.edit', user.id)}>
                        <Button variant="outline">
                            <Edit2 className="mr-1 h-4 w-4" /> Edit User
                        </Button>
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Portfolio Value</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">€{portfolio.total_portfolio_value_eur.toLocaleString()}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Fiat Total</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">€{portfolio.fiat_total_eur.toLocaleString()}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Crypto Total</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">€{portfolio.crypto_total_eur.toLocaleString()}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">KYC Level</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{user.kyc_level || 'None'}</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>User Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center gap-2">
                                <User className="size-4 text-muted-foreground" />
                                <span>{user.name}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Mail className="size-4 text-muted-foreground" />
                                <span>{user.email}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Calendar className="size-4 text-muted-foreground" />
                                <span>Joined {new Date(user.created_at).toLocaleDateString()}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Shield className="size-4 text-muted-foreground" />
                                <span>Role: {user.role?.name ?? 'N/A'}</span>
                            </div>
                            {user.phone && (
                                <div className="flex items-center gap-2">
                                    <span className="text-muted-foreground">Phone:</span>
                                    <span>{user.phone}</span>
                                </div>
                            )}
                            {user.nationality && (
                                <div className="flex items-center gap-2">
                                    <span className="text-muted-foreground">Nationality:</span>
                                    <span>{user.nationality}</span>
                                </div>
                            )}
                            <div className="flex gap-2">
                                <Badge variant={user.status === 'active' ? 'default' : 'secondary'}>
                                    {user.status}
                                </Badge>
                                {user.two_factor_enabled && <Badge variant="secondary">2FA Enabled</Badge>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Accounts ({user.accounts.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {user.accounts.map(a => (
                                <div key={a.id} className="flex flex-col gap-2 rounded-lg border p-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <CreditCard className="size-4 text-muted-foreground" />
                                            <div>
                                                <p className="text-sm font-medium">{a.label}</p>
                                                <p className="text-xs text-muted-foreground">****{a.number.slice(-4)}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm font-bold">{getCurrencySymbol(a.currency)}{a.balance.toLocaleString()}</p>
                                            <Badge variant={
                                                a.status === 'active' ? 'default' :
                                                a.status === 'pending' ? 'outline' :
                                                a.status === 'frozen' ? 'secondary' :
                                                'destructive'
                                            } className="text-[10px] capitalize">
                                                {a.status}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div className="flex justify-end gap-2 border-t pt-2 mt-1">
                                        {a.status === 'pending' && (
                                            <>
                                                <Button 
                                                    variant="outline" 
                                                    size="sm" 
                                                    className="h-7 text-xs border-emerald-600/50 hover:bg-emerald-600/10 text-emerald-500"
                                                    onClick={() => handleApprove(a.id)}
                                                >
                                                    Approve
                                                </Button>
                                                <Button 
                                                    variant="outline" 
                                                    size="sm" 
                                                    className="h-7 text-xs border-rose-600/50 hover:bg-rose-600/10 text-rose-500"
                                                    onClick={() => handleReject(a.id)}
                                                >
                                                    Reject
                                                </Button>
                                            </>
                                        )}
                                        {a.status === 'active' && (
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                className="h-7 text-xs border-amber-600/50 hover:bg-amber-600/10 text-amber-500"
                                                onClick={() => handleFreeze(a.id)}
                                            >
                                                Freeze
                                            </Button>
                                        )}
                                        {a.status === 'frozen' && (
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                className="h-7 text-xs border-emerald-600/50 hover:bg-emerald-600/10 text-emerald-500"
                                                onClick={() => handleUnfreeze(a.id)}
                                            >
                                                Unfreeze
                                            </Button>
                                        )}
                                        {a.status !== 'closed' && a.status !== 'rejected' && (
                                            <Button 
                                                variant="destructive" 
                                                size="sm" 
                                                className="h-7 text-xs"
                                                onClick={() => handleClose(a.id)}
                                            >
                                                Close
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                            {user.accounts.length === 0 && <p className="text-sm text-muted-foreground">No accounts</p>}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

UserDetail.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'Users', href: '/admin/users' },
        { title: 'User Details', href: '#' },
    ],
};
