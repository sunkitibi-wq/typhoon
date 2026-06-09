import { Head } from '@inertiajs/react';
import { Building2, Users, Send, Wallet } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Business {
    id: number;
    company_name: string;
    registration_number: string | null;
    business_type: string | null;
    status: string;
    verified_at: string | null;
}

interface TeamMember {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
}

interface BulkPayment {
    id: number;
    batch_reference: string;
    total_amount: number;
    total_transactions: number;
    status: string;
    created_at: string;
}

export default function CorporateDashboard({ business, team, recent_bulk_payments }: {
    business: Business | null;
    team: TeamMember[];
    recent_bulk_payments: BulkPayment[];
}) {
    return (
        <>
            <Head title="Corporate Dashboard" />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-bold">Corporate Dashboard</h1>
                    <p className="text-muted-foreground">{business?.company_name ?? 'No business profile'}</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Status</CardTitle>
                            <Building2 className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <Badge variant={business?.status === 'verified' ? 'default' : 'secondary'}>
                                {business?.status ?? 'Not registered'}
                            </Badge>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Team Members</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{team.length}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Bulk Payments</CardTitle>
                            <Send className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{recent_bulk_payments.length}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Processed</CardTitle>
                            <Wallet className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                €{recent_bulk_payments.reduce((s, p) => s + p.total_amount, 0).toLocaleString()}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Bulk Payments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recent_bulk_payments.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No bulk payments yet.</p>
                        ) : (
                            <div className="space-y-3">
                                {recent_bulk_payments.map((payment) => (
                                    <div key={payment.id} className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-medium">{payment.batch_reference}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {payment.total_transactions} transactions
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm font-medium">€{payment.total_amount.toLocaleString()}</p>
                                            <Badge variant={payment.status === 'completed' ? 'default' : 'secondary'}>
                                                {payment.status}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Team</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {team.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No team members yet.</p>
                        ) : (
                            <div className="space-y-3">
                                {team.map((member) => (
                                    <div key={member.id} className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-medium">{member.name}</p>
                                            <p className="text-xs text-muted-foreground">{member.email}</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="outline">{member.role}</Badge>
                                            <Badge variant={member.status === 'active' ? 'default' : 'secondary'}>
                                                {member.status}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
