<x-mail::message>
# Transfer {{ ucfirst($direction) }}

A transfer has been **{{ $direction }}** on your account.

**Reference:** {{ $transaction->reference }}
**Amount:** &euro;{{ number_format($transaction->amount, 2) }}
**Date:** {{ $transaction->created_at->format('d M Y H:i') }}
**Description:** {{ $transaction->description ?? 'No description' }}

<x-mail::button :url="url('/banking/transactions')">
View Transaction
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
