<x-mail::message>
# Account Statement

Hello,

Please find attached the account statement for **{{ $business->name }}** covering **{{ $statement['from']->format('M j, Y') }}** to **{{ $statement['to']->format('M j, Y') }}**.

@if ($customMessage)
{{ $customMessage }}

@endif

**Statement summary**

- Statement number: {{ $statement['statement_number'] }}
- Opening balance: {{ $statement['currency'] }} {{ number_format($statement['opening_balance'], 0) }}
- Total credits: {{ $statement['currency'] }} {{ number_format($statement['total_credits'], 0) }}
- Total debits & fees: {{ $statement['currency'] }} {{ number_format($statement['total_debits'] + $statement['total_fees'], 0) }}
- Closing balance: {{ $statement['currency'] }} {{ number_format($statement['closing_balance'], 0) }}

The full transaction breakdown is attached as a PDF.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
