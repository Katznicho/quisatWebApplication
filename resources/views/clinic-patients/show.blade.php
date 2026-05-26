@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $patient->full_name }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Clinic patient · {{ $patient->patient_number }}</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('clinic-patients.edit', $patient) }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('clinic-patients.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($patient->school_access_code)
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800 font-medium">School child access code</p>
            <p class="text-2xl font-mono font-bold text-blue-900 mt-1">{{ $patient->school_access_code }}</p>
            <p class="text-xs text-blue-700 mt-2">Parents use this code in the app to link this child to your clinic.</p>
        </div>
    @endif

    @if($patient->student && $patient->student->business)
        <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-700">Imported from school</p>
            <p class="text-gray-900 font-semibold">{{ $patient->student->business->name }}</p>
            @if($patient->student->student_id)
                <p class="text-sm text-gray-500">School ID: {{ $patient->student->student_id }}</p>
            @endif
        </div>
    @endif

    @if($patient->school_access_code)
        <div class="mb-4 p-3 bg-pink-50 border border-pink-200 rounded-lg">
            <p class="text-xs font-medium text-pink-800 uppercase tracking-wide">School access code</p>
            <p class="text-lg font-mono font-bold text-pink-900">{{ $patient->school_access_code }}</p>
            <p class="text-xs text-pink-700 mt-1">Parents use this code in the app to link; staff can re-import with the same code.</p>
        </div>
    @endif

    <div class="mb-6 bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Appointments</h2>
        </div>
        <div class="p-6">
            @if($patient->appointments->isEmpty())
                <p class="text-sm text-gray-500 mb-4">No appointments yet. Schedule one below — parents will see it in the app.</p>
            @else
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($patient->appointments as $appointment)
                                <tr>
                                    <td class="px-4 py-2">{{ $appointment->scheduled_at->format('d M Y, H:i') }}</td>
                                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $appointment->appointment_type) }}</td>
                                    <td class="px-4 py-2">{{ $appointment->doctor_name ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex px-2 text-xs font-semibold rounded-full
                                            @if($appointment->status === 'scheduled') bg-blue-100 text-blue-800
                                            @elseif($appointment->status === 'completed') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($appointment->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <form action="{{ route('clinic-patients.appointments.store', $patient) }}" method="POST" class="border-t pt-4 space-y-4">
                @csrf
                <p class="text-sm font-medium text-gray-700">Schedule appointment</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date & time</label>
                        <input type="datetime-local" name="scheduled_at" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Doctor name</label>
                        <input type="text" name="doctor_name" placeholder="Dr. Smith"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Type</label>
                        <select name="appointment_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                            <option value="consultation">Consultation</option>
                            <option value="follow_up">Follow-up</option>
                            <option value="vaccination">Vaccination</option>
                            <option value="checkup">Check-up</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Notes (optional)</label>
                    <textarea name="notes" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"></textarea>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white text-sm font-medium rounded-md hover:bg-pink-700">
                    Save appointment
                </button>
            </form>
        </div>
    </div>

    <div class="mb-6 p-4 border border-dashed border-gray-300 rounded-lg bg-gray-50">
        <p class="text-sm font-medium text-gray-700">More clinical modules</p>
        <p class="text-sm text-gray-500 mt-1">Vaccination schedules, growth charts, and visit notes — coming soon.</p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <div>
                @if($patient->photo)
                    <img src="{{ Storage::url($patient->photo) }}"
                         alt="{{ $patient->full_name }}"
                         class="w-full max-h-64 object-cover rounded-lg mb-4">
                @else
                    <div class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-child text-4xl text-gray-300"></i>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status</h3>
                    <span class="mt-1 inline-flex px-2 text-xs font-semibold rounded-full
                        {{ $patient->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($patient->status) }}
                    </span>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Date of birth</h3>
                    <p class="mt-1 text-gray-900">{{ $patient->date_of_birth?->format('d M Y') ?? '—' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Gender</h3>
                    <p class="mt-1 text-gray-900">{{ $patient->gender ? ucfirst($patient->gender) : '—' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Blood group</h3>
                    <p class="mt-1 text-gray-900">{{ $patient->blood_group ?? '—' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Primary parent</h3>
                    <p class="mt-1 text-gray-900">{{ $patient->parentGuardian?->full_name ?? '—' }}</p>
                    @if($patient->parentGuardian?->phone)
                        <p class="text-sm text-gray-500">{{ $patient->parentGuardian->phone }}</p>
                    @endif
                </div>

                @if($patient->student)
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Linked school student</h3>
                        <p class="mt-1 text-gray-900">{{ $patient->student->first_name }} {{ $patient->student->last_name }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="border-t px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Allergies</h3>
                @if(!empty($patient->allergies))
                    <ul class="list-disc list-inside text-gray-900">
                        @foreach($patient->allergies as $allergy)
                            <li>{{ $allergy }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">None recorded</p>
                @endif
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Emergency contacts</h3>
                @if(!empty($patient->emergency_contacts))
                    @foreach($patient->emergency_contacts as $contact)
                        <p class="text-gray-900">
                            {{ $contact['name'] ?? 'Contact' }}
                            @if(!empty($contact['relationship'])) ({{ $contact['relationship'] }}) @endif
                            @if(!empty($contact['phone'])) — {{ $contact['phone'] }} @endif
                        </p>
                    @endforeach
                @else
                    <p class="text-gray-500">None recorded</p>
                @endif
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Insurance</h3>
                @if(!empty($patient->insurance_info))
                    <p class="text-gray-900">{{ $patient->insurance_info['provider'] ?? '—' }}</p>
                    @if(!empty($patient->insurance_info['policy_number']))
                        <p class="text-sm text-gray-500">Policy: {{ $patient->insurance_info['policy_number'] }}</p>
                    @endif
                @else
                    <p class="text-gray-500">None recorded</p>
                @endif
            </div>

            @if($patient->family && $patient->family->members->count())
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Family members (linked parents)</h3>
                    <ul class="space-y-1">
                        @foreach($patient->family->members as $member)
                            <li class="text-gray-900">
                                {{ $member->parentGuardian?->full_name ?? 'Parent' }}
                                <span class="text-xs text-gray-500">({{ $member->relationship }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
