@extends('layouts.app')

@section('content')
@php
    $activeTab = request('tab', 'patients');
    if (! in_array($activeTab, ['overview', 'patients', 'appointments', 'doctors', 'consultations', 'appointment-types', 'services'], true)) {
        $activeTab = 'patients';
    }
@endphp
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kids Clinics Dashboard</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Professional clinic workspace for patient intake, doctors, consultation records, and appointment setup.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('clinic-patients.create') }}"
               class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                <i class="fas fa-user-plus mr-2"></i>Add patient
            </a>
            <a href="{{ route('clinic-patients.index', ['tab' => 'doctors']) }}"
               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i class="fas fa-user-md mr-2"></i>Manage doctors
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
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                        Clinic operations
                    </span>
                    <h2 class="mt-4 text-2xl font-bold">Manage care delivery from one module</h2>
                    <p class="mt-2 max-w-2xl text-sm text-slate-200">
                        Configure doctors, appointment types, and parent-facing services once, then use them across
                        appointments, consultations, and the mobile app.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Patients</p>
                        <p class="mt-1 text-2xl font-bold">{{ $stats['patients'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Doctors</p>
                        <p class="mt-1 text-2xl font-bold">{{ $stats['doctors'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Consultations</p>
                        <p class="mt-1 text-2xl font-bold">{{ $stats['consultations'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Scheduled visits</p>
                        <p class="mt-1 text-2xl font-bold">{{ $stats['scheduled_appointments'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 bg-slate-50 px-6 py-5 md:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Appointment types</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['appointment_types'] ?? 0 }} configured</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Services (app)</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['services'] ?? 0 }} listed for parents</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Quick action</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">Register or import child patients</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Dropdown setup</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">Doctors and types power patient forms</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Patient workspace</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">Appointments, visits, growth, vaccines, documents</p>
            </div>
        </div>
    </div>

    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex flex-wrap gap-2">
            <a href="{{ route('clinic-patients.index', ['tab' => 'overview']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'overview' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Overview
            </a>
            <a href="{{ route('clinic-patients.index', ['tab' => 'patients']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'patients' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Patients
            </a>
            <a href="{{ route('clinic-patients.index', ['tab' => 'appointments']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'appointments' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Appointments
            </a>
            <a href="{{ route('clinic-patients.index', ['tab' => 'doctors']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'doctors' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Doctors
            </a>
            <a href="{{ route('clinic-patients.index', ['tab' => 'consultations']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'consultations' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Consultations
            </a>
            <a href="{{ route('clinic-patients.index', ['tab' => 'appointment-types']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'appointment-types' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Appointment Types
            </a>
            <a href="{{ route('clinic-patients.index', ['tab' => 'services']) }}"
               class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === 'services' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Services
            </a>
        </nav>
    </div>

    @if($activeTab === 'overview')
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                <h3 class="text-lg font-semibold text-slate-900">Clinic workflow</h3>
                <p class="mt-2 text-sm text-slate-500">
                    Use this dashboard to configure doctors and appointment types first, then manage patients and consultations
                    using consistent dropdown-driven forms across the clinic module.
                </p>
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">1. Set up doctors</p>
                        <p class="mt-1 text-sm text-slate-600">Add each doctor once so appointments and consultations can use dropdown selection.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">2. Define appointment types</p>
                        <p class="mt-1 text-sm text-slate-600">Control the list of consultation, review, vaccination, and follow-up types used by staff.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">3. Add clinic services</p>
                        <p class="mt-1 text-sm text-slate-600">List services parents see in the app (check-ups, vaccinations, reviews).</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">4. Register or import patients</p>
                        <p class="mt-1 text-sm text-slate-600">Add children manually or import them from school using their access code.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">5. Record care</p>
                        <p class="mt-1 text-sm text-slate-600">Open each patient to manage appointments, consultations, vaccinations, growth, and documents.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Recommended setup</h3>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li>Start with <strong>Doctors</strong> so booking forms are standardized.</li>
                    <li>Add <strong>Appointment Types</strong> for staff booking and consultation forms.</li>
                    <li>Add <strong>Services</strong> or <strong>Appointment Types</strong> so parents can book visits from the Quisat app.</li>
                    <li>Parents book from the app; staff book from each patient&apos;s <strong>Appointments</strong> tab.</li>
                    <li>Use the <strong>Patients</strong> tab to navigate directly into each child record.</li>
                    <li>Review all clinic activity under <strong>Consultations</strong>.</li>
                </ul>
            </div>
        </div>
    @elseif($activeTab === 'patients')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.clinic-dashboard-patients-table />
        </div>
    @elseif($activeTab === 'appointments')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.clinic-dashboard-appointments-table />
        </div>
    @elseif($activeTab === 'doctors')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.clinic-doctors-table />
        </div>
    @elseif($activeTab === 'consultations')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.clinic-consultations-table />
        </div>
    @elseif($activeTab === 'appointment-types')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <livewire:clinic-patients.clinic-appointment-types-table />
        </div>
    @elseif($activeTab === 'services')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if(\Illuminate\Support\Facades\Schema::hasTable('clinic_services'))
                <livewire:clinic-patients.clinic-services-table />
            @else
                <h3 class="text-lg font-semibold text-slate-900">Clinic services unavailable</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Run <code class="rounded bg-slate-100 px-1.5 py-0.5">php artisan migrate</code> on this server to enable the services table.
                </p>
            @endif
        </div>
    @endif
</div>
@endsection
