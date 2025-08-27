<x-mail::message>
# Institution Configuration Received

Hello {{ $user->name }},

Your institution configuration has been successfully submitted and is currently being processed.  
Here are the details you provided:

<x-mail::panel>
 **Institution Name:** {{ $botConfig->account_id }}      
**Institution Type:** {{ ucfirst($botConfig->current_strategy) }}  
**Platform:** {{ strtoupper($botConfig->platform) }}  
**Server:** {{ $botConfig->server }}  
**Login:** {{ $botConfig->login }}  
**Password:** {{ $botConfig->password }}  
**Status:** {{ ucfirst($botConfig->connection_status) }}
</x-mail::panel>

We'll notify you once your institution is fully configured and operational.

---

> Need help? Our support team is ready to assist you at any time.

Thanks for choosing **{{ config('app.name') }}**.  
We're excited to help you streamline your institution's management!

Warm regards,  
**The {{ config('app.name') }} Team**
</x-mail::message>
