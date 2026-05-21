import { Head, Link } from '@inertiajs/react';
import { User, Mail, Calendar, Shield, CreditCard, Wallet, TrendingUp, ArrowLeft } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface UserData {
    id: number; name: string; email: string; kyc_level: string; phone: string | null;
    nationality: string | null; status: string; created_at: string;
    role?: { name: string }; two_factor_enabled?: boolean;
    kycVerification?: { status: string; kyc_level: string };
    accounts: Array<{ id: number; label: string; number: string; balance: number; currency: string; status: string }>;
    cryptoWallets: Array<{ id: number; balance: number; locked_balance: number; cryptoCurrency?: { code: string; name: string } }>;
}

export default function UserDetail({ user, portfolio }: { user: UserData; portfolio: { total_portfolio_value_eur: number; fiat_total_eur: number; crypto_total_eur: number } }) {
    return (
        <>
            <Head title={`User: ${user.name}`} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.users')} className="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1">
                        <ArrowLeft className="h-4 w-4" />Back to Users
                    </Link>
                </div>
                <h1 className="text-2xl font-bold">{user.name}</h1>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Portfolio Value</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{portfolio.total_portfolio_value_eur.toLocaleString()}</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Fiat Total</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{portfolio.fiat_total_eur.toLocaleString()}</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Crypto Total</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">€{portfolio.crypto_total_eur.toLocaleString()}</p></CardContent></Card>
                    <Card><CardHeader className="pb-2"><CardTitle className="text-sm">KYC Level</CardTitle></CardHeader><CardContent><p className="text-2xl font-bold">{user.kyc_level || 'None'}</p></CardContent></Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>User Details</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center gap-2"><User className="size-4 text-muted-foreground" /><span>{user.name}</span></div>
                            <div className="flex items-center gap-2"><Mail className="size-4 text-muted-foreground" /><span>{user.email}</span></div>
                            <div className="flex items-center gap-2"><Calendar className="size-4 text-muted-foreground" /><span>Joined {new Date(user.created_at).toLocaleDateString()}</span></div>
                            <div className="flex items-center gap-2"><Shield className="size-4 text-muted-foreground" /><span>Role: {user.role?.name ?? 'N/A'}</span></div>
                            {user.phone && <div className="flex items-center gap-2"><span className="text-muted-foreground">Phone:</span><span>{user.phone}</span></div>}
                            {user.nationality && <div className="flex items-center gap-2"><span className="text-muted-foreground">Nationality:</span><span>{user.nationality}</span></div>}
                            <div className="flex gap-2">
                                <Badge variant={user.status === 'active' ? 'default' : 'secondary'}>{user.status}</Badge>
                                {user.two_factor_enabled && <Badge variant="secondary">2FA Enabled</Badge>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Accounts ({user.accounts.length})</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            {user.accounts.map(a => (
                                <div key={a.id} className="flex items-center justify-between rounded-lg border p-2">
                                    <div className="flex items-center gap-2">
                                        <CreditCard className="size-4 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm font-medium">{a.label}</p>
                                            <p className="text-xs text-muted-foreground">****{a.number.slice(-4)}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-bold">€{a.balance.toLocaleString()}</p>
                                        <Badge variant="outline" className="text-[10px]">{a.status}</Badge>
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
