<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Country & Currency Setup</h2>
                </div>

                @if (session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-1 rounded-lg border border-gray-200 p-4">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Add Country</h3>
                        <form method="POST" action="{{ route('countries.store') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Country</label>
                                <input type="text" name="name" value="{{ old('name', 'Uganda') }}"
                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Currency Code</label>
                                <input type="text" name="currency_code" value="{{ old('currency_code', 'UGX') }}"
                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Currency Name</label>
                                <input type="text" name="currency_name" value="{{ old('currency_name', 'Ugandan Shilling') }}"
                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Exchange Rate</label>
                                <input type="number" step="0.000001" min="0.000001" name="exchange_rate" value="{{ old('exchange_rate', '1') }}"
                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm" required>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_default" value="1" {{ old('is_default', true) ? 'checked' : '' }}>
                                Set as default
                            </label>
                            <button type="submit"
                                class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Save Country
                            </button>
                        </form>
                    </div>

                    <div class="lg:col-span-2 rounded-lg border border-gray-200 p-4">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Configured Countries</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-gray-50 text-left">
                                        <th class="px-3 py-2">Country</th>
                                        <th class="px-3 py-2">Currency</th>
                                        <th class="px-3 py-2">Rate</th>
                                        <th class="px-3 py-2">Default</th>
                                        <th class="px-3 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($countries as $country)
                                        <tr class="border-b align-top">
                                            <td class="px-3 py-3">
                                                <form method="POST" action="{{ route('countries.update', $country) }}" class="space-y-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="name" value="{{ $country->name }}" class="w-full rounded border border-gray-300 px-2 py-1">
                                            </td>
                                            <td class="px-3 py-3">
                                                    <input type="text" name="currency_code" value="{{ $country->currency_code }}" class="mb-2 w-full rounded border border-gray-300 px-2 py-1">
                                                    <input type="text" name="currency_name" value="{{ $country->currency_name }}" class="w-full rounded border border-gray-300 px-2 py-1">
                                            </td>
                                            <td class="px-3 py-3">
                                                    <input type="number" step="0.000001" min="0.000001" name="exchange_rate" value="{{ $country->exchange_rate }}" class="w-full rounded border border-gray-300 px-2 py-1">
                                            </td>
                                            <td class="px-3 py-3">
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox" name="is_default" value="1" {{ $country->is_default ? 'checked' : '' }}>
                                                    <span>{{ $country->is_default ? 'Yes' : 'No' }}</span>
                                                </label>
                                            </td>
                                            <td class="px-3 py-3">
                                                    <button type="submit" class="mb-2 rounded bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700">Update</button>
                                                </form>
                                                <form method="POST" action="{{ route('countries.destroy', $country) }}" onsubmit="return confirm('Delete this country?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded bg-red-600 px-3 py-1 text-white hover:bg-red-700">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-6 text-center text-gray-500">No countries configured.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
