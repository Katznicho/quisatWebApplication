@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kids Clinics — Patients</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Import children from school using each child&apos;s access code (from the parent app).
            </p>
        </div>
        <a href="{{ route('clinic-patients.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
            <i class="fas fa-file-import mr-2"></i>Import by access code
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('clinic-patients.index') }}" class="mb-4 flex gap-2">
        <input type="search" name="q" value="{{ $search ?? '' }}"
               placeholder="Search by access code, patient no., or name"
               class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium">
            Search
        </button>
        @if(!empty($search))
            <a href="{{ route('clinic-patients.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Clear</a>
        @endif
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($patients as $patient)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($patient->photo)
                                <img src="{{ Storage::url($patient->photo) }}"
                                     alt="{{ $patient->full_name }}"
                                     class="h-12 w-12 object-cover rounded-full">
                            @else
                                <div class="h-12 w-12 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-child text-gray-400"></i>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $patient->full_name }}</div>
                            @if($patient->date_of_birth)
                                <div class="text-xs text-gray-500">DOB: {{ $patient->date_of_birth->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                            {{ $patient->patient_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($patient->school_access_code)
                                <span class="text-sm font-mono font-semibold text-blue-700">{{ $patient->school_access_code }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $patient->parentGuardian?->full_name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $patient->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($patient->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('clinic-patients.show', $patient) }}" class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="{{ route('clinic-patients.edit', $patient) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <form action="{{ route('clinic-patients.destroy', $patient) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Remove this patient record?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            No patients yet.
                            <a href="{{ route('clinic-patients.create') }}" class="text-blue-600 hover:underline">Import the first child by access code</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
