<x-mail::message>
# Welcome to Typhoon Banking, {{ $name }}!

Your account has been successfully created.

**Account Number:** {{ $accountNumber }}

You can now:
- Make transfers and payments
- Access cryptocurrency exchange
- Manage your portfolio
- Apply for higher account limits

<x-mail::button :url="url('/banking/dashboard')">
Go to Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
