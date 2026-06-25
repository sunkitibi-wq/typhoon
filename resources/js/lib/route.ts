const routes: Record<string, string> = {
    // Banking
    'banking.dashboard': '/banking/dashboard',
    'banking.accounts': '/banking/accounts',
    'banking.accounts.create': '/banking/accounts/create',
    'banking.accounts.show': '/banking/accounts/{account}',
    'banking.transfer': '/banking/transfer',
    'banking.transfer.submit': '/banking/transfer',
    'banking.transactions': '/banking/transactions',
    'banking.kyc': '/banking/kyc',
    'banking.crypto': '/banking/crypto',
    'banking.beneficiaries': '/banking/beneficiaries',
    'banking.beneficiaries.destroy': '/banking/beneficiaries/{beneficiary}',
    'banking.accounts.freeze': '/banking/accounts/{account}/freeze',
    'banking.accounts.unfreeze': '/banking/accounts/{account}/unfreeze',
    'banking.accounts.close': '/banking/accounts/{account}/close',
    'banking.standing-orders': '/banking/standing-orders',
    'banking.standing-orders.toggle': '/banking/standing-orders/{order}/toggle',
    'banking.standing-orders.destroy': '/banking/standing-orders/{order}',
    'banking.sepa': '/banking/sepa',
    'banking.swift': '/banking/swift',
    'banking.statements': '/banking/statements',
    'banking.statements.download': '/banking/statements/{account}/download',
    'banking.notifications': '/banking/notifications',
    'banking.onboarding': '/banking/onboarding',
    'banking.deposit': '/banking/deposit',
    'banking.loans': '/banking/loans',
    'banking.loans.show': '/banking/loans/{loan}',
    'banking.loans.pay': '/banking/loans/{loan}/pay',
    'banking.pos': '/banking/pos',
    'banking.pos.terminals.store': '/banking/pos/terminals',
    'banking.pos.terminals.toggle': '/banking/pos/terminals/{terminal}/toggle',
    'banking.pos.terminals.configure': '/banking/pos/terminals/{terminal}/configure',
    'banking.pos.terminals.destroy': '/banking/pos/terminals/{terminal}',
    'banking.pos.transactions.refund': '/banking/pos/transactions/{posTransaction}/refund',

    // Admin
    'admin.dashboard': '/admin/dashboard',
    'admin.kyc': '/admin/kyc',
    'admin.kyc.approve': '/admin/kyc/{kyc}/approve',
    'admin.kyc.reject': '/admin/kyc/{kyc}/reject',
    'admin.monitoring': '/admin/monitoring',
    'admin.monitoring.resolve': '/admin/monitoring/{alert}/resolve',
    'admin.users': '/admin/users',
    'admin.users.create': '/admin/users/create',
    'admin.users.store': '/admin/users/create',
    'admin.users.edit': '/admin/users/{user}/edit',
    'admin.users.update': '/admin/users/{user}/edit',
    'admin.users.detail': '/admin/users/{user}',
    'admin.fee-schedules': '/admin/fee-schedules',
    'admin.fee-schedules.toggle': '/admin/fee-schedules/{feeSchedule}/toggle',
    'admin.platform-settings': '/admin/platform-settings',
    'admin.platform-settings.update': '/admin/platform-settings/update',
    'admin.audit-logs': '/admin/audit-logs',
    'admin.api-clients': '/admin/api-clients',
    'admin.api-clients.revoke': '/admin/api-clients/{client}/revoke',
    'admin.crypto-deposits': '/admin/crypto-deposits',
    'admin.crypto-deposits.confirm': '/admin/crypto-deposits/{deposit}/confirm',
    'admin.crypto-withdrawals': '/admin/crypto-withdrawals',
    'admin.crypto-withdrawals.approve': '/admin/crypto-withdrawals/{withdrawal}/approve',
    'admin.loans': '/admin/loans',
    'admin.loans.show': '/admin/loans/{loan}',
    'admin.loans.underwrite': '/admin/loans/{loan}/underwrite',
    'admin.loans.approve': '/admin/loans/{loan}/approve',
    'admin.loans.reject': '/admin/loans/{loan}/reject',
    'admin.loans.disburse': '/admin/loans/{loan}/disburse',

    // Corporate
    'corporate.dashboard': '/corporate/dashboard',
    'corporate.bulk-payments': '/corporate/bulk-payments',
    'corporate.team': '/corporate/team',
    'corporate.business-profile': '/corporate/business-profile',

    // Webhooks
    'webhooks.banking': '/webhooks/banking/{event}',
};

function route(name: string, params?: Record<string, string | number> | string | number): string {
    let url = routes[name];
    if (!url) {
        url = '/' + name.replace(/\./g, '/');
    }

    if (params === undefined) {
        return url;
    }

    if (typeof params === 'string' || typeof params === 'number') {
        return url.replace(/\{(\w+)\}/, String(params));
    }

    let result = url;
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(params)) {
        const placeholder = `{${key}}`;
        if (result.includes(placeholder)) {
            result = result.replace(placeholder, String(value));
        } else {
            query[key] = String(value);
        }
    }

    const qs = new URLSearchParams(query).toString();
    if (qs) {
        result += '?' + qs;
    }

    return result;
}

(globalThis as any).route = route;

export default route;
