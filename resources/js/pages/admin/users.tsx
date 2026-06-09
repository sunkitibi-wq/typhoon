import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { UserPlus } from 'lucide-react';

interface UserData {
    id: number;
    name: string;
    email: string;
    kyc_level: string;
    created_at: string;
    role?: { name: string };
    accounts_count?: number;
}

export default function AdminUsers({ users }: { users: { data: UserData[] } }) {
    return (
        <>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight">User Management</h2>
                    <Link href={route('admin.users.create')}>
                        <Button><UserPlus className="mr-1 h-4 w-4" />Add User</Button>
                    </Link>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>All Users ({users.data.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-2">
                            {users.data.map(user => (
                                <Link key={user.id} href={route('admin.users.detail', user.id)} className="flex items-center justify-between rounded-lg border p-3 hover:bg-accent transition-colors">
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-9 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary">
                                            {user.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium">{user.name}</p>
                                            <p className="text-xs text-muted-foreground">{user.email}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Badge variant="secondary" className="text-[10px]">
                                            {user.role?.name ?? 'client'}
                                        </Badge>
                                        <Badge variant="outline" className="text-[10px]">
                                            KYC: {user.kyc_level}
                                        </Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(user.created_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminUsers.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'Users', href: '/admin/users' },
    ],
};
