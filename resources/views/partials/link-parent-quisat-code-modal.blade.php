@php
    $formAction = $formAction ?? route('school-management.parents.link-by-quisat-code');
    $redirectTo = $redirectTo ?? url()->current();
@endphp

<div x-data="{ open: false }" class="inline-flex">
    <button type="button"
            @click="open = true"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
        </svg>
        Link by Quisat code
    </button>

    <div x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog"
         aria-modal="true"
         aria-labelledby="link-quisat-code-title">
        <div class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>

        <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <div class="mb-4">
                <h3 id="link-quisat-code-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                    Link parent by Quisat code
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Ask the parent to open <strong>Profile</strong> in the Quisat app and share their code (e.g. <code>QSP-XXXXXXXX</code>).
                    Enter it here to connect their account to your {{ $contextLabel ?? 'business' }}.
                </p>
            </div>

            <form method="POST" action="{{ $formAction }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

                <div class="space-y-4">
                    <div>
                        <label for="universal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Quisat code
                        </label>
                        <input type="text"
                               id="universal_code"
                               name="universal_code"
                               value="{{ old('universal_code') }}"
                               placeholder="QSP-XXXXXXXX"
                               required
                               maxlength="32"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white uppercase"
                               autocomplete="off">
                        @error('universal_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="relationship" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Relationship (optional)
                        </label>
                        <select id="relationship"
                                name="relationship"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Select relationship</option>
                            <option value="father" @selected(old('relationship') === 'father')>Father</option>
                            <option value="mother" @selected(old('relationship') === 'mother')>Mother</option>
                            <option value="guardian" @selected(old('relationship') === 'guardian')>Guardian</option>
                            <option value="other" @selected(old('relationship') === 'other')>Other</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                            @click="open = false"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Link parent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('universal_code'))
    <script>
        document.addEventListener('alpine:init', () => {
            window.dispatchEvent(new CustomEvent('open-quisat-link-modal'));
        });
    </script>
@endif
