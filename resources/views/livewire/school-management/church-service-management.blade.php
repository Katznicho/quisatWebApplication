<div>
    <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-4">
        <label class="text-sm text-slate-600">
            Date
            <input type="date" wire:model.live="reportDate" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900">
        </label>
        <label class="text-sm text-slate-600">
            Service
            <select wire:model.live="reportServiceId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900">
                <option value="">All services</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm text-slate-600">
            Class
            <select wire:model.live="reportClassId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900">
                <option value="">All classes</option>
                @foreach ($classes as $classRoom)
                    <option value="{{ $classRoom->id }}">{{ $classRoom->name }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Checked in</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $checkedIn }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Checked out</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $checkedOut }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Still present</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $stillPresent }}</p>
        </div>
    </div>
    {{ $this->table }}
</div>
