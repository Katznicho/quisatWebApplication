<x-guest-layout title="Verify OTP">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-[#322f90] mb-2">{{ __('Enter the OTP sent to your email') }}</h1>
        <p class="text-sm text-gray-600">Enter the one-time password you received.</p>
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-red-600">
            {{ session('status') }}
        </div>
    @endif

    <form id="verifyOtpForm">
        @csrf
        <input type="hidden" id="username" name="username" value="{{ $username }}">
        <input type="hidden" id="customerNo" name="customerNo" value="{{ $customerNo }}">
        <div class="space-y-4">
            <div>
                <label for="otp" class="block mb-2 text-gray-700 font-medium">{{ __('Enter OTP') }} <span class="text-red-500">*</span></label>
                <input id="otp" type="text" name="otp" placeholder="Enter OTP" required autofocus
                    class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-base focus:outline-none focus:border-[#322f90] transition-colors">
            </div>
        </div>
        <div class="flex justify-end mt-6">
            <button type="submit" class="w-full py-3 px-4 rounded-lg text-base font-medium text-white bg-[#322f90] hover:bg-[#252171] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#322f90] transition-colors">
                {{ __('Confirm OTP') }}
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
        document.getElementById('verifyOtpForm').addEventListener('submit', function(event) {
            event.preventDefault();
            var username = document.getElementById('username').value;
            var customerNo = document.getElementById('customerNo').value;
            var otp = document.getElementById('otp').value;

            Swal.fire({
                title: 'Please wait...',
                html: 'Verifying...',
                allowOutsideClick: false,
                didOpen: function() { Swal.showLoading(); }
            });

            var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

            fetch('{{ route("verify-otp.post") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ _token: CSRF_TOKEN, username: username, customerNo: customerNo, otp: otp })
            })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                Swal.close();
                if (result.response === 'success') {
                    var u = result.username || username;
                    var c = result.customerNo || customerNo;
                    Swal.fire('Success', result.message, 'success').then(function() {
                        window.location.href = '{{ url("/change-password") }}/' + encodeURIComponent(u) + '/' + encodeURIComponent(c);
                    });
                } else {
                    Swal.fire('Warning', result.message || 'Invalid or expired OTP.', 'warning');
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
