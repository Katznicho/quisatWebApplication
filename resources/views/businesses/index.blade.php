<x-app-layout>
    <div class="py-12" x-data="{ showModal: false }" x-cloak>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Manage Businesses</h2>

                    @if (Auth::check() && Auth::user()->business_id == 1)
                        <button @click="showModal = true"
                            class="inline-flex items-center px-4 py-2 bg-[#011478] text-white text-sm font-semibold rounded-md hover:bg-[#011478]/90 transition duration-150">
                            ➕ Create Business
                        </button>
                    @endif
                </div>

                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show"
                        class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 transition"
                        role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                        <button @click="show = false"
                            class="absolute top-1 right-2 text-xl font-semibold text-green-700">
                            &times;
                        </button>
                    </div>
                @endif


                @livewire('list-business')
            </div>
        </div>

        <!-- Modal -->
        <div x-show="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-2xl p-6 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Create New Business</h3>
                    <button @click="showModal = false"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100">
                        ✕
                    </button>
                </div>

                <form action="{{ route('businesses.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="email"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" id="email" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('email')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="phone"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input type="tel" name="phone" id="phone"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('phone')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="address"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                            <input type="text" name="address" id="address"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('address')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="logo"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Logo</label>
                            <input type="file" name="logo" id="logo" accept="image/*"
                                class="mt-1 block w-full text-gray-700 dark:text-gray-300">
                            @error('logo')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>


                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="showModal = false"
                            class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-[#011478] text-white rounded-md hover:bg-[#011478]/90">
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
