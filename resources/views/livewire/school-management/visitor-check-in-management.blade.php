<div>
    <div class="mb-4">
        <label class="text-sm text-slate-600">
            Date
            <input type="date" wire:model.live="reportDate" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-slate-900">
        </label>
    </div>
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Awaiting arrival</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $awaiting }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Inside</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $inside }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Checked out</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $checkedOut }}</p>
        </div>
    </div>
    {{ $this->table }}
</div>
