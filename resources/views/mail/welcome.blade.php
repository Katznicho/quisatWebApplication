<x-mail::message>
    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" style="width: 150px; margin-bottom: 20px;">

# Welcome to {{ config('app.name') }}, {{ $user->name }}! 🎓

Thank you for joining **{{ config('app.name') }}**, your comprehensive school and business management platform.

You're now ready to start managing your institution with our powerful tools.

---

## 🚀 Get Started with {{ config('app.name') }}:
Access your dashboard to begin managing students, staff, finances, and more.  
Our platform provides everything you need to run your institution efficiently.

<x-mail::button :url="config('app.url') . '/dashboard'">
Access Your Dashboard
</x-mail::button>

> Need help? Our support team is ready to assist you at any time.

Thanks for choosing **{{ config('app.name') }}**.  
We're excited to help you streamline your institution's management!

Warm regards,  
**The {{ config('app.name') }} Team**
</x-mail::message>
