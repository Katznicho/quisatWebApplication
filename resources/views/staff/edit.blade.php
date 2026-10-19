<x-app-layout title="Edit User - Stabex Customer Portal">
    <x-dashboard-layout header="Edit User">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <h1 class="text-lg font-semibold mb-6 text-[#322f90]">Edit User</h1>

            <div class="bg-white shadow overflow-hidden sm:rounded-lg border-b-4 border-[#322f90]">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-[#322f90]">User Management</h3>
                </div>

                <div class="p-6">
                    <form id="editStaffForm">
                        @csrf
                        <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                            <div>
                                <label for="customer_no" class="block text-sm font-medium text-gray-700">Customer No</label>
                                <input type="text" name="customer_no" id="customer_no" value="{{ $user->Customer_No ?? '' }}" readonly
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-100">
                            </div>

                            <input type="hidden" name="patch_key" id="patch_key" value="{{ $user->Patchkey ?? $user->PatchKey ?? '' }}" />

                            <div>
                                <label for="user_name" class="block text-sm font-medium text-gray-700">Username</label>
                                <input type="text" name="user_name" id="user_name" value="{{ $user->User_Name ?? '' }}" readonly
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-100">
                            </div>

                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" name="full_name" id="full_name" value="{{ $user->Full_Name ?? '' }}"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label for="phone_no" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" name="phone_no" id="phone_no" value="{{ $user->Phone_No ?? '' }}"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="email" value="{{ $user->Email ?? '' }}"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label for="account_type" class="block text-sm font-medium text-gray-700">Account Type</label>
                                <input type="text" name="account_type" id="account_type" value="{{ $user->Account_Type ?? '' }}" readonly
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-100">
                            </div>

                            <div>
                                <label for="blocked" class="block text-sm font-medium text-gray-700">Account Status</label>
                                <div class="mt-1">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="blocked" id="blocked"
                                            {{ ($user->Blocked ?? false) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-[#322f90] shadow-sm focus:border-[#322f90] focus:ring focus:ring-[#322f90] focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-600">Block Account</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <a href="{{ route('staff.index') }}" class="mr-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Back to Users
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            (function() {
                var updateFieldUrl = @json(route('staff.update-field'));

                document.addEventListener('DOMContentLoaded', function() {
                    var fullName = document.getElementById('full_name');
                    var phoneNo = document.getElementById('phone_no');
                    var email = document.getElementById('email');
                    var blocked = document.getElementById('blocked');
                    var patchKeyEl = document.getElementById('patch_key');
                    var patchKey = patchKeyEl ? patchKeyEl.value : '';

                    function updateField(fieldName, fieldValue, fieldType) {
                        fetch(updateFieldUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                patch_key: patchKey,
                                field_name: fieldName,
                                field_value: fieldValue,
                                field_type: fieldType
                            })
                        }).then(function(r) { return r.json(); }).then(function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Updated!',
                                    text: data.message,
                                    icon: 'success',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            } else {
                                throw new Error(data.message);
                            }
                        }).catch(function(error) {
                            var errorMessage = error.message || 'Failed to update field';
                            if (errorMessage.includes('Client error:')) {
                                var match = errorMessage.match(/400\s+(.+?)(?=\s+response:)/);
                                if (match && match[1]) errorMessage = match[1];
                            }
                            Swal.fire({
                                title: 'Update Failed',
                                text: errorMessage,
                                icon: 'error',
                                toast: true,
                                position: 'top-end',
                                confirmButtonColor: '#d1111b'
                            });
                        });
                    }

                    if (fullName) fullName.addEventListener('change', function() { updateField('Full_Name', this.value, 'text'); });
                    if (phoneNo) phoneNo.addEventListener('change', function() { updateField('Phone_No', this.value, 'text'); });
                    if (email) email.addEventListener('change', function() { updateField('Email', this.value, 'text'); });
                    if (blocked) blocked.addEventListener('change', function() { updateField('Blocked', this.checked, 'boolean'); });

                    var form = document.getElementById('editStaffForm');
                    if (form) form.addEventListener('submit', function(e) { e.preventDefault(); });
                });
            })();
        </script>
        @endpush
    </x-dashboard-layout>
</x-app-layout>
