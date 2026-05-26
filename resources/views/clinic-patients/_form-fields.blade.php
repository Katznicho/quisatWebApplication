@php
    $patient = $patient ?? null;
    $useRepeaters = $useRepeaters ?? false;
    $importedFromSchool = $importedFromSchool ?? false;
    $manualGuardianEntry = $manualGuardianEntry ?? false;
    $insuranceProvider = old('insurance_provider', data_get($patient ?? null, 'insurance_info.provider', ''));
    $insurancePolicy = old('insurance_policy_number', data_get($patient ?? null, 'insurance_info.policy_number', ''));

    if ($useRepeaters) {
        $allergyRows = old('allergies');
        if ($allergyRows === null) {
            $allergyRows = isset($patient) && !empty($patient->allergies) ? $patient->allergies : [''];
        }
        if (empty($allergyRows)) {
            $allergyRows = [''];
        }

        $emergencyRows = old('emergency_contacts');
        if ($emergencyRows === null) {
            $emergencyRows = isset($patient) && !empty($patient->emergency_contacts)
                ? $patient->emergency_contacts
                : [['name' => '', 'phone' => '', 'relationship' => '']];
        }
        if (empty($emergencyRows)) {
            $emergencyRows = [['name' => '', 'phone' => '', 'relationship' => '']];
        }
    } else {
        $emergencyName = old('emergency_contact_name', data_get($patient ?? null, 'emergency_contacts.0.name', ''));
        $emergencyPhone = old('emergency_contact_phone', data_get($patient ?? null, 'emergency_contacts.0.phone', ''));
        $emergencyRelationship = old('emergency_contact_relationship', data_get($patient ?? null, 'emergency_contacts.0.relationship', ''));
        $allergiesValue = old('allergies', isset($patient) && $patient->allergies ? implode(', ', $patient->allergies) : '');
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @if($importedFromSchool && isset($patient))
        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div>
                <span class="text-xs text-gray-500 uppercase">Name</span>
                <p class="font-medium text-gray-900">{{ $patient->full_name }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase">Date of birth</span>
                <p class="font-medium text-gray-900">{{ $patient->date_of_birth?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase">Gender</span>
                <p class="font-medium text-gray-900">{{ $patient->gender ? ucfirst($patient->gender) : '—' }}</p>
            </div>
        </div>
        <input type="hidden" name="first_name" value="{{ $patient->first_name }}">
        <input type="hidden" name="last_name" value="{{ $patient->last_name }}">
        <input type="hidden" name="date_of_birth" value="{{ $patient->date_of_birth?->format('Y-m-d') }}">
        <input type="hidden" name="gender" value="{{ $patient->gender }}">
    @else
        <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First name <span class="text-red-500">*</span></label>
            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', optional($patient)->first_name) }}" required
                   placeholder="e.g. Amina"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last name <span class="text-red-500">*</span></label>
            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', optional($patient)->last_name) }}" required
                   placeholder="e.g. Okello"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">Date of birth</label>
            <input type="date" name="date_of_birth" id="date_of_birth"
                   value="{{ old('date_of_birth', isset($patient) && $patient->date_of_birth ? $patient->date_of_birth->format('Y-m-d') : '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
            <select name="gender" id="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select gender</option>
                @foreach(['male', 'female', 'other'] as $g)
                    <option value="{{ $g }}" @selected(old('gender', optional($patient)->gender) === $g)>{{ ucfirst($g) }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label for="blood_group" class="block text-sm font-medium text-gray-700 mb-2">Blood group</label>
        <select name="blood_group" id="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select blood group</option>
            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                <option value="{{ $bg }}" @selected(old('blood_group', optional($patient)->blood_group) === $bg)>{{ $bg }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="active" @selected(old('status', optional($patient)->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', optional($patient)->status) === 'inactive')>Inactive</option>
        </select>
    </div>

    @if($useRepeaters)
        <div class="md:col-span-2">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">Allergies</label>
                <button type="button" onclick="addRepeaterRow('allergies-repeater', 'allergy-row-template')"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Add allergy</button>
            </div>
            <div id="allergies-repeater" class="space-y-2">
                @foreach($allergyRows as $index => $allergy)
                    <div class="flex gap-2 items-start" data-repeater-row>
                        <input type="text"
                               data-repeater-name="allergies[__INDEX__]"
                               name="allergies[{{ $index }}]"
                               value="{{ is_string($allergy) ? $allergy : '' }}"
                               placeholder="e.g. Peanuts, Penicillin, Dust"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" onclick="removeRepeaterRow(this, 'allergies-repeater')"
                                class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-md text-sm" title="Remove">Remove</button>
                    </div>
                @endforeach
            </div>
            @error('allergies')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('allergies.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">Emergency contacts</label>
                <button type="button" onclick="addRepeaterRow('emergency-contacts-repeater', 'emergency-row-template')"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Add contact</button>
            </div>
            <div id="emergency-contacts-repeater" class="space-y-4">
                @foreach($emergencyRows as $index => $contact)
                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50" data-repeater-row>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Full name</label>
                                <input type="text"
                                       data-repeater-name="emergency_contacts[__INDEX__][name]"
                                       name="emergency_contacts[{{ $index }}][name]"
                                       value="{{ $contact['name'] ?? '' }}"
                                       placeholder="e.g. Grace Nakato"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Phone</label>
                                <input type="text"
                                       data-repeater-name="emergency_contacts[__INDEX__][phone]"
                                       name="emergency_contacts[{{ $index }}][phone]"
                                       value="{{ $contact['phone'] ?? '' }}"
                                       placeholder="+256 700 000 000"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Relationship</label>
                                <input type="text"
                                       data-repeater-name="emergency_contacts[__INDEX__][relationship]"
                                       name="emergency_contacts[{{ $index }}][relationship]"
                                       value="{{ $contact['relationship'] ?? '' }}"
                                       placeholder="e.g. Mother, Father, Uncle"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            </div>
                        </div>
                        <div class="mt-2 text-right">
                            <button type="button" onclick="removeRepeaterRow(this, 'emergency-contacts-repeater')"
                                    class="text-sm text-red-600 hover:text-red-800">Remove contact</button>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('emergency_contacts')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    @else
        <div class="md:col-span-2">
            <label for="allergies" class="block text-sm font-medium text-gray-700 mb-2">Allergies</label>
            <input type="text" name="allergies" id="allergies"
                   value="{{ $allergiesValue }}"
                   placeholder="Comma-separated, e.g. Peanuts, Penicillin"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-2">Emergency contact name</label>
            <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                   value="{{ $emergencyName }}"
                   placeholder="e.g. Grace Nakato"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-2">Emergency contact phone</label>
            <input type="text" name="emergency_contact_phone" id="emergency_contact_phone"
                   value="{{ $emergencyPhone }}"
                   placeholder="+256 700 000 000"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="emergency_contact_relationship" class="block text-sm font-medium text-gray-700 mb-2">Emergency contact relationship</label>
            <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship"
                   value="{{ $emergencyRelationship }}"
                   placeholder="e.g. Mother"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    @endif

    <div>
        <label for="insurance_provider" class="block text-sm font-medium text-gray-700 mb-2">Insurance provider</label>
        <input type="text" name="insurance_provider" id="insurance_provider"
               value="{{ $insuranceProvider }}"
               placeholder="e.g. Jubilee Insurance, UAP"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="insurance_policy_number" class="block text-sm font-medium text-gray-700 mb-2">Insurance policy number</label>
        <input type="text" name="insurance_policy_number" id="insurance_policy_number"
               value="{{ $insurancePolicy }}"
               placeholder="e.g. POL-UG-2024-00123"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
    </div>

    @if(!$importedFromSchool)
        @if($manualGuardianEntry)
            <div class="md:col-span-2">
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Parent / guardian details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="guardian_first_name" class="block text-sm font-medium text-gray-700 mb-2">First name <span class="text-red-500">*</span></label>
                            <input type="text" name="guardian_first_name" id="guardian_first_name"
                                   value="{{ old('guardian_first_name') }}"
                                   placeholder="e.g. Grace"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('guardian_first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="guardian_last_name" class="block text-sm font-medium text-gray-700 mb-2">Last name <span class="text-red-500">*</span></label>
                            <input type="text" name="guardian_last_name" id="guardian_last_name"
                                   value="{{ old('guardian_last_name') }}"
                                   placeholder="e.g. Nakato"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('guardian_last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="guardian_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone <span class="text-red-500">*</span></label>
                            <input type="text" name="guardian_phone" id="guardian_phone"
                                   value="{{ old('guardian_phone') }}"
                                   placeholder="+256 700 000 000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('guardian_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="guardian_email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="guardian_email" id="guardian_email"
                                   value="{{ old('guardian_email') }}"
                                   placeholder="guardian@example.com"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Optional. Leave blank if the guardian does not have email.</p>
                            @error('guardian_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="guardian_relationship" class="block text-sm font-medium text-gray-700 mb-2">Relationship <span class="text-red-500">*</span></label>
                            <select name="guardian_relationship" id="guardian_relationship"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select relationship</option>
                                @foreach(['father', 'mother', 'guardian', 'other'] as $relationship)
                                    <option value="{{ $relationship }}" @selected(old('guardian_relationship') === $relationship)>{{ ucfirst($relationship) }}</option>
                                @endforeach
                            </select>
                            @error('guardian_relationship')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div>
                <label for="parent_guardian_id" class="block text-sm font-medium text-gray-700 mb-2">Primary parent / guardian</label>
                <select name="parent_guardian_id" id="parent_guardian_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">No parent linked yet</option>
                    @foreach($parents ?? [] as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_guardian_id', optional($patient)->parent_guardian_id) == $parent->id)>
                            {{ $parent->full_name }} @if($parent->email) ({{ $parent->email }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="student_id" class="block text-sm font-medium text-gray-700 mb-2">Link to school student (optional)</label>
                <select name="student_id" id="student_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Not linked to a school record</option>
                    @foreach($students ?? [] as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id', optional($patient)->student_id) == $student->id)>
                            {{ $student->first_name }} {{ $student->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    @else
        <input type="hidden" name="parent_guardian_id" value="{{ optional($patient)->parent_guardian_id }}">
        <input type="hidden" name="student_id" value="{{ optional($patient)->student_id }}">
    @endif

    <div>
        <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">Patient photo</label>
        <input type="file" name="photo" id="photo" accept="image/*"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-500">
        <p class="mt-1 text-xs text-gray-500">JPG or PNG, max 2MB</p>
    </div>
</div>

@if($useRepeaters)
    <template id="allergy-row-template">
        <div class="flex gap-2 items-start" data-repeater-row>
            <input type="text"
                   data-repeater-name="allergies[__INDEX__]"
                   placeholder="e.g. Peanuts, Penicillin, Dust"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <button type="button" onclick="removeRepeaterRow(this, 'allergies-repeater')"
                    class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-md text-sm">Remove</button>
        </div>
    </template>

    <template id="emergency-row-template">
        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50" data-repeater-row>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Full name</label>
                    <input type="text" data-repeater-name="emergency_contacts[__INDEX__][name]"
                           placeholder="e.g. Grace Nakato"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Phone</label>
                    <input type="text" data-repeater-name="emergency_contacts[__INDEX__][phone]"
                           placeholder="+256 700 000 000"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Relationship</label>
                    <input type="text" data-repeater-name="emergency_contacts[__INDEX__][relationship]"
                           placeholder="e.g. Mother, Father, Uncle"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
            </div>
            <div class="mt-2 text-right">
                <button type="button" onclick="removeRepeaterRow(this, 'emergency-contacts-repeater')"
                        class="text-sm text-red-600 hover:text-red-800">Remove contact</button>
            </div>
        </div>
    </template>
@endif
