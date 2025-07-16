<x-mail::message>
<img src="https://yourdomain.com/logo.png" alt="{{ config('app.name') }}" style="width: 150px; margin-bottom: 20px;">

# 🔐 Your MarzPay API Credentials

Hi {{ auth()->user()->name }},

Below are your API credentials for securely integrating with **{{ config('app.name') }}**:

---

### 🧾 API Key  
`{{ $key }}`

### 🔑 API Secret  
`{{ $secret }}`

### 🧬 Base64 Authorization Header  
`{{ $encoded }}`

Use this in your request headers:

```http
Authorization: Basic {{ $encoded }}


Thanks,<br>
{{ env("APP_NAME") }}
</x-mail::message>
