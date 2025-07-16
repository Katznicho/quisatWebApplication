<x-mail::message>
# Bot Configuration Received

Hello {{ $user->name }},

Your bot configuration has been successfully submitted and is currently being connected.  
Here are the details you provided:

<x-mail::panel>
 **Broker:** {{ $botConfig->account_id }}      
**Broker Type:** {{ ucfirst($botConfig->current_strategy) }}  
**Platform:** {{ strtoupper($botConfig->platform) }}  
**Server:** {{ $botConfig->server }}  
**Login:** {{ $botConfig->login }}  
**Password:** {{ $botConfig->password }}  
**Connection Status:** {{ ucfirst($botConfig->connection_status) }}
</x-mail::panel>

We’ll notify you once your bot is fully connected and operational.

---

> Need help? Our support team is ready to assist you at any time.

Thanks for choosing **{{ config('app.name') }}**.  
We’re excited to help you on your trading journey!

Warm regards,  
**The {{ config('app.name') }} Team**
</x-mail::message>
