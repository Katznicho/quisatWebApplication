<div class="space-y-4">
    @forelse($fee->payments as $payment)
        <div class="border rounded-lg p-3">
            <p class="font-semibold">UGX {{ number_format((float) $payment->amount, 0) }} · {{ $payment->methodLabel() }}</p>
            <p class="text-sm text-gray-500">
                {{ optional($payment->paid_at)->format('d M Y H:i') }}
                @if($payment->receipt_number) · Receipt {{ $payment->receipt_number }} @endif
            </p>
            @if($payment->notes)
                <p class="text-sm mt-1">{{ $payment->notes }}</p>
            @endif
            @if($payment->proof_url)
                <a href="{{ $payment->proof_url }}" target="_blank" class="text-blue-600 text-sm underline">View attached receipt</a>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500">No parent payments recorded yet.</p>
    @endforelse
</div>
