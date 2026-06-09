import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { 
    CreditCard, ShieldAlert, CheckCircle, XCircle, Search, 
    ArrowRight, Eye, Landmark, Ban, Unlock, ShieldCheck
} from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
}

interface AccountType {
    id: number;
    code: string;
    name: string;
}

interface BankAccount {
    id: number;
    user_id: number;
    account_number: string;
    iban: string | null;
    swift_bic: string | null;
    currency: string;
    balance: number;
    available_balance: number;
    status: string;
    label: string | null;
    created_at: string;
    user?: User;
    account_type?: AccountType;
}

interface PaginatedData {
    data: BankAccount[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    accounts: PaginatedData;
    filters: {
        status?: string;
        search?: string;
    };
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

export default function AdminAccounts({ accounts, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearchChange = (val: string) => {
        setSearch(val);
        updateFilters(val, statusFilter);
    };

    const handleStatusFilterChange = (status: string) => {
        setStatusFilter(status);
        updateFilters(search, status);
    };

    const updateFilters = (searchVal: string, statusVal: string) => {
        router.get(
            route('admin.accounts'),
            { search: searchVal, status: statusVal },
            { preserveState: true, replace: true }
        );
    };

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
            <Head title="Bank Accounts Oversight" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Bank Accounts Oversight</h1>
                        <p className="text-sm text-muted-foreground mt-1">
                            Monitor, approve, freeze, and manage user SEPA & SWIFT accounts globally.
                        </p>
                    </div>
                </div>

                {/* Filter and Search Bar */}
                <div className="flex flex-col md:flex-row gap-4 items-center justify-between bg-card border rounded-lg p-4">
                    <div className="relative w-full md:max-w-sm">
                        <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder="Search accounts, IBANs, users..."
                            value={search}
                            onChange={(e) => handleSearchChange(e.target.value)}
                            className="pl-9"
                        />
                    </div>
                    
                    <div className="flex flex-wrap gap-2 w-full md:w-auto">
                        <Button 
                            variant={statusFilter === '' ? 'default' : 'outline'} 
                            size="sm"
                            onClick={() => handleStatusFilterChange('')}
                        >
                            All
                        </Button>
                        <Button 
                            variant={statusFilter === 'pending' ? 'default' : 'outline'} 
                            size="sm"
                            className={statusFilter === 'pending' ? '' : 'text-amber-500 border-amber-600/30 hover:bg-amber-600/10'}
                            onClick={() => handleStatusFilterChange('pending')}
                        >
                            Pending Approval
                        </Button>
                        <Button 
                            variant={statusFilter === 'active' ? 'default' : 'outline'} 
                            size="sm"
                            className={statusFilter === 'active' ? '' : 'text-emerald-500 border-emerald-600/30 hover:bg-emerald-600/10'}
                            onClick={() => handleStatusFilterChange('active')}
                        >
                            Active
                        </Button>
                        <Button 
                            variant={statusFilter === 'frozen' ? 'default' : 'outline'} 
                            size="sm"
                            className={statusFilter === 'frozen' ? '' : 'text-indigo-500 border-indigo-600/30 hover:bg-indigo-600/10'}
                            onClick={() => handleStatusFilterChange('frozen')}
                        >
                            Frozen
                        </Button>
                        <Button 
                            variant={statusFilter === 'rejected' ? 'default' : 'outline'} 
                            size="sm"
                            className={statusFilter === 'rejected' ? '' : 'text-rose-500 border-rose-600/30 hover:bg-rose-600/10'}
                            onClick={() => handleStatusFilterChange('rejected')}
                        >
                            Rejected
                        </Button>
                        <Button 
                            variant={statusFilter === 'closed' ? 'default' : 'outline'} 
                            size="sm"
                            onClick={() => handleStatusFilterChange('closed')}
                        >
                            Closed
                        </Button>
                    </div>
                </div>

                {/* Account List Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>Accounts Queue ({accounts.total})</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {accounts.data.length === 0 && (
                            <div className="py-8 text-center text-sm text-muted-foreground flex flex-col items-center gap-2">
                                <Landmark className="size-8 text-muted-foreground/50" />
                                <p>No bank accounts found matching the criteria.</p>
                            </div>
                        )}

                        {accounts.data.map(account => (
                            <div 
                                key={account.id} 
                                className={`flex flex-col md:flex-row items-start md:items-center justify-between rounded-lg border p-4 gap-4 transition-all hover:bg-accent/10 ${
                                    account.status === 'pending' ? 'border-l-4 border-l-amber-500' : 
                                    account.status === 'frozen' ? 'border-l-4 border-l-indigo-500' : ''
                                }`}
                            >
                                <div className="flex items-start gap-3 min-w-0 flex-1">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary shrink-0 mt-1">
                                        <CreditCard className="size-5" />
                                    </div>
                                    <div className="min-w-0 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-semibold text-sm">
                                                {account.label || account.account_type?.name || 'Bank Account'}
                                            </span>
                                            <Badge variant={
                                                account.status === 'active' ? 'default' :
                                                account.status === 'pending' ? 'outline' :
                                                account.status === 'frozen' ? 'secondary' :
                                                'destructive'
                                            } className="text-[10px] capitalize">
                                                {account.status}
                                            </Badge>
                                        </div>
                                        <p className="text-xs font-mono text-muted-foreground flex flex-wrap gap-x-4 gap-y-1">
                                            <span>No: {account.account_number}</span>
                                            {account.iban && <span>IBAN: {account.iban}</span>}
                                            {account.swift_bic && <span>BIC: {account.swift_bic}</span>}
                                        </p>
                                        {account.user && (
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <span>User: <strong>{account.user.name}</strong> ({account.user.email})</span>
                                                <span>•</span>
                                                <span>Created: {new Date(account.created_at).toLocaleDateString()}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="flex flex-row md:flex-col items-end gap-2 w-full md:w-auto shrink-0 justify-between md:justify-center border-t md:border-t-0 pt-3 md:pt-0 mt-2 md:mt-0">
                                    <div className="text-left md:text-right">
                                        <p className="text-lg font-bold text-foreground">
                                            {getCurrencySymbol(account.currency)}{Number(account.balance).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2 flex-wrap">
                                        {account.user && (
                                            <Link href={route('admin.users.detail', account.user.id)}>
                                                <Button variant="outline" size="sm" className="h-8 text-xs">
                                                    <Eye className="mr-1 h-3.5 w-3.5" /> User
                                                </Button>
                                            </Link>
                                        )}
                                        
                                        {account.status === 'pending' && (
                                            <>
                                                <Button 
                                                    variant="outline" 
                                                    size="sm" 
                                                    className="h-8 text-xs border-emerald-600/50 hover:bg-emerald-600/10 text-emerald-500"
                                                    onClick={() => handleApprove(account.id)}
                                                >
                                                    <ShieldCheck className="mr-1 h-3.5 w-3.5" /> Approve
                                                </Button>
                                                <Button 
                                                    variant="outline" 
                                                    size="sm" 
                                                    className="h-8 text-xs border-rose-600/50 hover:bg-rose-600/10 text-rose-500"
                                                    onClick={() => handleReject(account.id)}
                                                >
                                                    <XCircle className="mr-1 h-3.5 w-3.5" /> Reject
                                                </Button>
                                            </>
                                        )}

                                        {account.status === 'active' && (
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                className="h-8 text-xs border-amber-600/50 hover:bg-amber-600/10 text-amber-500"
                                                onClick={() => handleFreeze(account.id)}
                                            >
                                                <Ban className="mr-1 h-3.5 w-3.5" /> Freeze
                                            </Button>
                                        )}

                                        {account.status === 'frozen' && (
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                className="h-8 text-xs border-emerald-600/50 hover:bg-emerald-600/10 text-emerald-500"
                                                onClick={() => handleUnfreeze(account.id)}
                                            >
                                                <Unlock className="mr-1 h-3.5 w-3.5" /> Unfreeze
                                            </Button>
                                        )}

                                        {account.status !== 'closed' && account.status !== 'rejected' && (
                                            <Button 
                                                variant="destructive" 
                                                size="sm" 
                                                className="h-8 text-xs"
                                                onClick={() => handleClose(account.id)}
                                            >
                                                Close
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}

                        {/* Pagination Links */}
                        {accounts.links && accounts.links.length > 3 && (
                            <div className="flex justify-center gap-1 mt-6 pt-4 border-t">
                                {accounts.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url || '#'}
                                        className={`px-3 py-1.5 text-xs border rounded-md transition-colors ${
                                            link.active
                                                ? 'bg-primary text-white border-primary font-medium'
                                                : 'bg-background hover:bg-muted text-foreground'
                                        } ${!link.url ? 'opacity-50 pointer-events-none' : ''}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminAccounts.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'Bank Accounts', href: '/admin/accounts' },
    ],
};
