<x-mail::message>
# Your Two-Factor Authentication Code

We received a request to verify your identity with Two-Factor Authentication (2FA).

## Your 2FA Code:
**{{ $otpCode }}**

> **Important:** This code is valid for **10 minutes** only.

Please enter this code in the verification screen to complete the process.

If you didn't request this, please ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
