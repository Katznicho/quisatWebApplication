<x-app-layout title="My Profile - Stabex Customer Portal">
    <x-dashboard-layout header="Profile">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-3xl mx-auto">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-[#322f90]">
                    <h2 class="text-lg font-semibold text-white">Profile overview</h2>
                    <p class="text-sm text-white/80">Your Stabex Customer Portal account details</p>
                </div>

                <div class="px-6 py-6 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                            @php
                                $picture = session('picture');
                            @endphp
                            @if($picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($picture))
                                <img src="{{ asset('storage/' . $picture) }}" alt="{{ session('fullName') }}" class="h-full w-full object-cover">
                            @else
                                <svg class="h-8 w-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <p class="text-base font-semibold text-gray-900">{{ session('fullName') }}</p>
                            <p class="text-sm text-gray-500">
                                @if(session('customerNumber'))
                                    Account {{ session('customerNumber') }}
                                @else
                                    Stabex Customer
                                @endif
                            </p>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Full name</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-words">
                                {{ session('fullName') ?? '—' }}
                            </dd>
                        </div>

                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Customer number</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-words">
                                {{ session('customerNumber') ?? '—' }}
                            </dd>
                        </div>

                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Phone number</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-words">
                                {{ session('phoneNumber') ?? session('phoneNo') ?? '—' }}
                            </dd>
                        </div>

                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-words">
                                {{ session('email') ?? '—' }}
                            </dd>
                        </div>

                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Account type</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-words">
                                {{ session('accountType') ?? '—' }}
                            </dd>
                        </div>

                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Role</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-words">
                                {{ session('role') ?? '—' }}
                            </dd>
                        </div>
                    </dl>

                    <!-- Update Profile Picture -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Update Profile Picture</h2>
                        <form id="updatePictureForm" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                            @csrf
                            <div class="flex-1 min-w-[200px]">
                                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Choose image (JPEG, PNG, max 2MB)</label>
                                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml" required
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-[#322f90] file:text-white hover:file:bg-[#252171]">
                            </div>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#322f90] hover:bg-[#252171] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#322f90]">
                                Update Picture
                            </button>
                        </form>
                    </div>

                    <!-- Update Password -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Update Password</h2>
                        <form id="changePasswordForm" class="space-y-4 max-w-md">
                            @csrf
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input id="new_password" name="new_password" type="password" required minlength="8" maxlength="16"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#322f90] focus:ring-[#322f90]">
                                <p class="mt-1 text-xs text-gray-500">Password must be between 8 and 16 characters</p>
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input id="confirm_password" name="confirm_password" type="password" required minlength="8" maxlength="16"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#322f90] focus:ring-[#322f90]">
                            </div>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#322f90] hover:bg-[#252171]">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        (function() {
            var changePasswordUrl = @json(route('change-password'));
            var updateProfileUrl = @json(route('update-profile'));
            var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
            var username = @json(session('userName'));
            var customerNo = @json(session('customerNumber') ?? session('customerNo'));

            document.addEventListener('DOMContentLoaded', function() {
                var cpForm = document.getElementById('changePasswordForm');
                if (cpForm) {
                    cpForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        var newPass = document.getElementById('new_password').value;
                        var confirmPass = document.getElementById('confirm_password').value;
                        if (!newPass || !confirmPass) {
                            Swal.fire('Error', 'Please fill both password fields', 'error');
                            return;
                        }
                        if (newPass.length < 8 || newPass.length > 16) {
                            Swal.fire('Error', 'Password must be between 8 and 16 characters', 'error');
                            return;
                        }
                        if (newPass !== confirmPass) {
                            Swal.fire('Error', 'Passwords do not match', 'error');
                            return;
                        }
                        Swal.fire({ title: 'Updating...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                        fetch(changePasswordUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ username: username, customerNo: customerNo, password: newPass, password_confirmation: confirmPass })
                        }).then(function(r) { return r.json(); }).then(function(data) {
                            Swal.close();
                            if (data.response === 'success') {
                                Swal.fire('Success', data.message || 'Password updated successfully', 'success').then(function() { cpForm.reset(); });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to update password', 'error');
                            }
                        }).catch(function(err) {
                            Swal.close();
                            Swal.fire('Error', err.message || 'Request failed', 'error');
                        });
                    });
                }

                var picForm = document.getElementById('updatePictureForm');
                if (picForm) {
                    picForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        var input = document.getElementById('image');
                        if (!input || !input.files.length) {
                            Swal.fire('Error', 'Please select an image', 'error');
                            return;
                        }
                        var fd = new FormData();
                        fd.append('_token', csrf);
                        fd.append('image', input.files[0]);
                        Swal.fire({ title: 'Updating...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                        fetch(updateProfileUrl, { method: 'POST', body: fd })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                Swal.close();
                                if (data.success) {
                                    Swal.fire('Success', data.message || 'Picture updated', 'success').then(function() { window.location.reload(); });
                                } else {
                                    Swal.fire('Error', data.message || 'Failed to update picture', 'error');
                                }
                            })
                            .catch(function(err) {
                                Swal.close();
                                Swal.fire('Error', err.message || 'Request failed', 'error');
                            });
                    });
                }
            });
        })();
        </script>
        @endpush
    </x-dashboard-layout>
</x-app-layout>

