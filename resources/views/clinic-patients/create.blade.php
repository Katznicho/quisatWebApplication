@extends('layouts.app')

@section('content')
@php
    $activeTab = old('entry_mode');
    if (!$activeTab) {
        $activeTab = request('tab', !empty($accessCode) ? 'import' : 'manual');
    }
    if (! in_array($activeTab, ['import', 'manual'], true)) {
        $activeTab = 'manual';
    }
@endphp
<div class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="mb-6">
        <a href="{{ route('clinic-patients.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-1"></i> Back to patients
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-3">Register clinic patient</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            You can either import a child from school using their access code, or register a child directly at the clinic
            for walk-ins and children who are not linked to a school.
        </p>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <p class="font-medium">Please fix the highlighted fields and try again.</p>
        </div>
    @endif

    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-3">
            <a href="{{ route('clinic-patients.create', ['tab' => 'import']) }}"
               class="px-4 py-3 text-sm font-semibold border-b-2 {{ $activeTab === 'import' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Import from school
            </a>
            <a href="{{ route('clinic-patients.create', ['tab' => 'manual']) }}"
               class="px-4 py-3 text-sm font-semibold border-b-2 {{ $activeTab === 'manual' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Create child patient
            </a>
        </nav>
    </div>

    @if($activeTab === 'import')
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Import from school</h2>
            <p class="text-sm text-gray-600 mb-4">
                Ask the parent for their child&apos;s school access code from the Quisat parent app.
            </p>

            <form method="GET" action="{{ route('clinic-patients.create') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="tab" value="import">
                <div class="flex-1">
                    <label for="access_code" class="sr-only">Child access code</label>
                    <input type="text"
                           name="access_code"
                           id="access_code"
                           value="{{ old('access_code', $accessCode ?? '') }}"
                           placeholder="e.g. CHD-A1B2C3D4"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-lg uppercase tracking-wide focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           autocomplete="off"
                           autofocus>
                </div>
                <button type="submit"
                        class="px-6 py-3 bg-gray-800 text-white font-semibold rounded-lg hover:bg-gray-900 whitespace-nowrap">
                    <i class="fas fa-search mr-2"></i>Look up
                </button>
            </form>

            @if(isset($lookupError))
                <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-lg mt-4">
                    {{ $lookupError }}
                </div>
            @endif

            @if(isset($previewStudent) && $previewStudent)
                <div class="mt-6 bg-white rounded-lg overflow-hidden border-2 border-green-200">
                    <div class="bg-green-50 px-6 py-3 border-b border-green-200">
                        <p class="text-sm font-semibold text-green-800">Child found — review before importing</p>
                    </div>
                    <div class="p-6">
                        <div class="flex gap-4">
                            @if($previewStudent->photo)
                                <img src="{{ Storage::url($previewStudent->photo) }}"
                                     alt=""
                                     class="w-20 h-20 rounded-full object-cover border">
                            @else
                                <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-child text-3xl text-gray-400"></i>
                                </div>
                            @endif
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900">{{ $previewStudent->full_name }}</h3>
                                <p class="text-sm font-mono text-blue-700 mt-1">{{ $previewStudent->access_code }}</p>
                                @if($previewStudent->date_of_birth)
                                    <p class="text-sm text-gray-600 mt-2">DOB: {{ $previewStudent->date_of_birth->format('d M Y') }}</p>
                                @endif
                                @if($previewStudent->gender)
                                    <p class="text-sm text-gray-600">Gender: {{ ucfirst($previewStudent->gender) }}</p>
                                @endif
                                @if($previewStudent->classRoom)
                                    <p class="text-sm text-gray-600">Class: {{ $previewStudent->classRoom->name }}</p>
                                @endif
                                @if($previewStudent->business)
                                    <p class="text-sm text-gray-600 mt-2">
                                        <i class="fas fa-school mr-1"></i>School: <strong>{{ $previewStudent->business->name }}</strong>
                                    </p>
                                @endif
                                @if($previewStudent->parentGuardian)
                                    <p class="text-sm text-gray-600">
                                        Parent: {{ $previewStudent->parentGuardian->full_name }}
                                        @if($previewStudent->parentGuardian->phone)
                                            · {{ $previewStudent->parentGuardian->phone }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>

                        @if($alreadyRegistered ?? false)
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-sm text-blue-800">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    This child is already registered at your clinic
                                    @if($existingPatient ?? null)
                                        as <strong>{{ $existingPatient->patient_number }}</strong>.
                                        <a href="{{ route('clinic-patients.show', $existingPatient) }}" class="underline font-medium">View record</a>
                                    @endif
                                </p>
                            </div>
                        @else
                            <form action="{{ route('clinic-patients.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6">
                                @csrf
                                <input type="hidden" name="entry_mode" value="import">
                                <input type="hidden" name="child_access_code" value="{{ $previewStudent->access_code }}">
                                <div class="border-t pt-6">
                                    <h4 class="text-base font-semibold text-gray-900 mb-2">Complete clinic details</h4>
                                    <p class="text-sm text-gray-600 mb-4">
                                        School identity details stay locked from the school record. Fill any clinic-specific details below before importing.
                                    </p>
                                    @include('clinic-patients._form-fields', [
                                        'useRepeaters' => true,
                                        'importedFromSchool' => true,
                                        'patient' => $previewStudent,
                                    ])
                                </div>
                                <button type="submit"
                                        class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-file-import mr-2"></i>Import patient to clinic
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Register directly at clinic</h2>
            <p class="text-sm text-gray-600 mb-6">
                Use this when a child does not come from a school record. Add the child and parent or guardian details here,
                and the clinic family will be created automatically.
            </p>

            <form action="{{ route('clinic-patients.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="entry_mode" value="manual">

                <h3 class="text-base font-semibold text-gray-900 mb-4">Patient details</h3>
                @include('clinic-patients._form-fields', ['useRepeaters' => true, 'importedFromSchool' => false, 'manualGuardianEntry' => true])

                <div class="mt-8 flex justify-end">
                    <button type="submit"
                            class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
                        <i class="fas fa-user-plus mr-2"></i>Register patient
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="mt-8 p-4 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-600">
        <p class="font-medium text-gray-800 mb-1">For clinic staff</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Use <strong>Import from school</strong> when the child already has a Quisat school record.</li>
            <li>Use <strong>Register directly at clinic</strong> for walk-ins or children without a school profile.</li>
            <li>You can later add clinic-only details such as allergies, emergency contacts, and appointments.</li>
        </ul>
    </div>
</div>
@include('clinic-patients._repeater-script')
@endsection
