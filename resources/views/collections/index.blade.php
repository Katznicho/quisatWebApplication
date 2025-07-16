<x-app-layout>
    <div class="py-12" x-data="{ showModal: false }" @keydown.escape.window="showModal = false" x-cloak>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Collect Money from your Users</h2>

                    <button @click="showModal = true"
                        class="inline-flex items-center px-4 py-2 bg-[#011478] text-white text-sm font-semibold rounded-md hover:bg-[#011478]/90 transition duration-150">
                        ➕ Collect Now
                    </button>
                </div>

                {{-- You can list existing collections here, or stats --}}
            </div>
        </div>

        <!-- Modal for Collecting Money -->
        <div x-show="showModal" x-transition.opacity
            class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50"
            style="display: none;">
            <div @click.away="showModal = false" x-transition
                class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Collect Money</h3>
                    <button @click="showModal = false"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100">
                        ✕
                    </button>
                </div>
                <form action="{{ route('collections.store') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6" x-data="{ method: 'mobile_money' }">
                    @csrf

                    <div>
                        <label for="title" class="block font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" name="title" id="title" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="e.g. Monthly Subscription">
                    </div>
                                        <div>
                        <label for="method" class="block font-medium text-gray-700 dark:text-gray-300">Payment
                            Method</label>
                        <select name="method" id="method" x-model="method" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="mobile_money">Mobile Money</option>
                            <option value="card">Card (Coming Soon)</option>
                            <option value="bank_transfer">Bank Transfer (Coming Soon)</option>
                            <option value="crypto">Crypto (Coming Soon)</option>
                        </select>
                    </div>

                    <div>
                        <label for="amount" class="block font-medium text-gray-700 dark:text-gray-300">Amount to
                            Collect</label>
                        <input type="number" step="0.01" name="amount" id="amount" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="e.g. 10000">
                    </div>

                    <div>
                        <label for="phone_number" class="block font-medium text-gray-700 dark:text-gray-300">Phone
                            Number to Charge (Format: 256xxxxxxxxx)</label>
                        <input type="text" name="phone_number" id="phone_number" required pattern="256[0-9]{9}"
                            maxlength="12"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="e.g. 256772345678">
                    </div>


                    <div x-show="method !== 'mobile_money'" class="text-yellow-600 text-sm italic">
                        This method is not yet supported. Please select Mobile Money to collect now.
                    </div>

                    <div>
                        <label for="description" class="block font-medium text-gray-700 dark:text-gray-300">Description
                            (Optional)</label>
                        <textarea name="description" id="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="Reason for the collection (e.g. Event Registration, Loan Payment)"></textarea>
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-[#011478] text-white rounded-md hover:bg-[#011478]/90">
                            Collect Now
                        </button>
                    </div>
                </form>



            </div>
        </div>
    </div>
</x-app-layout>
