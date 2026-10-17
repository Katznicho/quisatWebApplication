<div class="space-y-3 text-sm text-slate-700">
    <p><span class="font-medium text-slate-900">Child:</span> {{ $visit->child?->full_name }} · {{ $visit->child?->ageLabel() ?: 'Age not recorded' }}</p>
    <p><span class="font-medium text-slate-900">Visit:</span> {{ \App\Models\WalkinVisit::query()->where('walkin_child_id', $visit->walkin_child_id)->where('id', '<', $visit->id)->exists() ? 'Returning' : 'First time' }}</p>
    <p><span class="font-medium text-slate-900">Class:</span> {{ $visit->classRoom?->name ?: '—' }}</p>
    <p><span class="font-medium text-slate-900">Service:</span> {{ $visit->service_name ?: '—' }} · {{ optional($visit->service_date)->toFormattedDateString() }}</p>
    <p><span class="font-medium text-slate-900">Guardian:</span> {{ $visit->child?->family?->guardian_name }} · {{ $visit->child?->family?->phone }}</p>
    <p><span class="font-medium text-slate-900">Relationship:</span> {{ $visit->child?->family?->relationship }}</p>
    <p><span class="font-medium text-slate-900">Authorised collector:</span> {{ $visit->collector_name ?: $visit->child?->family?->collector_name }}</p>
    <p><span class="font-medium text-slate-900">Care notes:</span> {{ $visit->child?->care_notes ?: 'None' }}</p>
    <p><span class="font-medium text-slate-900">Consent:</span> Participation recorded. Follow-up {{ $visit->child?->family?->follow_up_consent ? 'accepted' : 'not accepted' }}.</p>
    <p><span class="font-medium text-slate-900">Checked in:</span> {{ optional($visit->checked_in_at)->timezone(auth()->user()?->business?->timezoneName() ?: 'Africa/Nairobi')->format('H:i') }} by {{ $visit->checkedInBy?->name ?: '—' }}</p>
    <p><span class="font-medium text-slate-900">Status:</span> {{ $visit->statusLabel() }}</p>
    @if($visit->status === 'checked_out')
        <p><span class="font-medium text-slate-900">Checked out:</span> {{ optional($visit->checked_out_at)->timezone(auth()->user()?->business?->timezoneName() ?: 'Africa/Nairobi')->format('H:i') }} by {{ $visit->checkedOutBy?->name ?: '—' }}</p>
        @if($visit->exception_reason)
            <p><span class="font-medium text-slate-900">Missing-code approval:</span> {{ $visit->exception_reason }}</p>
        @endif
    @endif
    <div>
        <p class="font-medium text-slate-900">Other children in this family at this service</p>
        @forelse($siblings as $sibling)
            <p>{{ $sibling->child?->full_name }} · {{ $sibling->classRoom?->name ?: 'No class' }} · {{ $sibling->statusLabel() }}</p>
        @empty
            <p>No other children are recorded for this service.</p>
        @endforelse
    </div>
</div>
