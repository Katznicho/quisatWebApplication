@php
    use App\Models\Business;

    // Load businesses with their branches
    $businesses = Business::with('branches')->get()->keyBy('id');

    // Prepare business-branch mapping for JSON
    $businessBranchData = $businesses->map(function ($b) {
        return [
            'id' => $b->id,
            'branches' => $b->branches->map(function ($br) {
                return [
                    'id' => $br->id,
                    'name' => $br->name,
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

<x-app-layout>
    <div class="py-12" x-data="userForm()" x-init="init()" @keydown.escape.window="showModal = false">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Manage Users</h2>

                    @if(Auth::user()->business_id == 1)
                        <button @click="showModal = true"
                                class="inline-flex items-center px-4 py-2 bg-[#011478] text-white text-sm font-semibold rounded-md hover:bg-[#011478]/90 transition duration-150">
                            ➕ Create User
                        </button>
                    @endif
                </div>

                @livewire('list-users')
            </div>
        </div>

        <!-- Modal -->
        <div x-show="showModal" x-transition.opacity
             class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="showModal = false"
                 class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Create New User</h3>
                    <button @click="showModal = false"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100">✕</button>
                </div>

                <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label for="name" class="block font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input type="text" name="name" id="name" placeholder="Enter full name" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" name="email" id="email" placeholder="example@email.com" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('email') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="status" class="block font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" id="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        @error('status') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="business_id" class="block font-medium text-gray-700 dark:text-gray-300">Business</label>
                        <select name="business_id" id="business_id" required
                                x-model="selectedBusinessId"
                                @change="updateBranches"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Business</option>
                            @foreach($businesses as $id => $business)
                                <option value="{{ $id }}">{{ $business->name }}</option>
                            @endforeach
                        </select>
                        @error('business_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="branch_id" class="block font-medium text-gray-700 dark:text-gray-300">Branch</label>
                        <select name="branch_id" id="branch_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                :disabled="!branches.length">
                            <option value="">Select Branch</option>
                            <template x-for="branch in branches" :key="branch.id">
                                <option :value="branch.id" x-text="branch.name"></option>
                            </template>
                        </select>
                        @error('branch_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="profile_photo_path" class="block font-medium text-gray-700 dark:text-gray-300">Profile Photo</label>
                        <input type="file" name="profile_photo_path" id="profile_photo_path" accept="image/*"
                               class="mt-1 block w-full text-gray-700 dark:text-gray-300">
                        @error('profile_photo_path') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" @click="showModal = false"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-[#011478] text-white rounded-md hover:bg-[#011478]/90">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AlpineJS logic -->
    <script>
        function userForm() {
            return {
                showModal: false,
                selectedBusinessId: '',
                branches: [],
                businessData: @json($businessBranchData),

                init() {
                    this.$watch('showModal', (open) => {
                        if (open) {
                            this.selectedBusinessId = '';
                            this.branches = [];
                        }
                    });
                },

                updateBranches() {
                    const biz = this.businessData.find(b => b.id == this.selectedBusinessId);
                    this.branches = biz ? biz.branches : [];
                },
            }
        }
    </script>
</x-app-layout>
