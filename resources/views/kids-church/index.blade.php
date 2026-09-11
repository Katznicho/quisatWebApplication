@extends('layouts.app')

@section('content')
@php
    $activeTab = request('tab', 'children');
    $tabs = ['overview', 'children', 'groups', 'check-in', 'lessons', 'incidents', 'volunteers', 'moments', 'prayer', 'memory-wall', 'events', 'parents', 'fees'];
    if (! in_array($activeTab, $tabs, true)) {
        $activeTab = 'children';
    }
@endphp
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kids Church Dashboard</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Children’s ministry workspace for groups, Sunday check-in, pickup codes, Moments, prayer, and family communication.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @include('partials.link-parent-quisat-code-modal', [
                'contextLabel' => 'church',
                'formAction' => route('school-management.parents.link-by-quisat-code'),
                'redirectTo' => route('kids-church.index', ['tab' => 'parents']),
            ])
            <a href="{{ route('school-management.students.create') }}"
               class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                <i class="fas fa-user-plus mr-2"></i>Add child
            </a>
            <a href="{{ route('kids-church.index', ['tab' => 'moments']) }}"
               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i class="fas fa-images mr-2"></i>Open Moments
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-900 via-indigo-900 to-blue-900 px-6 py-8 text-white">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                        Kids ministry
                    </span>
                    <h2 class="mt-4 text-2xl font-bold">Run Sunday from one module</h2>
                    <p class="mt-2 max-w-2xl text-sm text-slate-200">
                        Set up groups, register children, check them in with a pickup code, then share class photos and prayer with families in the Quisat app.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Children</p>
                        <p class="mt-1 text-2xl font-bold">{{ $stats['children'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Groups</p>
                        <p class="mt-1 text-2xl font-bold">{{ $stats['groups'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Checked in today</p>
                        <p class="mt-1 text-2xl font-bold">{{ $stats['checked_in_today'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Open pickup codes</p>
                        <p class="mt-1 text-2xl font-bold">{{ $stats['pickup_codes'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 bg-slate-50 px-6 py-5 md:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Birthdays today</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['birthdays_today'] ?? 0 }} children</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Medical alerts</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['medical_alerts'] ?? 0 }} on file</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Lessons published</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['lessons'] ?? 0 }} for teachers & families</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Incidents today</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['incidents'] ?? 0 }} logged</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Volunteers</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['volunteers'] ?? 0 }} on the roster</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Moments</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['albums'] ?? 0 }} albums</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Families</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['parents'] ?? 0 }} parents linked</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Upcoming events</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $stats['events'] ?? 0 }} on the calendar</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Giving</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">
                    <a href="{{ route('kids-church.index', ['tab' => 'fees']) }}" class="text-blue-600 hover:underline">Fees / Giving</a>
                    — collect from parents in the app
                </p>
            </div>
        </div>
    </div>

    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex flex-wrap gap-2">
            @foreach ([
                'overview' => 'Overview',
                'children' => 'Children',
                'groups' => 'Groups',
                'check-in' => 'Check-in',
                'lessons' => 'Lessons',
                'incidents' => 'Incidents',
                'volunteers' => 'Volunteers',
                'moments' => 'Moments',
                'prayer' => 'Prayer',
                'memory-wall' => 'Memory Wall',
                'events' => 'Events',
                'parents' => 'Parents',
                'fees' => 'Fees / Giving',
            ] as $tab => $label)
                <a href="{{ route('kids-church.index', ['tab' => $tab]) }}"
                   class="rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold {{ $activeTab === $tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    @if($activeTab === 'overview')
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                <h3 class="text-lg font-semibold text-slate-900">Sunday workflow</h3>
                <p class="mt-2 text-sm text-slate-500">
                    Configure groups and children once, then use check-in, Moments, and prayer every week from this dashboard.
                </p>
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">1. Create groups</p>
                        <p class="mt-1 text-sm text-slate-600">Age groups or classes (Nursery, Kids, Teens) so children land in the right room.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">2. Register children</p>
                        <p class="mt-1 text-sm text-slate-600">Add a child or link a parent with their Quisat code so allergies and family details come with them.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">3. Check in with a pickup code</p>
                        <p class="mt-1 text-sm text-slate-600">Parents or staff check a child in. A 4-digit code is issued and must be shown at pickup.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">4. Share Moments</p>
                        <p class="mt-1 text-sm text-slate-600">Upload class photos once. Only parents of that group see, like, and comment.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">5. Pray and teach</p>
                        <p class="mt-1 text-sm text-slate-600">Publish this week’s lesson, Memory Wall verse, and work through prayer requests from families.</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 md:col-span-2">
                        <p class="text-sm font-semibold text-emerald-900">6. Collect giving</p>
                        <p class="mt-1 text-sm text-emerald-800">Use <strong>Fees / Giving</strong> for child fees or offerings. Parents pay from Fees in the Quisat app.</p>
                        <a href="{{ route('kids-church.index', ['tab' => 'fees']) }}"
                           class="mt-3 inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                            Open giving
                        </a>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Live this week</h3>
                <div class="mt-4 space-y-4 text-sm">
                    <div>
                        <p class="font-semibold text-slate-900">Birthdays today</p>
                        @forelse ($birthdayChildren as $child)
                            <p class="mt-1 text-slate-600">{{ $child->full_name }}</p>
                        @empty
                            <p class="mt-1 text-slate-500">No birthdays today.</p>
                        @endforelse
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Medical alerts</p>
                        @forelse ($medicalAlertChildren as $child)
                            <p class="mt-1 text-slate-600">{{ $child->full_name }}@if($child->allergies) — {{ \Illuminate\Support\Str::limit($child->allergies, 40) }}@endif</p>
                        @empty
                            <p class="mt-1 text-slate-500">No allergy notes on file.</p>
                        @endforelse
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Active lesson</p>
                        @if($activeLesson)
                            <a href="{{ route('kids-church.index', ['tab' => 'lessons']) }}" class="mt-1 block text-blue-600 hover:underline">{{ $activeLesson->title }}</a>
                        @else
                            <p class="mt-1 text-slate-500">Publish this Sunday’s lesson from the Lessons tab.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'children')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Children</h2>
                    <p class="text-sm text-slate-500">Register children, assign groups, and keep allergy notes on file.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('school-management.students.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add child</a>
                    <a href="{{ route('school-management.students.bulk-upload-page') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Bulk upload</a>
                </div>
            </div>
            @livewire('school-management.student-management')
        </div>
    @elseif($activeTab === 'groups')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Groups</h2>
            <p class="mb-4 text-sm text-slate-500">Age groups or classes used for check-in, lessons, and Moments albums.</p>
            @livewire('school-management.class-room-management')
        </div>
    @elseif($activeTab === 'check-in')
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @if(\Illuminate\Support\Facades\Schema::hasTable('pickup_codes'))
                    @livewire('school-management.pickup-codes-management')
                @else
                    <p class="text-sm text-slate-600">Run <code class="rounded bg-slate-100 px-1.5 py-0.5">php artisan migrate</code> to enable pickup codes.</p>
                @endif
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-1 text-xl font-semibold text-slate-900">Attendance</h2>
                <p class="mb-4 text-sm text-slate-500">Daily check-in records. Checkout stays present and requires the pickup code from the app.</p>
                @livewire('school-management.attendance-management')
            </div>
        </div>
    @elseif($activeTab === 'lessons')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Lessons & home faith</h2>
            <p class="mb-4 text-sm text-slate-500">Weekly Bible lessons, craft lists, teaching scripts, family challenges, and the pastor’s devotion. Publishing notifies teachers and parents in the app.</p>
            @if(\Illuminate\Support\Facades\Schema::hasTable('kids_lessons'))
                @livewire('school-management.kids-lesson-management')
            @else
                <p class="text-sm text-slate-600">Run <code class="rounded bg-slate-100 px-1.5 py-0.5">php artisan migrate</code> to enable lessons.</p>
            @endif
        </div>
    @elseif($activeTab === 'incidents')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Incident & health reports</h2>
            <p class="mb-4 text-sm text-slate-500">Log a physical, behavioral, or health incident. The parent is notified immediately in the app.</p>
            @if(\Illuminate\Support\Facades\Schema::hasTable('kids_incidents'))
                @livewire('school-management.kids-incident-management')
            @else
                <p class="text-sm text-slate-600">Run <code class="rounded bg-slate-100 px-1.5 py-0.5">php artisan migrate</code> to enable incident reports.</p>
            @endif
        </div>
    @elseif($activeTab === 'volunteers')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Volunteer roster</h2>
            <p class="mb-4 text-sm text-slate-500">Track Sunday team roles, background checks, and hours. Send schedule reminders from Chat.</p>
            @if(\Illuminate\Support\Facades\Schema::hasTable('kids_volunteers'))
                @livewire('school-management.kids-volunteer-management')
            @else
                <p class="text-sm text-slate-600">Run <code class="rounded bg-slate-100 px-1.5 py-0.5">php artisan migrate</code> to enable the volunteer roster.</p>
            @endif
        </div>
    @elseif($activeTab === 'moments')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Quisat Moments</h2>
            <p class="mb-4 text-sm text-slate-500">Private group and event albums. Only parents of that group see the photos.</p>
            @livewire('school-management.quisat-moments-management')
        </div>
    @elseif($activeTab === 'prayer')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Prayer requests</h2>
            <p class="mb-4 text-sm text-slate-500">Mark requests as being prayed for or answered. Families see status in the app.</p>
            @livewire('school-management.prayer-requests-management')
        </div>
    @elseif($activeTab === 'memory-wall')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Memory Wall</h2>
            <p class="mb-4 text-sm text-slate-500">This week’s verse and prayer focus, shown on the parent home screen.</p>
            @livewire('school-management.memory-wall-management')
        </div>
    @elseif($activeTab === 'events')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Church events</h2>
            <p class="mb-4 text-sm text-slate-500">Services, family days, and kids events published to the app calendar.</p>
            @livewire('school-management.calendar-events-management')
        </div>
    @elseif($activeTab === 'parents')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Parents</h2>
                    <p class="text-sm text-slate-500">Link families with a Quisat code so they can check in and see Moments.</p>
                </div>
                <a href="{{ route('school-management.parents.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add parent</a>
            </div>
            @livewire('school-management.parent-guardian-management')
        </div>
    @elseif($activeTab === 'fees')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-xl font-semibold text-slate-900">Fees / Giving</h2>
            <p class="mb-4 text-sm text-slate-500">Child fees and giving. Parents pay from Fees in the Quisat app.</p>
            @livewire('school-management.fee-management')
        </div>
    @endif
</div>
@endsection
