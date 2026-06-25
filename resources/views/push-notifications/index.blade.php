<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Push Notifications</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Compose and send notifications to mobile app and web users.
                        </p>
                    </div>
                    <a href="{{ route('push-notifications.create') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-2"></i>Send notification
                    </a>
                    <button type="button" id="enable-web-push"
                        class="inline-flex items-center justify-center rounded-lg border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">
                        Enable browser notifications
                    </button>
                </div>

                @if (session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $deviceStats['active'] }}</div>
                        <div class="text-xs text-gray-500">Active devices</div>
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
                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 p-3 text-center bg-blue-50/50 dark:bg-blue-900/20">
                        <a href="{{ route('push-notifications.devices') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                            View all devices →
                        </a>
                    </div>
                </div>

                @if ($isSuperAdmin && count($configChecks))
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

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Title</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Audience</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Channels</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Sent</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($broadcasts as $broadcast)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $broadcast->title }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ $broadcast->body }}</div>
                                        @if ($broadcast->image_path)
                                            <span class="mt-1 inline-flex text-xs text-blue-600">Has image</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $broadcast->audience) }}</td>
                                    <td class="px-4 py-3">{{ implode(', ', $broadcast->channels ?? []) }}</td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-green-100 text-green-800' => $broadcast->status === 'sent',
                                            'bg-yellow-100 text-yellow-800' => in_array($broadcast->status, ['queued', 'sending']),
                                            'bg-red-100 text-red-800' => $broadcast->status === 'failed',
                                            'bg-gray-100 text-gray-800' => $broadcast->status === 'draft',
                                        ])>
                                            {{ ucfirst($broadcast->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $broadcast->sent_at?->format('M d, Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('push-notifications.show', $broadcast) }}"
                                            class="text-blue-600 hover:text-blue-800">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        No notifications sent yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">{{ $broadcasts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
