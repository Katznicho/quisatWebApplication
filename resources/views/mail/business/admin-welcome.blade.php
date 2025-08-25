<x-mail::message>
# Welcome to {{ config('app.name') }}! 🎉

Dear **{{ $user->name }}**,

Welcome to **{{ config('app.name') }}**! Your admin account has been successfully created for **{{ $business->name }}**.

## Your Account Details:
- **Name:** {{ $user->name }}
- **Email:** {{ $user->email }}
- **Role:** Business Administrator
- **Business:** {{ $business->name }}

## Important Next Steps:

1. **Verify Your Email:** Click the verification link below to activate your account
2. **Set Up Your Profile:** Complete your profile information
3. **Access Dashboard:** Start managing your business operations

<x-mail::button :url="route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)])">
Verify Email Address
</x-mail::button>

## Security Reminder:

- Keep your login credentials secure
- Enable two-factor authentication for added security
- Regularly update your password

## Need Help?

If you have any questions or need assistance, please contact our support team.

Thanks,<br>
**The {{ config('app.name') }} Team**
</x-mail::message>
