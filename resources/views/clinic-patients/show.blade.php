@extends('layouts.app')

@section('content')
@php
    $activeTab = request('tab', 'overview');
    if (! in_array($activeTab, ['overview', 'appointments', 'visits', 'vaccinations', 'growth', 'documents'], true)) {
        $activeTab = 'overview';
    }
    $statusClasses = $patient->status === 'active'
        ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
        : 'bg-rose-100 text-rose-800 border border-rose-200';
    $appointmentCount = $patient->appointments->count();
    $nextAppointment = $patient->appointments
        ->where('status', 'scheduled')
        ->sortBy('scheduled_at')
        ->first();
    $documentCount = $patient->documents()->count();
    $ageText = 'Not recorded';
    if ($patient->date_of_birth) {
        $years = $patient->date_of_birth->age;
        if ($years >= 1) {
            $ageText = $years.' '.\Illuminate\Support\Str::plural('year', $years);
        } else {
            $months = max(1, $patient->date_of_birth->diffInMonths(now()));
            $ageText = $months.' '.\Illuminate\Support\Str::plural('month', $months);
        }
    }
@endphp

<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('clinic-patients.index') }}" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to patients
            </a>
            <h1 class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $patient->full_name }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Patient profile · {{ $patient->patient_number }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('clinic-patients.edit', $patient) }}"
               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                <i class="fas fa-edit mr-2"></i>Edit patient
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-6 py-8 text-white">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-5">
                    @if($patient->photo)
                        <img src="{{ Storage::url($patient->photo) }}"
                             alt="{{ $patient->full_name }}"
                             class="h-24 w-24 rounded-2xl border-4 border-white/20 object-cover shadow-lg">
                    @else
                        <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-white/10 text-3xl text-white shadow-lg">
                            <i class="fas fa-user-injured"></i>
                        </div>
                    @endif

                    <div>
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                                Clinic patient
                            </span>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                {{ ucfirst($patient->status) }}
                            </span>
                            <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">
                                Age: {{ $ageText }}
                            </span>
                            @if($patient->school_access_code)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                                    Linked by code
                                </span>
                            @endif
                        </div>

                        <h2 class="text-2xl font-bold">{{ $patient->full_name }}</h2>
                        <p class="mt-1 text-sm text-slate-200">Patient no. {{ $patient->patient_number }} · Age {{ $ageText }}</p>

                        @if($patient->parentGuardian)
                            <p class="mt-3 text-sm text-slate-100">
                                Primary guardian: <span class="font-semibold">{{ $patient->parentGuardian->full_name }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 lg:min-w-[360px]">
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Date of birth</p>
                        <p class="mt-1 text-sm font-semibold">{{ $patient->date_of_birth?->format('d M Y') ?? 'Not recorded' }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Age</p>
                        <p class="mt-1 text-sm font-semibold">{{ $ageText }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Gender</p>
                        <p class="mt-1 text-sm font-semibold">{{ $patient->gender ? ucfirst($patient->gender) : 'Not recorded' }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Blood group</p>
                        <p class="mt-1 text-sm font-semibold">{{ $patient->blood_group ?? 'Not recorded' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 bg-slate-50 px-6 py-5 md:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Next appointment</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">
                    {{ $nextAppointment ? $nextAppointment->scheduled_at->format('d M Y, H:i') : 'None scheduled' }}
                </p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Appointments</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $appointmentCount }} recorded</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Documents</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $documentCount }} uploaded</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">School access code</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $patient->school_access_code ?? 'Not linked' }}</p>
            </div>
        </div>
    </div>

    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex flex-wrap gap-2">
            <a href="{{ route('clinic-patients.show', ['clinic_patient' => $patient->id, 'tab' => 'overview']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'overview' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Overview
            </a>
            <a href="{{ route('clinic-patients.show', ['clinic_patient' => $patient->id, 'tab' => 'appointments']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'appointments' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Appointments
            </a>
            <a href="{{ route('clinic-patients.show', ['clinic_patient' => $patient->id, 'tab' => 'visits']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'visits' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Visits / EMR
            </a>
            <a href="{{ route('clinic-patients.show', ['clinic_patient' => $patient->id, 'tab' => 'vaccinations']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'vaccinations' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Vaccinations
            </a>
            <a href="{{ route('clinic-patients.show', ['clinic_patient' => $patient->id, 'tab' => 'growth']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'growth' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Growth
            </a>
            <a href="{{ route('clinic-patients.show', ['clinic_patient' => $patient->id, 'tab' => 'documents']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'documents' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Documents
            </a>
        </nav>
    </div>

    @if($activeTab === 'overview')
        <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">Clinical overview</h2>
                        <p class="mt-1 text-sm text-slate-500">Core patient details, guardian contact, and record source.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 px-6 py-6 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Full name</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $patient->full_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Patient number</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $patient->patient_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Date of birth</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $patient->date_of_birth?->format('d M Y') ?? 'Not recorded' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Age</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $ageText }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Gender</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $patient->gender ? ucfirst($patient->gender) : 'Not recorded' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Blood group</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $patient->blood_group ?? 'Not recorded' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Primary guardian</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $patient->parentGuardian?->full_name ?? 'Not linked' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Guardian contact</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $patient->parentGuardian?->phone ?? 'Not recorded' }}</p>
                            @if($patient->parentGuardian?->email)
                                <p class="mt-1 text-xs text-slate-500">{{ $patient->parentGuardian->email }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-900">Allergies</h3>
                        </div>
                        <div class="px-6 py-5">
                            @if(!empty($patient->allergies))
                                <div class="flex flex-wrap gap-2">
                                    @foreach($patient->allergies as $allergy)
                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-sm font-medium text-amber-800">
                                            {{ $allergy }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-slate-500">No allergies recorded.</p>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-900">Insurance</h3>
                        </div>
                        <div class="px-6 py-5">
                            @if(!empty($patient->insurance_info))
                                <p class="text-sm font-semibold text-slate-900">{{ $patient->insurance_info['provider'] ?? 'Not recorded' }}</p>
                                <p class="mt-1 text-sm text-slate-600">
                                    Policy: {{ $patient->insurance_info['policy_number'] ?? 'Not recorded' }}
                                </p>
                            @else
                                <p class="text-sm text-slate-500">No insurance details recorded.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-900">Emergency contacts</h3>
                    </div>
                    <div class="px-6 py-5">
                        @if(!empty($patient->emergency_contacts))
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach($patient->emergency_contacts as $contact)
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <p class="text-sm font-semibold text-slate-900">{{ $contact['name'] ?? 'Contact' }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $contact['relationship'] ?? 'Relationship not recorded' }}</p>
                                        <p class="mt-2 text-sm text-slate-900">{{ $contact['phone'] ?? 'No phone recorded' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No emergency contacts recorded.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-900">Record source</h3>
                    </div>
                    <div class="space-y-4 px-6 py-5">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Registration type</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $patient->student ? 'Imported from school' : 'Registered directly at clinic' }}</p>
                        </div>

                        @if($patient->student && $patient->student->business)
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">School</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $patient->student->business->name }}</p>
                                @if($patient->student->student_id)
                                    <p class="mt-1 text-xs text-slate-500">School ID: {{ $patient->student->student_id }}</p>
                                @endif
                            </div>
                        @endif

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">School access code</p>
                            @if($patient->school_access_code)
                                <div class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                                    <p class="font-mono text-sm font-bold text-emerald-900">{{ $patient->school_access_code }}</p>
                                    <p class="mt-1 text-xs text-emerald-700">Parents use this code in the Quisat app to link this child.</p>
                                </div>
                            @else
                                <p class="mt-1 text-sm text-slate-500">No school access code linked.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-900">Family and guardians</h3>
                    </div>
                    <div class="space-y-4 px-6 py-5">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Family</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $patient->family?->family_name ?: 'Auto-created clinic family' }}</p>
                        </div>

                        @if($patient->family && $patient->family->members->count())
                            <div class="space-y-3">
                                @foreach($patient->family->members as $member)
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <p class="text-sm font-semibold text-slate-900">{{ $member->parentGuardian?->full_name ?? 'Parent / Guardian' }}</p>
                                        <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">{{ $member->relationship }}</p>
                                        @if($member->parentGuardian?->phone)
                                            <p class="mt-2 text-sm text-slate-700">{{ $member->parentGuardian->phone }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No additional guardians linked.</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                    <h3 class="text-base font-semibold text-slate-900">More clinical modules</h3>
                    <p class="mt-2 text-sm text-slate-500">Vaccinations, growth charts, visit notes, and other care workflows can be added as additional patient tabs next.</p>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'appointments')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.patient-appointments-table :patient="$patient" :wire:key="'patient-appointments-'.$patient->id" />
        </div>
    @elseif($activeTab === 'visits')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.patient-visits-table :patient="$patient" :wire:key="'patient-visits-'.$patient->id" />
        </div>
    @elseif($activeTab === 'vaccinations')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.patient-vaccinations-table :patient="$patient" :wire:key="'patient-vaccinations-'.$patient->id" />
        </div>
    @elseif($activeTab === 'growth')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.patient-growth-table :patient="$patient" :wire:key="'patient-growth-'.$patient->id" />
        </div>
    @elseif($activeTab === 'documents')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.patient-documents-table :patient="$patient" :wire:key="'patient-documents-'.$patient->id" />
        </div>
    @endif
</div>
@endsection
