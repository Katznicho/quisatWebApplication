<x-mail::message>
# Welcome to {{ config('app.name') }}! 🎉

Dear **{{ $business->name }}**,

Thank you for registering your business with **{{ config('app.name') }}**! We're excited to have you on board.

## Your Business Details:
- **Business Name:** {{ $business->name }}
- **Account Number:** {{ $business->account_number }}
- **Email:** {{ $business->email }}
- **Phone:** {{ $business->phone }}
- **Address:** {{ $business->address }}, {{ $business->city }}, {{ $business->country }}

## What's Next?

1. **Verify Your Email:** Check your email for a verification link from your admin account
2. **Complete Setup:** Your admin user will receive login credentials
3. **Start Using:** Access your dashboard and begin managing your business

## Need Help?

If you have any questions or need assistance, please don't hesitate to contact our support team.

Thanks,<br>
**The {{ config('app.name') }} Team**
</x-mail::message>
