import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid, Wallet, ArrowLeftRight, CreditCard, Shield,
    TrendingUp, BarChart3, Users, AlertTriangle, Settings, Building2, Send,
    UserPlus, CalendarClock, Landmark, Globe, FileText, Bell, Key,
    Database, Eye, DollarSign, ScrollText, Sliders, Tablet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const bankingNavItems: NavItem[] = [
    {
        title: 'Banking Overview',
        href: '/banking/dashboard',
        icon: Wallet,
    },
    {
        title: 'Accounts',
        href: '/banking/accounts',
        icon: Building2,
    },
    {
        title: 'Cards',
        href: '/banking/cards',
        icon: CreditCard,
    },
    {
        title: 'Transactions',
        href: '/banking/transactions',
        icon: ArrowLeftRight,
    },
    {
        title: 'Transfer',
        href: '/banking/transfer',
        icon: TrendingUp,
    },
    {
        title: 'SEPA Transfer',
        href: '/banking/sepa',
        icon: Landmark,
    },
    {
        title: 'SWIFT Transfer',
        href: '/banking/swift',
        icon: Globe,
    },
    {
        title: 'Crypto Exchange',
        href: '/banking/crypto',
        icon: BarChart3,
    },
    {
        title: 'POS Gateway',
        href: '/banking/pos',
        icon: Tablet,
    },
    {
        title: 'Beneficiaries',
        href: '/banking/beneficiaries',
        icon: UserPlus,
    },
    {
        title: 'Standing Orders',
        href: '/banking/standing-orders',
        icon: CalendarClock,
    },
    {
        title: 'Statements',
        href: '/banking/statements',
        icon: FileText,
    },
    {
        title: 'KYC Verification',
        href: '/banking/kyc',
        icon: Shield,
    },
    {
        title: 'Notifications',
        href: '/banking/notifications',
        icon: Bell,
    },
];

const corporateNavItems: NavItem[] = [
    {
        title: 'Corporate Dashboard',
        href: '/corporate/dashboard',
        icon: Building2,
    },
    {
        title: 'Business Profile',
        href: '/corporate/business-profile',
        icon: Building2,
    },
    {
        title: 'Team',
        href: '/corporate/team',
        icon: Users,
    },
    {
        title: 'Bulk Payments',
        href: '/corporate/bulk-payments',
        icon: Send,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
        icon: Users,
    },
    {
        title: 'KYC Queue',
        href: '/admin/kyc',
        icon: Shield,
    },
    {
        title: 'Monitoring',
        href: '/admin/monitoring',
        icon: AlertTriangle,
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Bank Accounts',
        href: '/admin/accounts',
        icon: Landmark,
    },
    {
        title: 'Crypto Deposits',
        href: '/admin/crypto-deposits',
        icon: Database,
    },
    {
        title: 'Crypto Withdrawals',
        href: '/admin/crypto-withdrawals',
        icon: Eye,
    },
    {
        title: 'Fee Schedules',
        href: '/admin/fee-schedules',
        icon: DollarSign,
    },
    {
        title: 'Platform Settings',
        href: '/admin/platform-settings',
        icon: Sliders,
    },
    {
        title: 'Audit Logs',
        href: '/admin/audit-logs',
        icon: ScrollText,
    },
    {
        title: 'API Clients',
        href: '/admin/api-clients',
        icon: Key,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: { role?: string } }>().props;
    const role = auth?.role;

    const isUser = role === 'user' || role === 'corporate';
    const isCorporate = role === 'corporate';
    const isAdmin = role === 'admin';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />

                {isUser && (
                    <>
                        <div className="px-2 py-1">
                            <div className="px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground/60">
                                Banking
                            </div>
                        </div>
                        <NavMain items={bankingNavItems} />
                    </>
                )}

                {isCorporate && (
                    <>
                        <div className="px-2 py-1">
                            <div className="px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground/60">
                                Corporate
                            </div>
                        </div>
                        <NavMain items={corporateNavItems} />
                    </>
                )}

                {isAdmin && (
                    <>
                        <div className="px-2 py-1">
                            <div className="px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground/60">
                                Administration
                            </div>
                        </div>
                        <NavMain items={adminNavItems} />
                    </>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
