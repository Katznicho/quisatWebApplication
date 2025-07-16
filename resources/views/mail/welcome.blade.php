<x-mail::message>
    <img src="https://app.nextgentraderai.com/public/images/logo.png" alt="{{ config('app.name') }}" style="width: 150px; margin-bottom: 20px;">

# Welcome to {{ config('app.name') }}, {{ $user->name }}! 💰

Thank you for joining **{{ config('app.name') }}**, your trusted automated trading platform.

You're just one step away from activating your trading bot.

---

## 🚀 Choose Your Subscription Package & Get Started:
To begin trading, please select a subscription package and make your first deposit.  
Once subscribed, your trading bot will start analyzing the markets and executing strategies on your behalf — automatically.

<x-mail::button :url="config('app.url') . '/subscriptions'">
Choose Your Subscription Package
</x-mail::button>

> Need help? Our support team is ready to assist you at any time.

Thanks for choosing **{{ config('app.name') }}**.  
We’re excited to help you on your trading journey!

Warm regards,  
**The {{ config('app.name') }} Team**
</x-mail::message>
