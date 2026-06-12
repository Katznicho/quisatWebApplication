<x-mail::message>
# New business registration

A new business has registered on **{{ config('app.name') }}**.

## Business details
- **Name:** {{ $business->name }}
- **Email:** {{ $business->email }}
- **Phone:** {{ $business->phone }}
- **Category:** {{ $categoryName ?? 'Not set' }}
- **Location:** {{ $business->address }}, {{ $business->city }}, {{ $business->country }}
- **Account number:** {{ $business->account_number }}
@if($business->website_link)
- **Website:** {{ $business->website_link }}
@endif

## Admin account
- **Name:** {{ $adminUser->name }}
- **Email:** {{ $adminUser->email }}
- **Phone:** {{ $adminUser->phone ?? 'Not provided' }}

<x-mail::button :url="url('/login')">
View dashboard
</x-mail::button>

Thanks,<br>
**{{ config('app.name') }}**
</x-mail::message>
