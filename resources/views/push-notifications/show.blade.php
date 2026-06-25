<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <a href="{{ route('push-notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to notifications</a>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mt-2">{{ $broadcast->title }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Sent {{ $broadcast->sent_at?->format('M d, Y H:i') ?? 'pending' }}</p>
                </div>

                @if (session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
                    <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $broadcast->body }}</p>
                    @if ($broadcast->imageUrl())
                        <img src="{{ $broadcast->imageUrl() }}" alt="Notification image"
                            class="mt-4 max-h-64 rounded-lg border border-gray-200 dark:border-gray-700 object-contain">
                    @endif
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                        <div class="text-xs text-gray-500 uppercase">Status</div>
                        <div class="text-lg font-semibold capitalize">{{ $broadcast->status }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                        <div class="text-xs text-gray-500 uppercase">Recipients</div>
                        <div class="text-lg font-semibold">{{ number_format($broadcast->total_recipients) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                        <div class="text-xs text-gray-500 uppercase">Push sent</div>
                        <div class="text-lg font-semibold text-green-700">{{ number_format($broadcast->push_sent_count) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                        <div class="text-xs text-gray-500 uppercase">In-app</div>
                        <div class="text-lg font-semibold">{{ number_format($broadcast->in_app_count) }}</div>
                    </div>
                </div>

                @if ($broadcast->error_message)
                    <div class="mb-6 rounded border border-red-300 bg-red-50 px-4 py-3 text-red-700 text-sm">
                        {{ $broadcast->error_message }}
                    </div>
                @endif

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold text-gray-800 dark:text-white mb-3">Registered devices (active)</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div>Active: <strong>{{ number_format($deviceStats['active']) }}</strong></div>
                        <div>iOS: <strong>{{ number_format($deviceStats['ios']) }}</strong></div>
                        <div>Android: <strong>{{ number_format($deviceStats['android']) }}</strong></div>
                        <div>Web: <strong>{{ number_format($deviceStats['web']) }}</strong></div>
                    </div>
                    <a href="{{ route('push-notifications.devices') }}" class="mt-3 inline-block text-sm text-blue-600 hover:text-blue-800">
                        View registered devices →
                    </a>
                </div>

                <dl class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Audience</dt>
                        <dd class="font-medium capitalize">{{ str_replace('_', ' ', $broadcast->audience) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Channels</dt>
                        <dd class="font-medium">{{ implode(', ', $broadcast->channels ?? []) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Created by</dt>
                        <dd class="font-medium">{{ $broadcast->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Push failed</dt>
                        <dd class="font-medium">{{ number_format($broadcast->push_failed_count) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
