<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <a href="{{ route('push-notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to notifications</a>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mt-2">Send push notification</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Message will be delivered to registered devices on mobile (iOS/Android) and web browsers.
                    </p>
                </div>

                <form method="POST" action="{{ route('push-notifications.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" name="title" value="{{ old('title') }}" maxlength="120" required
                            class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm"
                            placeholder="e.g. School closed tomorrow">
                        @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                        <textarea name="body" rows="4" maxlength="1000" required
                            class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm"
                            placeholder="Write your notification message here...">{{ old('body') }}</textarea>
                        @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Audience</label>
                        <select name="audience" class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                            @if (auth()->user()->business_id == 1)
                                <option value="all" @selected(old('audience') === 'all')>All users (parents + staff)</option>
                                <option value="parents" @selected(old('audience') === 'parents')>Parents only</option>
                                <option value="staff" @selected(old('audience') === 'staff')>Staff only</option>
                                <option value="business" @selected(old('audience') === 'business')>Specific business</option>
                            @else
                                <option value="business" @selected(old('audience', 'business') === 'business')>Everyone in my business</option>
                                <option value="parents" @selected(old('audience') === 'parents')>Parents in my business</option>
                                <option value="staff" @selected(old('audience') === 'staff')>Staff in my business</option>
                            @endif
                        </select>
                        @error('audience')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    @if (auth()->user()->business_id == 1)
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Business (when audience is specific business)</label>
                            <select name="business_id" class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="">— Select business —</option>
                                @foreach ($businesses as $business)
                                    <option value="{{ $business->id }}" @selected((string) old('business_id') === (string) $business->id)>
                                        {{ $business->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('business_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Delivery channels</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="channels[]" value="push" @checked(collect(old('channels', ['push', 'in_app']))->contains('push'))>
                                Push notification (mobile + web browser)
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="channels[]" value="in_app" @checked(collect(old('channels', ['push', 'in_app']))->contains('in_app'))>
                                In-app notification inbox
                            </label>
                        </div>
                        @error('channels')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Deep link (optional)</label>
                        <input type="text" name="deep_link" value="{{ old('deep_link') }}"
                            class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm"
                            placeholder="/orders or screen name for mobile app">
                        @error('deep_link')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('push-notifications.index') }}"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Send now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
