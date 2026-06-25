<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <a href="{{ route('push-notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Push notifications</a>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white mt-2">Registered devices</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Mobile and web devices registered for push delivery.
                            @if ($isSuperAdmin)
                                Showing all schools.
                            @else
                                Showing devices for your school only.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $deviceStats['active'] }}</div>
                        <div class="text-xs text-gray-500">Active</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $deviceStats['ios'] }}</div>
                        <div class="text-xs text-gray-500">iOS</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $deviceStats['android'] }}</div>
                        <div class="text-xs text-gray-500">Android</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $deviceStats['web'] }}</div>
                        <div class="text-xs text-gray-500">Web</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                        <div class="text-2xl font-bold text-gray-500">{{ $deviceStats['total'] - $deviceStats['active'] }}</div>
                        <div class="text-xs text-gray-500">Inactive</div>
                    </div>
                </div>

                @if ($isSuperAdmin)
                    <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Push configuration</h3>
                        <ul class="space-y-2 text-sm">
                            @foreach ($configChecks as $check)
                                <li class="flex items-start gap-2">
                                    <span @class([
                                        'mt-0.5 inline-block h-2.5 w-2.5 rounded-full shrink-0',
                                        'bg-green-500' => $check['ok'],
                                        'bg-amber-500' => ! $check['ok'] && $check['key'] !== 'expo_token',
                                        'bg-gray-400' => ! $check['ok'] && $check['key'] === 'expo_token',
                                    ])></span>
                                    <div>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $check['label'] }}</span>
                                        <span class="text-gray-600 dark:text-gray-400"> — {{ $check['detail'] }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 mb-4">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search name, email, device…"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm flex-1 min-w-[200px]">
                    <select name="platform" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                        <option value="">All platforms</option>
                        <option value="ios" @selected($filters['platform'] === 'ios')>iOS</option>
                        <option value="android" @selected($filters['platform'] === 'android')>Android</option>
                        <option value="web" @selected($filters['platform'] === 'web')>Web</option>
                    </select>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="active_only" value="1" @checked($filters['active_only'])
                            class="rounded border-gray-300">
                        Active only
                    </label>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">User</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Platform</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Device</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Token</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Last seen</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($devices as $device)
                                @php
                                    $owner = $device->tokenable;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ \App\Http\Controllers\DeviceTokenAdminController::ownerLabel($owner) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ \App\Http\Controllers\DeviceTokenAdminController::ownerEmail($owner) ?? '—' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ \App\Http\Controllers\DeviceTokenAdminController::ownerType($owner) }}</td>
                                    <td class="px-4 py-3 capitalize">{{ $device->platform }}</td>
                                    <td class="px-4 py-3">
                                        <div>{{ $device->device_name ?? '—' }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ Str::limit($device->device_id, 24) }}</div>
                                        @if ($device->app_version)
                                            <div class="text-xs text-gray-400">v{{ $device->app_version }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600 max-w-[180px] truncate"
                                        title="{{ $device->push_token }}">
                                        {{ \App\Http\Controllers\DeviceTokenAdminController::maskToken($device->push_token) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $device->last_used_at?->diffForHumans() ?? $device->created_at?->diffForHumans() ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-green-100 text-green-800' => $device->is_active,
                                            'bg-gray-100 text-gray-600' => ! $device->is_active,
                                        ])>
                                            {{ $device->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                        No devices registered yet. Users must open the mobile app (or enable browser notifications) while logged in.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">{{ $devices->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
