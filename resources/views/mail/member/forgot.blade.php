<x-mail::message>
# Forgot Your Password?

We received a request to reset your password. Please use the following code to reset your password:

## Reset Code:
**{{ $otpCode }}**

> **Important:** This code is valid for **5 minutes** only.

If you did not request a password reset, please ignore this email.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
