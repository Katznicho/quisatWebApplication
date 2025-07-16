<x-mail::message>
# Welcome to {{ config('app.name') }}! 🎉

Thank you for signing up with ** {{ config('app.name') }} **. We're thrilled to have you on board!

## Your One-Time Password (OTP):
 **{{ $otpCode }}**

Please use this code to verify your email address and complete your registration.

> **Important:** This code is valid for **5 minutes** only.

To finish setting up your account, enter the code on the verification screen.

If you didn't sign up for **{{ config('app.name') }}**, please ignore this email.

---

Thank you for choosing **{{ config('app.name') }}**!
We’re here to help if you have any questions or need support.

Thanks,
**The {{ config('app.name') }} Team**

</x-mail::message>



