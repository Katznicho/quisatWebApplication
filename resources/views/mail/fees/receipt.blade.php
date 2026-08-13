@component('mail::message')
# School fee receipt

Hello {{ $fee->student?->parentGuardian?->first_name ?? 'Parent' }},

Payment has been recorded for **{{ $student?->full_name ?? 'your child' }}**.

- Bill: {{ $fee->fee_type }}
- Amount paid: UGX {{ number_format((float) $fee->amount_paid, 0) }}
- Balance due: UGX {{ number_format((float) $fee->remainingBalance(), 0) }}
- Receipt: {{ $fee->receipt_number ?: ('FEE-'.$fee->id) }}
@if($business)
- From: {{ $business->name }}
@endif

The receipt PDF is attached. You can also download it from the Fees tab in the Quisat app.

Thanks,<br>
{{ $business?->name ?? config('app.name') }}
@endcomponent
