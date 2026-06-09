<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Statement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1a56db; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18pt; color: #1a56db; }
        .header p { margin: 2px 0; color: #666; font-size: 9pt; }
        .summary { margin-bottom: 20px; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { padding: 4px 8px; border: 1px solid #ddd; }
        .summary td.label { background: #f3f4f6; font-weight: bold; width: 50%; }
        table.transactions { width: 100%; border-collapse: collapse; }
        table.transactions th { background: #1a56db; color: white; padding: 6px 8px; text-align: left; font-size: 9pt; }
        table.transactions td { padding: 4px 8px; border-bottom: 1px solid #eee; font-size: 9pt; }
        .amount-credit { color: #059669; }
        .amount-debit { color: #dc2626; }
        .footer { margin-top: 20px; text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Typhoon Banking - Account Statement</h1>
        <p>{{ $account->label }} ({{ $account->account_number }})</p>
        <p>{{ $statement['period']['from'] }} to {{ $statement['period']['to'] }}</p>
    </div>

    <div class="summary">
        <table>
            <tr><td class="label">Account Holder</td><td>{{ $account->user->name }}</td></tr>
            <tr><td class="label">IBAN</td><td>{{ $account->iban }}</td></tr>
            <tr><td class="label">Currency</td><td>{{ $account->currency }}</td></tr>
            <tr><td class="label">Opening Balance</td><td>&euro;{{ number_format($statement['opening_balance'], 2) }}</td></tr>
            <tr><td class="label">Closing Balance</td><td>&euro;{{ number_format($statement['closing_balance'], 2) }}</td></tr>
            <tr><td class="label">Total Credits</td><td class="amount-credit">&euro;{{ number_format($statement['total_credits'], 2) }}</td></tr>
            <tr><td class="label">Total Debits</td><td class="amount-debit">&euro;{{ number_format($statement['total_debits'], 2) }}</td></tr>
        </table>
    </div>

    <h3>Transactions ({{ $statement['transaction_count'] }})</h3>
    <table class="transactions">
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Status</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statement['transactions'] as $tx)
            <tr>
                <td>{{ $tx->created_at->format('d-m-Y') }}</td>
                <td>{{ $tx->reference }}</td>
                <td>{{ $tx->description ?? $tx->type }}</td>
                <td>{{ $tx->status }}</td>
                <td class="{{ $tx->type === 'deposit' ? 'amount-credit' : 'amount-debit' }}">
                    &euro;{{ number_format($tx->net_amount, 2) }}
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:#999;">No transactions in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Typhoon Banking Platform &bull; Generated on {{ now()->format('d-m-Y H:i:s') }}</p>
        <p>This is an auto-generated statement. For inquiries, contact support@typhoon.com</p>
    </div>
</body>
</html>
