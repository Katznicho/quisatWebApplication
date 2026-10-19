<x-guest-layout title="Update Password">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-[#322f90] mb-2">{{ __('Update Password') }}</h1>
        <p class="text-sm text-gray-600">Enter your new password (8–16 characters).</p>
    </div>

    <form id="updatePasswordForm">
        @csrf
        <input type="hidden" name="username" id="username" value="{{ $username }}">
        <input type="hidden" name="customerNo" id="customerNo" value="{{ $customerNo }}">

        <div class="space-y-4">
            <div>
                <label for="password" class="block mb-2 text-gray-700 font-medium">{{ __('New Password') }} <span class="text-red-500">*</span></label>
                <input id="password" type="password" name="password" required minlength="8" maxlength="16" autocomplete="new-password" placeholder="Enter new password"
                    class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-base focus:outline-none focus:border-[#322f90] transition-colors">
                <p class="mt-1 text-xs text-gray-500">Password must be between 8 and 16 characters.</p>
            </div>
            <div>
                <label for="password_confirmation" class="block mb-2 text-gray-700 font-medium">{{ __('Confirm Password') }} <span class="text-red-500">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="16" autocomplete="new-password" placeholder="Confirm new password"
                    class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-base focus:outline-none focus:border-[#322f90] transition-colors">
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="w-full py-3 px-4 rounded-lg text-base font-medium text-white bg-[#322f90] hover:bg-[#252171] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#322f90] transition-colors">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>

    <div class="mt-6 text-center">
        <a class="text-sm text-[#322f90] hover:text-[#d1111b] hover:no-underline" href="{{ route('login') }}">
            ← {{ __('Back to Login') }}
        </a>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('updatePasswordForm').addEventListener('submit', function(event) {
            event.preventDefault();
            var username = document.getElementById('username').value;
            var customerNo = document.getElementById('customerNo').value;
            var password = document.getElementById('password').value;
            var password_confirmation = document.getElementById('password_confirmation').value;

            if (password.length < 8 || password.length > 16) {
                Swal.fire('Error', 'Password must be between 8 and 16 characters.', 'error');
                return;
            }
            if (password !== password_confirmation) {
                Swal.fire('Error', 'Passwords do not match.', 'error');
                return;
            }

            Swal.fire({
                title: 'Please wait...',
                html: 'Updating password...',
                allowOutsideClick: false,
                didOpen: function() { Swal.showLoading(); }
            });

            var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

            fetch('{{ route("change-password") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({
                    _token: CSRF_TOKEN,
                    username: username,
                    customerNo: customerNo,
                    password: password,
                    password_confirmation: password_confirmation
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                Swal.close();
                if (result.response === 'success') {
                    Swal.fire('Success', result.message || 'Password updated successfully.', 'success').then(function() {
                        window.location.href = '{{ route("login") }}';
                    });
                } else {
                    Swal.fire('Warning', result.message || 'Failed to update password.', 'warning');
                }
            })
            .catch(function(err) {
                Swal.close();
                Swal.fire('Error', 'An error occurred. Please try again.', 'error');
            });
        });
    </script>
    @endpush
</x-guest-layout>
