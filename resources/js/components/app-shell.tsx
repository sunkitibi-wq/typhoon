import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const { url, props } = usePage();
    const isOpen = props.sidebarOpen;
    const isAdminArea = url.startsWith('/admin');

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    return (
        <SidebarProvider defaultOpen={isOpen} className={isAdminArea ? 'dark admin-theme' : ''}>
            {children}
        </SidebarProvider>
    );
}
