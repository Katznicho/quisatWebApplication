<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['groups' => $labels['module'], 'meetings' => 'Meetings', 'report' => 'Weekly report', 'settings' => 'Settings'] as $key => $label)
            <button type="button" wire:click="$set('section', '{{ $key }}')"
                class="rounded-lg px-3 py-2 text-sm font-semibold {{ $section === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($section === 'settings')
        <div class="max-w-2xl space-y-4">
            <p class="text-sm text-slate-500">These names are used in the admin workspace and the parent app. Extra fields appear on the group form for this church only.</p>
            <div>
                <label class="mb-1 block text-sm font-medium">Module name</label>
                <input wire:model="moduleLabel" class="w-full rounded-lg border border-slate-300 px-3 py-2" @disabled(! $canManage)>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Singular name</label>
                <input wire:model="singularLabel" class="w-full rounded-lg border border-slate-300 px-3 py-2" @disabled(! $canManage)>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium">Extra fields</label>
                    @if ($canManage)
                        <button type="button" wire:click="addCustomField" class="text-sm font-semibold text-blue-600">Add field</button>
                    @endif
                </div>
                @foreach ($customFields as $index => $field)
                    <div class="flex items-center gap-2">
                        <input wire:model="customFields.{{ $index }}.label" placeholder="Field label" class="flex-1 rounded-lg border border-slate-300 px-3 py-2" @disabled(! $canManage)>
                        <label class="flex items-center gap-1 text-sm text-slate-600">
                            <input type="checkbox" wire:model="customFields.{{ $index }}.required" @disabled(! $canManage)> Required
                        </label>
                        @if ($canManage)
                            <button type="button" wire:click="removeCustomField({{ $index }})" class="text-sm text-red-600">Remove</button>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($canManage)
                <button type="button" wire:click="saveSettings" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save settings</button>
            @endif
        </div>
    @endif

    @if ($section === 'groups' || $section === 'members')
        <div class="mb-4 flex flex-wrap gap-2">
            <input wire:model.live="search" placeholder="Search by name" class="rounded-lg border border-slate-300 px-3 py-2">
            <select wire:model.live="location" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All locations</option>
                @foreach ($locations as $item)
                    <option value="{{ $item }}">{{ $item }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Location</th>
                        <th class="px-3 py-2">Members</th>
                        <th class="px-3 py-2">Capacity</th>
                        <th class="px-3 py-2">Spaces</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2 font-medium">{{ $group->name }}</td>
                            <td class="px-3 py-2">{{ $group->location }}</td>
                            <td class="px-3 py-2">{{ $group->members_count }}</td>
                            <td class="px-3 py-2">{{ $group->capacity }}</td>
                            <td class="px-3 py-2">{{ $group->availableSpaces() }}</td>
                            <td class="px-3 py-2 text-right">
                                @if ($canManage)
                                    <button type="button" wire:click="editGroup({{ $group->id }})" class="mr-2 font-semibold text-blue-600">Edit</button>
                                @endif
                                <button type="button" wire:click="viewMembers({{ $group->id }})" class="font-semibold text-slate-700">View members</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-slate-500">No groups match this search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($section === 'members' && $memberGroup)
            <div class="mt-6 rounded-xl border border-slate-200 p-4">
                <h3 class="font-semibold">{{ $memberGroup->name }} members</h3>
                <p class="mb-3 text-sm text-slate-500">{{ $memberGroup->members_count ?? $members->count() }} registered · capacity {{ $memberGroup->capacity }}</p>
                <ul class="divide-y divide-slate-100">
                    @forelse ($members as $member)
                        <li class="flex items-center justify-between py-2">
                            <span>{{ $member->student?->full_name }} <span class="text-slate-500">· {{ $member->student?->parentGuardian?->full_name }}</span></span>
                            @if ($canManage)
                                <button type="button" wire:click="removeMember({{ $member->id }})" class="text-sm text-red-600">Remove</button>
                            @endif
                        </li>
                    @empty
                        <li class="py-2 text-slate-500">No members yet.</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($canManage)
            <form wire:submit="saveGroup" class="mt-6 grid max-w-3xl grid-cols-1 gap-3 md:grid-cols-2">
                <h3 class="md:col-span-2 font-semibold">{{ $editingId ? 'Edit '.$labels['singular'] : 'New '.$labels['singular'] }}</h3>
                <input wire:model="name" placeholder="Small group name" class="rounded-lg border border-slate-300 px-3 py-2">
                <input wire:model="groupLocation" placeholder="Location" class="rounded-lg border border-slate-300 px-3 py-2">
                <input wire:model="hostName" placeholder="Host name" class="rounded-lg border border-slate-300 px-3 py-2">
                <input wire:model="hostPhone" placeholder="Host phone" class="rounded-lg border border-slate-300 px-3 py-2">
                <input wire:model="leaderName" placeholder="Leader name" class="rounded-lg border border-slate-300 px-3 py-2">
                <input wire:model="leaderPhone" placeholder="Leader phone" class="rounded-lg border border-slate-300 px-3 py-2">
                <select wire:model="leaderUserId" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Assign a staff leader (optional)</option>
                    @foreach ($staff as $person)
                        <option value="{{ $person->id }}">{{ $person->name }}</option>
                    @endforeach
                </select>
                <input wire:model="capacity" type="number" min="1" placeholder="Capacity" class="rounded-lg border border-slate-300 px-3 py-2">
                @foreach ($settings->custom_fields ?? [] as $field)
                    <input wire:model="customValues.{{ $field['key'] }}" placeholder="{{ $field['label'] }}{{ ! empty($field['required']) ? ' *' : '' }}" class="rounded-lg border border-slate-300 px-3 py-2">
                @endforeach
                @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error('capacity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="md:col-span-2 flex gap-2">
                    <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">{{ $editingId ? 'Save changes' : 'Create group' }}</button>
                    @if ($editingId)
                        <button type="button" wire:click="resetGroupForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                    @endif
                </div>
            </form>
        @endif
    @endif

    @if ($section === 'meetings')
        @if ($canManage)
            <form wire:submit="saveMeeting" class="mb-6 grid max-w-3xl grid-cols-1 gap-3 md:grid-cols-3">
                <select wire:model="meetingGroupId" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Group</option>
                    @foreach ($allGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
                <input type="date" wire:model="meetingDate" class="rounded-lg border border-slate-300 px-3 py-2">
                <input wire:model="meetingLocation" placeholder="Location" class="rounded-lg border border-slate-300 px-3 py-2">
                <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white md:col-span-3 w-fit">Record meeting</button>
            </form>
        @endif

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Group</th>
                        <th class="px-3 py-2">Location</th>
                        <th class="px-3 py-2">Attendance</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($meetings as $meeting)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2">{{ optional($meeting->meeting_date)->toDateString() }}</td>
                            <td class="px-3 py-2">{{ $meeting->group?->name }}</td>
                            <td class="px-3 py-2">{{ $meeting->location }}</td>
                            <td class="px-3 py-2">{{ $meeting->present_count }}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" wire:click="reviewMeeting({{ $meeting->id }})" class="font-semibold text-blue-600">Review</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-slate-500">No meetings recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($review)
            <div class="mt-6 rounded-xl border border-slate-200 p-4">
                <h3 class="font-semibold">{{ $review['meeting']->group?->name }} · {{ optional($review['meeting']->meeting_date)->toDateString() }}</h3>
                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($review['roster'] as $member)
                        @php $row = $review['attendance']->get($member->student_id); @endphp
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <div>
                                <p class="font-medium">{{ $member->student?->full_name }}</p>
                                <p class="text-xs text-slate-500">
                                    @if (! $row || $row->status !== 'present')
                                        No attendance recorded
                                    @else
                                        {{ $row->verification_status === 'verified' ? 'Verified' : 'Pending verification' }}
                                        @if ($row->last_changed_at)
                                            · {{ $row->lastChangedByUser?->name ?: $row->lastChangedByParent?->full_name }} · {{ $row->last_changed_at->timezone(auth()->user()->business?->timezoneName() ?? config('app.timezone'))->format('j M Y g:i A') }}
                                        @endif
                                    @endif
                                </p>
                            </div>
                            @if ($this->canVerifyGroup($review['meeting']->group))
                                <div class="flex gap-2">
                                    @if ($row && $row->status === 'present' && $row->verification_status !== 'verified')
                                        <button type="button" wire:click="verifyAttendance({{ $row->id }})" class="text-sm font-semibold text-emerald-700">Verify</button>
                                    @endif
                                    @if ($row)
                                        <button type="button" wire:click="correctAttendance({{ $row->id }}, 'present')" class="text-sm font-semibold text-blue-600">Mark attended</button>
                                        <button type="button" wire:click="correctAttendance({{ $row->id }}, 'removed')" class="text-sm font-semibold text-red-600">Remove</button>
                                    @else
                                        <button type="button" wire:click="markAttended({{ $review['meeting']->id }}, {{ $member->student_id }})" class="text-sm font-semibold text-blue-600">Mark attended</button>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    @if ($section === 'report' && $report)
        <div class="mb-4 flex flex-wrap gap-2">
            <input type="date" wire:model.live="reportWeek" class="rounded-lg border border-slate-300 px-3 py-2">
            <select wire:model.live="reportGroupId" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All groups</option>
                @foreach ($allGroups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
            <input type="date" wire:model.live="reportDate" class="rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <p class="mb-4 text-sm text-slate-500">Week of {{ $report['week_label'] }}. A group with no row for a meeting has not met. A meeting with no names has no attendance recorded yet.</p>
        <div class="space-y-3">
            @foreach ($report['groups'] as $row)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="font-semibold">{{ $row['group']['name'] }}</h3>
                        <p class="text-sm text-slate-500">{{ $row['group']['location'] }} · {{ $row['group']['member_count'] }} members</p>
                    </div>
                    @if ($row['state'] === 'no_meeting')
                        <p class="mt-2 text-sm text-amber-700">No meeting recorded</p>
                    @else
                        @foreach ($row['meetings'] as $meeting)
                            <div class="mt-3 border-t border-slate-100 pt-3">
                                <p class="text-sm font-medium">{{ $meeting['meeting_date'] }} · {{ $meeting['location'] }} · {{ $meeting['attendance_count'] }} attended</p>
                                @if ($meeting['state'] === 'no_attendance')
                                    <p class="text-sm text-amber-700">Meeting recorded, no attendance recorded</p>
                                @else
                                    <p class="text-sm text-slate-600">{{ collect($meeting['attendees'])->pluck('name')->filter()->join(', ') }}</p>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
