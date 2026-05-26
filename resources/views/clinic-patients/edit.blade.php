@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Patient — {{ $patient->full_name }}</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Patient no. {{ $patient->patient_number }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('clinic-patients.update', $patient) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @if($patient->student_id)
                <input type="hidden" name="clinic_family_id" value="{{ $patient->clinic_family_id }}">
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">Imported from school — identity fields come from the school record.</p>
                    @if($patient->school_access_code)
                        <p class="text-sm font-mono font-semibold text-blue-900 mt-1">{{ $patient->school_access_code }}</p>
                    @endif
                </div>
            @else
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Family</h2>
                <div class="mb-8">
                    <label for="clinic_family_id" class="block text-sm font-medium text-gray-700 mb-2">Family <span class="text-red-500">*</span></label>
                    <select name="clinic_family_id" id="clinic_family_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach($families as $family)
                            <option value="{{ $family->id }}" @selected(old('clinic_family_id', $patient->clinic_family_id) == $family->id)>
                                {{ $family->family_name ?: 'Family' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <h2 class="text-lg font-semibold text-gray-900 mb-4">Clinic details</h2>
            <p class="text-sm text-gray-500 mb-4">Update allergies, emergency contacts, insurance, and other clinic-only information.</p>
            @include('clinic-patients._form-fields', ['useRepeaters' => true, 'importedFromSchool' => (bool) $patient->student_id])

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('clinic-patients.show', $patient) }}"
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

@include('clinic-patients._repeater-script')
@endsection
